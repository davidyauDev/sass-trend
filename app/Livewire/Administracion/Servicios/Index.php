<?php

namespace App\Livewire\Administracion\Servicios;

use App\Actions\Services\CreateServiceAction;
use App\Actions\Services\DeleteServiceAction;
use App\Actions\Services\ToggleServiceStatusAction;
use App\Actions\Services\UpdateServiceAction;
use App\Livewire\Forms\ServiceForm;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Services\ServicePaymentTypeCatalog;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Servicios')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ServiceForm $form;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'category')]
    public string $categoryFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url]
    public int $perPage = 10;

    public bool $isEditing = false;

    public ?int $serviceIdPendingDeletion = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('viewAny', Service::class) === true, 403);

        $this->form->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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

    public function openCreateModal(): void
    {
        $this->authorize('create', Service::class);

        $this->form->resetForm();
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-service')->show();
    }

    public function openEditModal(int $serviceId): void
    {
        $service = Service::query()
            ->with(['category', 'professionals.locations', 'schedules'])
            ->findOrFail($serviceId);

        $this->authorize('update', $service);

        $this->form->fillFromService($service);
        $this->isEditing = true;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-service')->show();
    }

    public function closeModal(): void
    {
        $this->form->resetForm();
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function selectAllProfessionals(): void
    {
        $this->form->professional_ids = $this->professionalsCatalog()
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function save(CreateServiceAction $createService, UpdateServiceAction $updateService): void
    {
        $this->form->withBusinessValidation()->validate();

        $payload = $this->form->payload();
        $isEditing = $this->isEditing;

        if ($isEditing && $this->form->serviceId !== null) {
            $service = Service::query()->findOrFail($this->form->serviceId);
            $updateService->handle($this->authUser(), $service, $payload);
        } else {
            $createService->handle($this->authUser(), $payload);
        }

        $this->closeModal();
        $this->modal('upsert-service')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Servicio actualizado correctamente.' : 'Servicio creado correctamente.',
        );
    }

    public function confirmDelete(int $serviceId): void
    {
        $this->serviceIdPendingDeletion = $serviceId;

        $this->modal('delete-service')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->serviceIdPendingDeletion = null;
    }

    public function delete(DeleteServiceAction $deleteService): void
    {
        if ($this->serviceIdPendingDeletion === null) {
            return;
        }

        $service = Service::query()->findOrFail($this->serviceIdPendingDeletion);

        $this->authorize('delete', $service);
        $result = $deleteService->handle($this->authUser(), $service);

        $this->closeDeleteModal();
        $this->modal('delete-service')->close();

        Flux::toast(
            variant: 'success',
            text: $result === 'deleted'
                ? 'Servicio eliminado correctamente.'
                : 'El servicio tenía reservas asociadas y fue desactivado.',
        );

        if ($this->services()->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
        }
    }

    public function toggleStatus(int $serviceId, ToggleServiceStatusAction $toggleServiceStatus): void
    {
        $service = Service::query()->findOrFail($serviceId);
        $this->authorize('update', $service);
        $toggleServiceStatus->handle($this->authUser(), $service);

        Flux::toast(
            variant: 'success',
            text: $service->is_active ? 'Servicio activado correctamente.' : 'Servicio desactivado correctamente.',
        );
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'statusFilter']);
        $this->perPage = 10;
        $this->resetPage();
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return ServiceCategory::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function professionalsCatalog(): Collection
    {
        return User::query()
            ->with('locations')
            ->where('is_active', true)
            ->when(
                $this->form->professional_location_filter_id !== null,
                fn (Builder $query): Builder => $query->whereHas(
                    'locations',
                    fn (Builder $locationQuery): Builder => $locationQuery->whereKey($this->form->professional_location_filter_id),
                ),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Location>
     */
    #[Computed]
    public function locationsCatalog(): Collection
    {
        return Location::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentTypeOptions(): array
    {
        return ServicePaymentTypeCatalog::options();
    }

    #[Computed]
    public function servicePendingDeletion(): ?Service
    {
        if ($this->serviceIdPendingDeletion === null) {
            return null;
        }

        return Service::query()->find($this->serviceIdPendingDeletion);
    }

    public function render(): View
    {
        return view('livewire.administracion.servicios.index', [
            'services' => $this->services(),
            'servicePendingDeletion' => $this->servicePendingDeletion(),
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, Service> */
    private function services(): LengthAwarePaginator
    {
        return Service::query()
            ->with(['category', 'professionals'])
            ->search($this->search)
            ->when($this->categoryFilter !== '', fn (Builder $query): Builder => $query->where('service_category_id', (int) $this->categoryFilter))
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query): Builder => $query->where('is_active', $this->statusFilter === 'active'),
            )
            ->latest()
            ->paginate($this->perPage);
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
