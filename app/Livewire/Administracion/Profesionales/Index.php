<?php

namespace App\Livewire\Administracion\Profesionales;

use App\Actions\Professionals\CreateProfessionalAction;
use App\Actions\Professionals\CreateProfessionalGroupAction;
use App\Actions\Professionals\DeleteProfessionalAction;
use App\Actions\Professionals\DeleteProfessionalGroupAction;
use App\Actions\Professionals\ToggleProfessionalStatusAction;
use App\Actions\Professionals\UpdateProfessionalAction;
use App\Actions\Professionals\UpdateProfessionalGroupAction;
use App\Livewire\Forms\ProfessionalForm;
use App\Livewire\Forms\ProfessionalGroupForm;
use App\Models\Location;
use App\Models\Professional;
use App\Models\ProfessionalGroup;
use App\Models\ServiceCategory;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Profesionales')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ProfessionalForm $form;

    public ProfessionalGroupForm $groupForm;

    #[Url(as: 'tab')]
    public string $sectionTab = 'professionals';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'group_q')]
    public string $groupSearch = '';

    #[Url(as: 'location')]
    public string $locationFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url]
    public int $perPage = 10;

    public string $professionalModalTab = 'basic';

    public bool $isEditing = false;

    public bool $isGroupEditing = false;

    public bool $showFilters = false;

    public ?int $professionalIdPendingDeletion = null;

    public ?int $groupIdPendingDeletion = null;

    public ?int $schedulePreviewProfessionalId = null;

    public ?int $selectedProfessionalCardId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdministrator() === true, 403);

        $this->form->resetForm();
        $this->groupForm->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedGroupSearch(): void
    {
        $this->resetPage(pageName: 'groups-page');
    }

    public function updatedLocationFilter(): void
    {
        $this->resetPage();
        $this->resetPage(pageName: 'groups-page');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->resetPage(pageName: 'groups-page');
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 25, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
        $this->resetPage(pageName: 'groups-page');
    }

    public function updatedGroupFormLocationId(): void
    {
        $this->groupForm->member_ids = $this->eligibleProfessionalsForGroup()
            ->pluck('id')
            ->intersect($this->groupForm->member_ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    public function switchSection(string $tab): void
    {
        if (! in_array($tab, ['professionals', 'groups'], true)) {
            return;
        }

        $this->sectionTab = $tab;
    }

    public function selectProfessional(int $professionalId): void
    {
        $this->selectedProfessionalCardId = $professionalId;
    }

    public function openCreateModal(): void
    {
        $this->form->resetForm();
        $this->professionalModalTab = 'basic';
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-professional')->show();
    }

    public function openEditModal(int $professionalId): void
    {
        $professional = Professional::query()
            ->with(['user', 'services.category', 'schedules.breaks', 'locations'])
            ->findOrFail($professionalId);

        $this->form->fillFromProfessional($professional);
        $this->professionalModalTab = 'basic';
        $this->isEditing = true;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-professional')->show();
    }

    public function closeModal(): void
    {
        $this->form->resetForm();
        $this->professionalModalTab = 'basic';
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function setProfessionalModalTab(string $tab): void
    {
        if (! in_array($tab, ['basic', 'schedule', 'profile'], true)) {
            return;
        }

        $this->professionalModalTab = $tab;
    }

    public function addBreak(int $index): void
    {
        $breaks = $this->form->schedules[$index]['breaks'] ?? [];
        $breaks[] = ['starts_at' => '', 'ends_at' => ''];
        $this->form->schedules[$index]['breaks'] = $breaks;
    }

    public function removeBreak(int $scheduleIndex, int $breakIndex): void
    {
        $breaks = $this->form->schedules[$scheduleIndex]['breaks'] ?? [];
        unset($breaks[$breakIndex]);

        $this->form->schedules[$scheduleIndex]['breaks'] = array_values($breaks);
    }

    public function copyScheduleToAll(int $index): void
    {
        $source = $this->form->schedules[$index];

        $this->form->schedules = collect($this->form->schedules)
            ->map(fn (array $schedule): array => [
                ...$schedule,
                'is_working' => $source['is_working'],
                'starts_at' => $source['starts_at'],
                'ends_at' => $source['ends_at'],
                'breaks' => collect((array) ($source['breaks'] ?? []))
                    ->map(fn (array $break): array => [
                        'starts_at' => $break['starts_at'],
                        'ends_at' => $break['ends_at'],
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    public function selectAllServices(): void
    {
        $this->form->service_ids = $this->serviceCategories()
            ->flatMap(fn (ServiceCategory $category) => $category->services->pluck('id'))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function save(CreateProfessionalAction $createProfessional, UpdateProfessionalAction $updateProfessional): void
    {
        $this->form->validate();
        $payload = $this->validatedProfessionalPayload();
        $isEditing = $this->isEditing;

        if ($isEditing && $this->form->professionalId !== null) {
            $professional = Professional::query()->findOrFail($this->form->professionalId);
            $updateProfessional->handle($this->authUser(), $professional, $payload);
        } else {
            $createProfessional->handle($this->authUser(), $payload);
        }

        $this->closeModal();
        $this->modal('upsert-professional')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Profesional actualizado correctamente.' : 'Profesional creado correctamente.',
        );
    }

    public function confirmDelete(int $professionalId): void
    {
        $this->professionalIdPendingDeletion = $professionalId;
        $this->modal('delete-professional')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->professionalIdPendingDeletion = null;
    }

    public function delete(DeleteProfessionalAction $deleteProfessional): void
    {
        if ($this->professionalIdPendingDeletion === null) {
            return;
        }

        $professional = Professional::query()->findOrFail($this->professionalIdPendingDeletion);
        $deleteProfessional->handle($this->authUser(), $professional);

        $this->closeDeleteModal();
        $this->modal('delete-professional')->close();

        Flux::toast(variant: 'success', text: 'Profesional desactivado correctamente.');
    }

    public function toggleStatus(int $professionalId, ToggleProfessionalStatusAction $toggleProfessionalStatus): void
    {
        $professional = Professional::query()->findOrFail($professionalId);
        $professional = $toggleProfessionalStatus->handle($this->authUser(), $professional);

        Flux::toast(
            variant: 'success',
            text: $professional->is_active ? 'Profesional activado correctamente.' : 'Profesional desactivado correctamente.',
        );
    }

    public function openSchedulePreview(int $professionalId): void
    {
        $this->schedulePreviewProfessionalId = $professionalId;
        $this->modal('professional-schedule-preview')->show();
    }

    public function closeSchedulePreview(): void
    {
        $this->schedulePreviewProfessionalId = null;
    }

    public function openCreateGroupModal(): void
    {
        $this->groupForm->resetForm();
        $this->isGroupEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-group')->show();
    }

    public function openEditGroupModal(int $groupId): void
    {
        $group = ProfessionalGroup::query()
            ->with(['location', 'professionals'])
            ->findOrFail($groupId);

        $this->groupForm->fillFromGroup($group);
        $this->isGroupEditing = true;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-group')->show();
    }

    public function closeGroupModal(): void
    {
        $this->groupForm->resetForm();
        $this->isGroupEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function saveGroup(CreateProfessionalGroupAction $createGroup, UpdateProfessionalGroupAction $updateGroup): void
    {
        $this->groupForm->validate();
        $payload = $this->validatedGroupPayload();
        $isEditing = $this->isGroupEditing;

        if ($isEditing && $this->groupForm->groupId !== null) {
            $group = ProfessionalGroup::query()->findOrFail($this->groupForm->groupId);
            $updateGroup->handle($this->authUser(), $group, $payload);
        } else {
            $createGroup->handle($this->authUser(), $payload);
        }

        $this->closeGroupModal();
        $this->modal('upsert-group')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Grupo actualizado correctamente.' : 'Grupo creado correctamente.',
        );
    }

    public function confirmGroupDelete(int $groupId): void
    {
        $this->groupIdPendingDeletion = $groupId;
        $this->modal('delete-group')->show();
    }

    public function closeGroupDeleteModal(): void
    {
        $this->groupIdPendingDeletion = null;
    }

    public function deleteGroup(DeleteProfessionalGroupAction $deleteGroup): void
    {
        if ($this->groupIdPendingDeletion === null) {
            return;
        }

        $group = ProfessionalGroup::query()->findOrFail($this->groupIdPendingDeletion);
        $deleteGroup->handle($this->authUser(), $group);

        $this->closeGroupDeleteModal();
        $this->modal('delete-group')->close();

        Flux::toast(variant: 'success', text: 'Grupo eliminado correctamente.');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'groupSearch', 'locationFilter', 'statusFilter']);
        $this->perPage = 10;
        $this->resetPage();
        $this->resetPage(pageName: 'groups-page');
    }

    /**
     * @return Collection<int, ServiceCategory>
     */
    #[Computed]
    public function serviceCategories(): Collection
    {
        return ServiceCategory::query()
            ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
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
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, Professional>
     */
    #[Computed]
    public function eligibleProfessionalsForGroup(): Collection
    {
        if ($this->groupForm->location_id === null) {
            return new Collection;
        }

        return Professional::query()
            ->with('locations')
            ->where('is_active', true)
            ->whereHas('locations', fn (Builder $query): Builder => $query->whereKey($this->groupForm->location_id))
            ->orderBy('public_name')
            ->get();
    }

    #[Computed]
    public function professionalPendingDeletion(): ?Professional
    {
        if ($this->professionalIdPendingDeletion === null) {
            return null;
        }

        return Professional::query()->find($this->professionalIdPendingDeletion);
    }

    #[Computed]
    public function groupPendingDeletion(): ?ProfessionalGroup
    {
        if ($this->groupIdPendingDeletion === null) {
            return null;
        }

        return ProfessionalGroup::query()->with('location')->find($this->groupIdPendingDeletion);
    }

    #[Computed]
    public function schedulePreviewProfessional(): ?Professional
    {
        if ($this->schedulePreviewProfessionalId === null) {
            return null;
        }

        return Professional::query()
            ->with(['schedules.breaks', 'locations'])
            ->find($this->schedulePreviewProfessionalId);
    }

    public function render(): View
    {
        $professionals = $this->professionals();
        $currentProfessional = $professionals->getCollection()->firstWhere('id', $this->selectedProfessionalCardId)
            ?? $professionals->getCollection()->first();

        return view('livewire.administracion.profesionales.index', [
            'professionals' => $professionals,
            'currentProfessional' => $currentProfessional,
            'groups' => $this->groups(),
            'professionalPendingDeletion' => $this->professionalPendingDeletion(),
            'groupPendingDeletion' => $this->groupPendingDeletion(),
            'schedulePreviewProfessional' => $this->schedulePreviewProfessional(),
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, Professional> */
    private function professionals(): LengthAwarePaginator
    {
        return Professional::query()
            ->with(['services', 'groups', 'locations', 'user'])
            ->search($this->search)
            ->when(
                $this->locationFilter !== '',
                fn (Builder $query): Builder => $query->whereHas('locations', fn (Builder $locationQuery): Builder => $locationQuery->whereKey((int) $this->locationFilter)),
            )
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query): Builder => $query->where('is_active', $this->statusFilter === 'active'),
            )
            ->latest()
            ->paginate($this->perPage);
    }

    /** @return LengthAwarePaginator<int, ProfessionalGroup> */
    private function groups(): LengthAwarePaginator
    {
        return ProfessionalGroup::query()
            ->with(['location', 'professionals'])
            ->search($this->groupSearch)
            ->when(
                $this->locationFilter !== '',
                fn (Builder $query): Builder => $query->where('location_id', (int) $this->locationFilter),
            )
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query): Builder => $query->where('is_active', $this->statusFilter === 'active'),
            )
            ->latest()
            ->paginate($this->perPage, ['*'], 'groups-page');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedProfessionalPayload(): array
    {
        $payload = $this->form->payload();

        if ($payload['has_system_access'] && $payload['email'] === null) {
            throw ValidationException::withMessages([
                'form.email' => 'El email es obligatorio cuando el profesional tendrá acceso al sistema.',
            ]);
        }

        $linkedUserId = null;

        if ($this->form->professionalId !== null) {
            $linkedUserId = Professional::query()->whereKey($this->form->professionalId)->value('user_id');
        }

        if ($payload['email'] !== null) {
            $email = $payload['email'];
            $professionalQuery = Professional::query()->where('email', $email);

            if ($this->form->professionalId !== null) {
                $professionalQuery->whereKeyNot($this->form->professionalId);
            }

            if ($professionalQuery->exists()) {
                throw ValidationException::withMessages([
                    'form.email' => 'Ya existe otro profesional con este correo.',
                ]);
            }

            $userQuery = User::query()->where('email', $email);

            if ($linkedUserId !== null) {
                $userQuery->whereKeyNot($linkedUserId);
            }

            if ($userQuery->exists()) {
                throw ValidationException::withMessages([
                    'form.email' => 'Ya existe otro usuario con este correo.',
                ]);
            }
        }

        foreach ($payload['schedules'] as $scheduleIndex => $schedule) {
            if (! $schedule['is_working']) {
                continue;
            }

            if ($schedule['starts_at'] === null) {
                throw ValidationException::withMessages([
                    "form.schedules.{$scheduleIndex}.starts_at" => 'La hora de inicio es obligatoria cuando el día está activo.',
                ]);
            }

            if ($schedule['ends_at'] === null) {
                throw ValidationException::withMessages([
                    "form.schedules.{$scheduleIndex}.ends_at" => 'La hora de fin es obligatoria cuando el día está activo.',
                ]);
            }

            $scheduleStartsAt = $schedule['starts_at'];
            $scheduleEndsAt = $schedule['ends_at'];

            if ($scheduleEndsAt <= $scheduleStartsAt) {
                throw ValidationException::withMessages([
                    "form.schedules.{$scheduleIndex}.ends_at" => 'La hora de fin debe ser posterior a la hora de inicio.',
                ]);
            }

            foreach ($schedule['breaks'] as $breakIndex => $break) {
                if ($break['starts_at'] === null && $break['ends_at'] === null) {
                    continue;
                }

                if ($break['starts_at'] === null || $break['ends_at'] === null) {
                    throw ValidationException::withMessages([
                        "form.schedules.{$scheduleIndex}.breaks.{$breakIndex}.starts_at" => 'Debes completar inicio y fin del descanso.',
                    ]);
                }

                $breakStartsAt = $break['starts_at'];
                $breakEndsAt = $break['ends_at'];

                if ($breakEndsAt <= $breakStartsAt) {
                    throw ValidationException::withMessages([
                        "form.schedules.{$scheduleIndex}.breaks.{$breakIndex}.ends_at" => 'El descanso debe terminar después de iniciar.',
                    ]);
                }

                if ($breakStartsAt < $scheduleStartsAt || $breakEndsAt > $scheduleEndsAt) {
                    throw ValidationException::withMessages([
                        "form.schedules.{$scheduleIndex}.breaks.{$breakIndex}.starts_at" => 'El descanso debe estar dentro del horario laboral del día.',
                    ]);
                }
            }
        }

        return $payload;
    }

    /**
     * @return array{name:string,location_id:int,is_active:bool,member_ids:list<int>}
     */
    private function validatedGroupPayload(): array
    {
        $payload = $this->groupForm->payload();

        $eligibleIds = Professional::query()
            ->whereHas('locations', fn (Builder $query): Builder => $query->whereKey($payload['location_id']))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        foreach ($payload['member_ids'] as $memberId) {
            if (! in_array($memberId, $eligibleIds, true)) {
                throw ValidationException::withMessages([
                    'groupForm.member_ids' => 'Solo puedes asignar profesionales del local seleccionado.',
                ]);
            }
        }

        return $payload;
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
