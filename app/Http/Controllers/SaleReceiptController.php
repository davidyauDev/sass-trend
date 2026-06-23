<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\SaleListingQuery;
use App\Services\Sales\SaleManagementGuard;
use App\Services\Sales\SalePaymentMethodCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleReceiptController extends Controller
{
    public function __construct(
        private readonly SaleManagementGuard $guard,
        private readonly SaleListingQuery $saleListingQuery,
    ) {}

    public function show(Request $request, int $sale): View
    {
        $this->guard->ensureCanView($this->authUser());

        $record = Sale::query()
            ->withTrashed()
            ->with(['branch', 'client', 'items.product.presentation', 'items.service', 'payments'])
            ->findOrFail($sale);

        return view('sales.receipt', [
            'sale' => $record,
            'paymentMethods' => SalePaymentMethodCatalog::options(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->guard->ensureCanView($this->authUser());

        $sales = $this->saleListingQuery
            ->handle($this->filtersFromRequest($request))
            ->latest('sold_at')
            ->get();

        $filename = 'ventas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sales): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Venta', 'Fecha', 'Estado', 'Cliente', 'Local', 'Metodos de pago', 'Total', 'Pagado'], ';');

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->sale_number ?? $sale->id,
                    $sale->sold_at->format('d/m/Y H:i'),
                    $sale->status,
                    $sale->client?->fullName() ?? 'Consumidor final',
                    $sale->branch->name,
                    $sale->payments
                        ->pluck('method')
                        ->map(fn (string $method): string => SalePaymentMethodCatalog::options()[$method] ?? $method)
                        ->implode(', '),
                    number_format((float) $sale->total, 2, '.', ''),
                    number_format((float) $sale->paid_total, 2, '.', ''),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{search:string,period:string,client:string,status:string,payment:string,branch:string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => (string) $request->string('q')->value(),
            'period' => (string) $request->string('period', '7')->value(),
            'client' => (string) $request->string('client')->value(),
            'status' => (string) $request->string('status')->value(),
            'payment' => (string) $request->string('payment')->value(),
            'branch' => (string) $request->string('branch')->value(),
        ];
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
