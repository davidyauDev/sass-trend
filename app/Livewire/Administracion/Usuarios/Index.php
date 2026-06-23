<?php

namespace App\Livewire\Administracion\Usuarios;

use App\Actions\Users\CreateUserAction;
use App\Actions\Users\DeleteUserAction;
use App\Actions\Users\ToggleUserStatusAction;
use App\Actions\Users\UpdateUserAction;
use App\Livewire\Forms\UserForm;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Users\UserRoleCatalog;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Usuarios')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public UserForm $form;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'role')]
    public string $roleFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url]
    public int $perPage = 10;

    public bool $isEditing = false;

    public ?int $userIdPendingDeletion = null;

    public function mount(): void
    {
        abort_unless($this->authUser()->isAdministratorGeneral() && $this->authUser()->is_active, 403);

        $this->form->resetForm();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
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
        $this->form->resetForm();
        $this->form->role_id = Role::query()->where('slug', UserRoleCatalog::LOCATION_ADMIN)->value('id');
        $this->applyRolePermissionsTemplate();
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-user')->show();
    }

    public function openEditModal(int $userId): void
    {
        $user = User::query()
            ->with(['locations', 'permissions.permission', 'role.permissions'])
            ->findOrFail($userId);

        $this->isEditing = true;
        $this->form->fillFromUser($user);
        $this->resetValidation();
        $this->resetErrorBag();

        $this->modal('upsert-user')->show();
    }

    public function closeModal(): void
    {
        $this->form->resetForm();
        $this->isEditing = false;
        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function applyRolePermissionsTemplate(): void
    {
        if ($this->form->role_id === null) {
            return;
        }

        $this->form->permission_ids = Role::query()
            ->with('permissions')
            ->find($this->form->role_id)
            ?->permissions
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all() ?? [];
    }

    public function save(CreateUserAction $createUser, UpdateUserAction $updateUser): void
    {
        $this->form->withCatalogValidation()->validate();

        $payload = $this->form->payload();
        $actor = $this->authUser();
        $isEditing = $this->isEditing;

        if ($isEditing && $this->form->userId !== null) {
            $user = User::findOrFail($this->form->userId);
            $updateUser->handle($actor, $user, $payload);
        } else {
            $createUser->handle($actor, $payload);
        }

        $this->closeModal();
        $this->modal('upsert-user')->close();

        Flux::toast(
            variant: 'success',
            text: $isEditing ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.',
        );
    }

    public function confirmDelete(int $userId): void
    {
        $this->userIdPendingDeletion = $userId;

        $this->modal('delete-user')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->userIdPendingDeletion = null;
        $this->resetErrorBag('deletion');
    }

    public function delete(DeleteUserAction $deleteUser): void
    {
        $user = User::findOrFail($this->userIdPendingDeletion);

        $deleteUser->handle($this->authUser(), $user);

        $this->closeDeleteModal();
        $this->modal('delete-user')->close();

        Flux::toast(variant: 'success', text: 'Usuario eliminado correctamente.');

        if ($this->users()->isEmpty() && $this->getPage() > 1) {
            $this->previousPage();
        }
    }

    public function toggleStatus(int $userId, ToggleUserStatusAction $toggleUserStatus): void
    {
        $user = User::findOrFail($userId);
        $toggleUserStatus->handle($this->authUser(), $user);

        Flux::toast(
            variant: 'success',
            text: $user->is_active ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.',
        );
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->perPage = 10;
        $this->resetPage();
    }

    /**
     * @return Collection<int, Role>
     */
    #[Computed]
    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return SupportCollection<string, Collection<int, Permission>>
     */
    #[Computed]
    public function permissionGroups(): SupportCollection
    {
        return Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');
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

    #[Computed]
    public function userPendingDeletion(): ?User
    {
        if ($this->userIdPendingDeletion === null) {
            return null;
        }

        return User::find($this->userIdPendingDeletion);
    }

    public function render(): View
    {
        return view('livewire.administracion.usuarios.index', [
            'users' => $this->users(),
            'userPendingDeletion' => $this->userPendingDeletion(),
        ])->layout('layouts.app');
    }

    /** @return LengthAwarePaginator<int, User> */
    private function users(): LengthAwarePaginator
    {
        return User::query()
            ->with(['locations', 'role'])
            ->when($this->search !== '', function (Builder $query): void {
                $term = "%{$this->search}%";

                $query->where(function (Builder $searchQuery) use ($term): void {
                    $searchQuery
                        ->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->roleFilter !== '', fn (Builder $query): Builder => $query->where('role_id', (int) $this->roleFilter))
            ->when(
                $this->statusFilter !== '',
                fn (Builder $query): Builder => $query->where('is_active', $this->statusFilter === 'active'),
            )
            ->latest()
            ->paginate($this->perPage);
    }

    private function authUser(): User
    {
        return auth()->user();
    }
}
