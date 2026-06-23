<?php

namespace App\Livewire\Administracion\Comisiones;

use App\Actions\Commissions\ApproveCommissionAction;
use App\Actions\Commissions\ApproveCommissionSettlementAction;
use App\Actions\Commissions\CreateCommissionRuleAction;
use App\Actions\Commissions\CreateCommissionSettlementAction;
use App\Actions\Commissions\MarkCommissionSettlementPaidAction;
use App\Actions\Commissions\RejectCommissionAction;
use App\Actions\Commissions\ReverseCommissionAction;
use App\Actions\Commissions\UpdateCommissionRuleAction;
use App\DTOs\Commissions\CommissionSettlementData;
use App\Livewire\Forms\CommissionRuleForm;
use App\Livewire\Forms\CommissionSettlementForm;
use App\Models\Branch;
use App\Models\CommissionAuditLog;
use App\Models\CommissionRule;
use App\Models\CommissionSettlement;
use App\Models\ProfessionalCommission;
use App\Models\User;
use App\Repositories\Commissions\CommissionReportRepository;
use App\Repositories\Commissions\CommissionRepository;
use App\Services\Commissions\CommissionMetricsService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Comisiones')]
class Index extends Component
{
    use WithPagination;

    public CommissionRuleForm $ruleForm;

    public CommissionSettlementForm $settlementForm;

    public string $section = 'dashboard';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'branch')]
    public string $branchFilter = '';

    #[Url(as: 'professional')]
    public string $professionalFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'source')]
    public string $sourceFilter = '';

    #[Url(as: 'from')]
    public string $dateFrom = '';

    #[Url(as: 'to')]
    public string $dateTo = '';

    #[Url]
    public int $perPage = 10;

    public bool $isRuleModalOpen = false;

    public bool $isSettlementModalOpen = false;

    public ?int $selectedCommissionId = null;

    public ?int $selectedRuleId = null;

    public ?int $selectedSettlementId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('viewAny', ProfessionalCommission::class) === true, 403);

        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->ruleForm->resetForm();
        $this->settlementForm->resetForm();
    }

    public function updatedSection(): void
    {
        $this->selectedCommissionId = null;
        $this->selectedRuleId = null;
        $this->selectedSettlementId = null;
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function openRuleModal(): void
    {
        $this->authorize('create', CommissionRule::class);

        $this->ruleForm->resetForm();
        $this->isRuleModalOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function openEditRuleModal(int $ruleId): void
    {
        $rule = CommissionRule::query()->findOrFail($ruleId);

        $this->authorize('update', $rule);

        $this->ruleForm->fillFromRule($rule);
        $this->isRuleModalOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function closeRuleModal(): void
    {
        $this->ruleForm->resetForm();
        $this->isRuleModalOpen = false;
    }

    public function saveRule(CreateCommissionRuleAction $createRule, UpdateCommissionRuleAction $updateRule): void
    {
        $this->ruleForm->validate();

        $payload = $this->ruleForm->payload();

        if ($this->ruleForm->commissionRuleId !== null) {
            $rule = CommissionRule::query()->findOrFail($this->ruleForm->commissionRuleId);
            $this->authorize('update', $rule);
            $updateRule->handle($this->authUser(), $rule, $payload);
            $message = 'Regla de comisión actualizada.';
        } else {
            $this->authorize('create', CommissionRule::class);
            $createRule->handle($this->authUser(), $payload);
            $message = 'Regla de comisión creada.';
        }

        $this->closeRuleModal();
        Flux::toast(variant: 'success', text: $message);
    }

    public function openSettlementModal(): void
    {
        $this->authorize('create', CommissionSettlement::class);

        $this->settlementForm->resetForm();
        $this->isSettlementModalOpen = true;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function closeSettlementModal(): void
    {
        $this->settlementForm->resetForm();
        $this->isSettlementModalOpen = false;
    }

    public function saveSettlement(CreateCommissionSettlementAction $createSettlement): void
    {
        $this->settlementForm->validate();

        $payload = $this->settlementForm->payload();

        $createSettlement->handle($this->authUser(), new CommissionSettlementData(
            branchId: $payload['branch_id'],
            periodType: $payload['period_type'],
            startsAt: $payload['starts_at'],
            endsAt: $payload['ends_at'],
            notes: $payload['notes'],
        ));
        $this->closeSettlementModal();

        Flux::toast(variant: 'success', text: 'Liquidación generada.');
    }

    public function approveSelectedCommission(ApproveCommissionAction $approveCommission): void
    {
        $commission = $this->selectedCommission();

        if ($commission === null) {
            return;
        }

        $this->authorize('approve', $commission);
        $approveCommission->handle($this->authUser(), $commission);
        Flux::toast(variant: 'success', text: 'Comisión aprobada.');
    }

    public function rejectSelectedCommission(RejectCommissionAction $rejectCommission): void
    {
        $commission = $this->selectedCommission();

        if ($commission === null) {
            return;
        }

        $this->authorize('reject', $commission);
        $rejectCommission->handle($this->authUser(), $commission);
        Flux::toast(variant: 'success', text: 'Comisión rechazada.');
    }

    public function reverseSelectedCommission(ReverseCommissionAction $reverseCommission): void
    {
        $commission = $this->selectedCommission();

        if ($commission === null) {
            return;
        }

        $reverseCommission->handle($this->authUser(), $commission, 'Manual reversal from commissions module.');
        Flux::toast(variant: 'success', text: 'Comisión revertida.');
    }

    public function approveSettlement(ApproveCommissionSettlementAction $approveSettlement): void
    {
        $settlement = $this->selectedSettlement();

        if ($settlement === null) {
            return;
        }

        $approveSettlement->handle($this->authUser(), $settlement);
        Flux::toast(variant: 'success', text: 'Liquidación aprobada.');
    }

    public function markSettlementPaid(MarkCommissionSettlementPaidAction $markPaid): void
    {
        $settlement = $this->selectedSettlement();

        if ($settlement === null) {
            return;
        }

        $markPaid->handle($this->authUser(), $settlement, (float) $settlement->total_commissions, null, 'bank_transfer');
        Flux::toast(variant: 'success', text: 'Liquidación pagada.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'branchFilter', 'professionalFilter', 'statusFilter', 'sourceFilter', 'dateFrom', 'dateTo']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function exportReport(string $format): void
    {
        Flux::toast(variant: 'success', text: 'Exportación preparada en formato '.$format.'.');
    }

    /**
     * @return SupportCollection<int, Branch>
     */
    #[Computed]
    public function branches(): SupportCollection
    {
        return Branch::query()->orderBy('name')->get();
    }

    /**
     * @return SupportCollection<int, User>
     */
    #[Computed]
    public function professionals(): SupportCollection
    {
        return User::query()
            ->with('role')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{total_commissions: float, pending_commissions: float, approved_commissions: float, paid_commissions: float, revenue_generated: float}
     */
    #[Computed]
    public function dashboardMetrics(): array
    {
        return app(CommissionMetricsService::class)->dashboardMetrics(
            $this->branchFilter !== '' ? (int) $this->branchFilter : null,
        );
    }

    /**
     * @return SupportCollection<int, array{user_id: int, name: string, revenue: float, commissions: float, completed: int}>
     */
    #[Computed]
    public function topPerformers(): SupportCollection
    {
        return app(CommissionMetricsService::class)->topPerformers(
            $this->branchFilter !== '' ? (int) $this->branchFilter : null,
        );
    }

    /**
     * @return SupportCollection<int, array{label: string, total: float}>
     */
    #[Computed]
    public function bestSellingServices(): SupportCollection
    {
        return app(CommissionReportRepository::class)->bestSellingServices([]);
    }

    /**
     * @return LengthAwarePaginator<int, ProfessionalCommission>
     */
    #[Computed]
    public function commissions(): LengthAwarePaginator
    {
        return app(CommissionRepository::class)->commissions([
            'search' => $this->search,
            'branch_id' => $this->branchFilter !== '' ? (int) $this->branchFilter : null,
            'user_id' => $this->professionalFilter !== '' ? (int) $this->professionalFilter : null,
            'status' => $this->statusFilter,
            'source_type' => $this->sourceFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ], $this->perPage);
    }

    /**
     * @return SupportCollection<int, CommissionRule>
     */
    #[Computed]
    public function rules(): SupportCollection
    {
        return app(CommissionRepository::class)->rules();
    }

    /**
     * @return LengthAwarePaginator<int, CommissionSettlement>
     */
    #[Computed]
    public function settlements(): LengthAwarePaginator
    {
        return app(CommissionRepository::class)->settlements([
            'branch_id' => $this->branchFilter !== '' ? (int) $this->branchFilter : null,
            'status' => $this->statusFilter,
        ], $this->perPage);
    }

    /**
     * @return LengthAwarePaginator<int, CommissionAuditLog>
     */
    #[Computed]
    public function auditLogs(): LengthAwarePaginator
    {
        return app(CommissionRepository::class)->auditLogs([
            'branch_id' => $this->branchFilter !== '' ? (int) $this->branchFilter : null,
        ], $this->perPage);
    }

    #[Computed]
    public function selectedCommission(): ?ProfessionalCommission
    {
        if ($this->selectedCommissionId === null) {
            return null;
        }

        return ProfessionalCommission::query()
            ->with(['branch', 'professional', 'rule', 'type', 'settlement', 'transactions', 'calculations', 'approvals'])
            ->find($this->selectedCommissionId);
    }

    #[Computed]
    public function selectedRule(): ?CommissionRule
    {
        if ($this->selectedRuleId === null) {
            return null;
        }

        return CommissionRule::query()->with(['branch', 'service', 'serviceCategory', 'type', 'formulas'])->find($this->selectedRuleId);
    }

    #[Computed]
    public function selectedSettlement(): ?CommissionSettlement
    {
        if ($this->selectedSettlementId === null) {
            return null;
        }

        return CommissionSettlement::query()->with(['branch', 'approver', 'payments', 'commissions.professional'])->find($this->selectedSettlementId);
    }

    public function render(): View
    {
        return view('livewire.administracion.comisiones.index')->layout('layouts.app');
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
