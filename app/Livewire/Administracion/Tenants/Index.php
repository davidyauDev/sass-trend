<?php

namespace App\Livewire\Administracion\Tenants;

use App\Actions\Tenants\CreateTenantAction;
use App\Actions\Tenants\UpdateTenantStatusAction;
use App\Livewire\Forms\TenantForm;
use App\Models\Tenant;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tenants')]
class Index extends Component
{
    use WithPagination;

    public TenantForm $form;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'plan')]
    public string $planFilter = '';

    #[Url]
    public int $perPage = 10;

    public ?string $tenantIdPendingSuspension = null;

    public function mount(): void
    {
        abort_unless(
            $this->authUser()->isAdministratorGeneral()
            && $this->authUser()->is_active
            && $this->authUser()->tenant_id === null,
            403,
        );

        $this->form->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPlanFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function updatedFormName(): void
    {
        if ($this->form->slug === '') {
            $this->form->normalizeSlug();
        }
    }

    public function updatedFormSlug(): void
    {
        $this->form->normalizeSlug();
    }

    public function openCreateModal(): void
    {
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('create-tenant')->show();
    }

    public function closeCreateModal(): void
    {
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function save(CreateTenantAction $createTenant): void
    {
        $this->form->normalizeSlug();
        $this->form->validate();

        $tenant = $createTenant->handle($this->authUser(), $this->form->payload());

        $this->closeCreateModal();
        $this->modal('create-tenant')->close();

        Flux::toast(
            variant: 'success',
            text: "Tenant {$tenant->name} creado correctamente.",
        );
    }

    public function confirmSuspend(string $tenantId): void
    {
        $this->tenantIdPendingSuspension = $tenantId;

        $this->modal('suspend-tenant')->show();
    }

    public function closeSuspendModal(): void
    {
        $this->tenantIdPendingSuspension = null;
    }

    public function suspend(UpdateTenantStatusAction $updateTenantStatus): void
    {
        $tenant = Tenant::query()->findOrFail($this->tenantIdPendingSuspension);

        $updateTenantStatus->suspend($this->authUser(), $tenant);

        $this->closeSuspendModal();
        $this->modal('suspend-tenant')->close();

        Flux::toast(variant: 'success', text: 'Tenant suspendido correctamente.');
    }

    public function activate(string $tenantId, UpdateTenantStatusAction $updateTenantStatus): void
    {
        $tenant = Tenant::query()->findOrFail($tenantId);

        $updateTenantStatus->activate($this->authUser(), $tenant);

        Flux::toast(variant: 'success', text: 'Tenant activado correctamente.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'planFilter']);
        $this->perPage = 10;
        $this->resetPage();
    }

    #[Computed]
    public function previewBookingUrl(): string
    {
        $slug = $this->form->slug !== '' ? $this->form->slug : 'tenant';

        return route('reservas.index', ['tenant' => $slug], absolute: false);
    }

    #[Computed]
    public function tenantPendingSuspension(): ?Tenant
    {
        if ($this->tenantIdPendingSuspension === null) {
            return null;
        }

        return Tenant::query()->find($this->tenantIdPendingSuspension);
    }

    public function render(): View
    {
        return view('livewire.administracion.tenants.index', [
            'tenants' => $this->tenants(),
            'tenantPendingSuspension' => $this->tenantPendingSuspension(),
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, Tenant> */
    private function tenants(): LengthAwarePaginator
    {
        return Tenant::query()
            ->when($this->search !== '', function (Builder $query): void {
                $term = "%{$this->search}%";

                $query->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery
                        ->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term)
                        ->orWhere('owner_email', 'like', $term);
                });
            })
            ->when($this->statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $this->statusFilter))
            ->when($this->planFilter !== '', fn (Builder $query): Builder => $query->where('plan', $this->planFilter))
            ->latest()
            ->paginate($this->perPage);
    }

    private function authUser(): User
    {
        return auth()->user();
    }
}
