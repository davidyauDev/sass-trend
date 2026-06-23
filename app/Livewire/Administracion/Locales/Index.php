<?php

namespace App\Livewire\Administracion\Locales;

use App\Actions\Locations\CreateLocationAction;
use App\Actions\Locations\DeleteLocationAction;
use App\Actions\Locations\UpdateLocationAction;
use App\Livewire\Forms\LocationForm;
use App\Models\Branch;
use App\Models\Location;
use App\Services\Locations\LocationLimitService;
use DateTimeZone;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Locales')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public LocationForm $form;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public int $perPage = 10;

    public ?int $locationIdPendingDeletion = null;

    public bool $isEditing = false;

    public string $modalTab = 'basic';

    public function mount(): void
    {
        $this->form->resetForm();
    }

    public function updatedSearch(): void
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
        $this->isEditing = false;
        $this->modalTab = 'basic';
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-location')->show();
    }

    public function openEditModal(int $locationId): void
    {
        $location = Location::query()
            ->with('schedules')
            ->findOrFail($locationId);

        $this->isEditing = true;
        $this->modalTab = 'basic';
        $this->form->fillFromLocation($location);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-location')->show();
    }

    public function closeModal(): void
    {
        $this->isEditing = false;
        $this->modalTab = 'basic';
        $this->form->resetForm();
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function copyScheduleToAll(): void
    {
        $source = collect($this->form->schedules)
            ->first(fn (array $schedule): bool => (bool) $schedule['is_open'] && $schedule['opens_at'] !== '' && $schedule['closes_at'] !== '');

        if ($source === null) {
            Flux::toast(variant: 'danger', text: 'Configura primero un horario base para poder copiarlo.');

            return;
        }

        $this->form->schedules = collect($this->form->schedules)
            ->map(fn (array $schedule): array => [
                ...$schedule,
                'is_open' => (bool) $source['is_open'],
                'opens_at' => (string) $source['opens_at'],
                'closes_at' => (string) $source['closes_at'],
            ])
            ->all();

        Flux::toast(variant: 'success', text: 'Horario copiado a todos los días.');
    }

    public function save(CreateLocationAction $createLocation, UpdateLocationAction $updateLocation, LocationLimitService $locationLimitService): void
    {
        $isEditing = $this->isEditing;

        if (! $this->isEditing) {
            $locationLimitService->ensureCanCreate(auth()->user());
        }

        $this->form->withScheduleValidation()->validate();

        $payload = $this->form->payload();

        if ($isEditing && $this->form->locationId !== null) {
            $location = Location::findOrFail($this->form->locationId);
            $updateLocation->handle($location, $payload);
        } else {
            $createLocation->handle($payload);
        }

        $this->closeModal();
        $this->modal('upsert-location')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Local actualizado correctamente.' : 'Local creado correctamente.',
        );
    }

    public function confirmDelete(int $locationId): void
    {
        $this->locationIdPendingDeletion = $locationId;

        $this->modal('delete-location')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->locationIdPendingDeletion = null;
    }

    public function delete(DeleteLocationAction $deleteLocation): void
    {
        $location = Location::findOrFail($this->locationIdPendingDeletion);

        $deleteLocation->handle($location);

        $this->closeDeleteModal();
        $this->modal('delete-location')->close();

        Flux::toast(variant: 'success', text: 'Local eliminado correctamente.');

        if ($this->locations()->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search']);
        $this->perPage = 10;
        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function timezoneSuggestions(): array
    {
        return timezone_identifiers_list(DateTimeZone::ALL_WITH_BC);
    }

    /**
     * @return Collection<int, Branch>
     */
    #[Computed]
    public function branchesCatalog(): Collection
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function canCreateLocations(): bool
    {
        return app(LocationLimitService::class)->canCreate(auth()->user());
    }

    #[Computed]
    public function locationPendingDeletion(): ?Location
    {
        if ($this->locationIdPendingDeletion === null) {
            return null;
        }

        return Location::find($this->locationIdPendingDeletion);
    }

    public function render(): View
    {
        return view('livewire.administracion.locales.index', [
            'locations' => $this->locations(),
            'locationPendingDeletion' => $this->locationPendingDeletion(),
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, Location> */
    private function locations(): LengthAwarePaginator
    {
        return Location::query()
            ->with('branch')
            ->search($this->search)
            ->latest()
            ->paginate($this->perPage);
    }
}
