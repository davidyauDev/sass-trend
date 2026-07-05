<?php

namespace App\Livewire\Administracion\Comisiones;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalCommission;
use App\Models\SaleItem;
use App\Models\Service;
use App\Services\Commissions\CommissionReportWorkbookExport;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Title('Reporte de comisiones')]
class Report extends Component
{
    use WithPagination;

    public string $period = 'last_7_days';

    public string $branchId = '';

    public string $userType = 'active_professionals';

    public string $professionalId = 'all';

    public string $sortField = 'commission_amount';

    public string $sortDirection = 'desc';

    public int $perPage = 10;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('viewAny', ProfessionalCommission::class) === true, 403);

        $this->branchId = (string) (Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id') ?? '');
    }

    public function applyFilters(): void
    {
        if (
            $this->professionalId !== 'all'
            && ! $this->availableProfessionals()->contains(fn (Professional $professional): bool => (string) $professional->id === $this->professionalId)
        ) {
            $this->professionalId = 'all';
        }

        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->resetPage();

            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function exportReport(): BinaryFileResponse
    {
        $filename = 'reporte-comisiones-'.$this->resolvedPeriod()['start']->format('Ymd').'-'.$this->resolvedPeriod()['end']->format('Ymd').'.xlsx';

        Flux::toast(variant: 'success', text: 'Exportando reporte de comisiones.');

        $path = app(CommissionReportWorkbookExport::class)->export([
            'period' => $this->period,
            'branchId' => $this->branchId,
            'userType' => $this->userType,
            'professionalId' => $this->professionalId,
        ]);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    #[Computed]
    public function branches(): EloquentCollection
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableProfessionals(): EloquentCollection
    {
        return Professional::query()
            ->when(
                $this->userType === 'active_professionals',
                fn ($query) => $query->where('is_active', true),
            )
            ->orderBy('public_name')
            ->get();
    }

    #[Computed]
    public function reportRowsPaginator(): LengthAwarePaginator
    {
        $rows = $this->reportRowsCollection();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = max(1, $this->perPage);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @return Collection<int, array{professional_id:int,professional_name:string,is_active:bool,sales_total:float,commission_amount:float}>
     */
    private function reportRowsCollection(): Collection
    {
        $professionals = Professional::query()
            ->when($this->userType === 'active_professionals', fn ($query) => $query->where('is_active', true))
            ->get()
            ->keyBy('id');

        $items = SaleItem::query()
            ->with([
                'sale.branch',
                'service.professionalProfiles',
                'product',
            ])
            ->whereIn('item_type', ['service', 'product'])
            ->whereHas('sale', function ($query): void {
                $period = $this->resolvedPeriod();

                $query
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['paid', 'partial'])
                    ->whereBetween('sold_at', [$period['start']->startOfDay(), $period['end']->endOfDay()]);

                if ($this->branchId !== '') {
                    $query->where('branch_id', (int) $this->branchId);
                }
            })
            ->get();

        $rows = collect();

        foreach ($items as $item) {
            $professionalId = (int) data_get($item->meta, 'professional_id', 0);

            if ($professionalId === 0) {
                continue;
            }

            if ($this->professionalId !== 'all' && $professionalId !== (int) $this->professionalId) {
                continue;
            }

            /** @var Professional|null $professional */
            $professional = $professionals->get($professionalId);

            if (! $professional instanceof Professional) {
                continue;
            }

            $salesTotal = round((float) $item->subtotal, 2);
            $commissionAmount = $this->calculateCommission($item, $professional);

            $current = $rows->get($professionalId, [
                'professional_id' => $professionalId,
                'professional_name' => $professional->public_name,
                'is_active' => $professional->is_active,
                'sales_total' => 0.0,
                'commission_amount' => 0.0,
            ]);

            $current['sales_total'] = round($current['sales_total'] + $salesTotal, 2);
            $current['commission_amount'] = round($current['commission_amount'] + $commissionAmount, 2);

            $rows->put($professionalId, $current);
        }

        return $rows
            ->values()
            ->sortBy([
                [$this->sortField, $this->sortDirection],
                ['professional_name', 'asc'],
            ])
            ->values();
    }

    /**
     * @return array{service_sales:float,product_sales:float,total_sales:float,total_commissions:float}
     */
    #[Computed]
    public function summary(): array
    {
        $items = SaleItem::query()
            ->with(['sale', 'service.professionalProfiles', 'product'])
            ->whereIn('item_type', ['service', 'product'])
            ->whereHas('sale', function ($query): void {
                $period = $this->resolvedPeriod();

                $query
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['paid', 'partial'])
                    ->whereBetween('sold_at', [$period['start']->startOfDay(), $period['end']->endOfDay()]);

                if ($this->branchId !== '') {
                    $query->where('branch_id', (int) $this->branchId);
                }
            })
            ->get();

        $professionals = Professional::query()->get()->keyBy('id');

        $serviceSales = 0.0;
        $productSales = 0.0;
        $totalCommissions = 0.0;

        foreach ($items as $item) {
            $professionalId = (int) data_get($item->meta, 'professional_id', 0);

            if ($professionalId === 0) {
                continue;
            }

            if ($this->professionalId !== 'all' && $professionalId !== (int) $this->professionalId) {
                continue;
            }

            /** @var Professional|null $professional */
            $professional = $professionals->get($professionalId);

            if (! $professional instanceof Professional) {
                continue;
            }

            $subtotal = round((float) $item->subtotal, 2);

            if ($item->item_type === 'service') {
                $serviceSales += $subtotal;
            }

            if ($item->item_type === 'product') {
                $productSales += $subtotal;
            }

            $totalCommissions += $this->calculateCommission($item, $professional);
        }

        return [
            'service_sales' => round($serviceSales, 2),
            'product_sales' => round($productSales, 2),
            'total_sales' => round($serviceSales + $productSales, 2),
            'total_commissions' => round($totalCommissions, 2),
        ];
    }

    /**
     * @return array<int, array{value:string,label:string,start:CarbonImmutable,end:CarbonImmutable}>
     */
    #[Computed]
    public function periodOptions(): array
    {
        $today = CarbonImmutable::today();

        return [
            [
                'value' => 'today',
                'label' => $today->format('d/m/Y').' - '.$today->format('d/m/Y'),
                'start' => $today,
                'end' => $today,
            ],
            [
                'value' => 'last_7_days',
                'label' => $today->subDays(7)->format('d/m/Y').' - '.$today->format('d/m/Y'),
                'start' => $today->subDays(7),
                'end' => $today,
            ],
            [
                'value' => 'last_15_days',
                'label' => $today->subDays(15)->format('d/m/Y').' - '.$today->format('d/m/Y'),
                'start' => $today->subDays(15),
                'end' => $today,
            ],
            [
                'value' => 'last_30_days',
                'label' => $today->subDays(30)->format('d/m/Y').' - '.$today->format('d/m/Y'),
                'start' => $today->subDays(30),
                'end' => $today,
            ],
        ];
    }

    /**
     * @return array{value:string,label:string,start:CarbonImmutable,end:CarbonImmutable}
     */
    public function resolvedPeriod(): array
    {
        return collect($this->periodOptions())
            ->firstWhere('value', $this->period)
            ?? $this->periodOptions()[1];
    }

    public function money(float $amount): string
    {
        $formatted = number_format($amount, 2, ',', '');
        $formatted = rtrim(rtrim($formatted, '0'), ',');

        return 'S/'.$formatted;
    }

    public function percentOf(float $amount, float $total): string
    {
        if ($total <= 0) {
            return '0%';
        }

        return number_format(($amount / $total) * 100, 2).'%';
    }

    public function sortIndicator(string $field): string
    {
        if ($this->sortField !== $field) {
            return '↕';
        }

        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }

    public function render(): View
    {
        return view('livewire.administracion.comisiones.report')->layout('layouts.app');
    }

    private function calculateCommission(SaleItem $item, Professional $professional): float
    {
        $revenue = round((float) $item->subtotal, 2);

        if ($item->item_type === 'service' && $item->service instanceof Service) {
            $assignment = $item->service->professionalProfiles->firstWhere('id', $professional->id);
            $amount = (float) ($assignment?->pivot?->sale_commission ?? $professional->sale_commission ?? 0);
            $type = (string) ($assignment?->pivot?->commission_type ?? $professional->commission_type ?? 'percent');

            return $this->resolveCommissionAmount($revenue, $amount, $type);
        }

        if ($item->item_type === 'product' && $item->product instanceof Product) {
            return $this->resolveCommissionAmount(
                $revenue,
                (float) ($item->product->sale_commission ?? 0),
                (string) ($item->product->commission_type ?? 'percent'),
            );
        }

        return 0.0;
    }

    private function resolveCommissionAmount(float $revenue, float $amount, string $type): float
    {
        return $type === 'amount'
            ? round(max(0, $amount), 2)
            : round($revenue * max(0, $amount) / 100, 2);
    }
}
