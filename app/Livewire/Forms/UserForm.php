<?php

namespace App\Livewire\Forms;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Livewire\Form;

class UserForm extends Form
{
    public ?int $userId = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public string $phone = '';

    public mixed $photo = null;

    public ?string $existingPhotoPath = null;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $is_active = true;

    public ?int $role_id = null;

    /** @var array<int, int> */
    public array $location_ids = [];

    /** @var array<int, int> */
    public array $permission_ids = [];

    public function resetForm(): void
    {
        $this->userId = null;
        $this->first_name = '';
        $this->last_name = '';
        $this->email = '';
        $this->phone = '';
        $this->photo = null;
        $this->existingPhotoPath = null;
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_active = true;
        $this->role_id = null;
        $this->location_ids = [];
        $this->permission_ids = [];
    }

    public function fillFromUser(User $user): void
    {
        $this->userId = $user->id;
        $this->first_name = $user->first_name ?? $user->displayFirstName();
        $this->last_name = $user->last_name ?? $user->displayLastName();
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->photo = null;
        $this->existingPhotoPath = $user->photo_path;
        $this->password = '';
        $this->password_confirmation = '';
        $this->is_active = $user->is_active;
        $this->role_id = $user->role_id;
        $this->location_ids = $user->locations->pluck('id')->map(fn (mixed $id): int => (int) $id)->values()->all();
        $this->permission_ids = $user->effectivePermissionIds();
    }

    public function withCatalogValidation(): self
    {
        return $this->withValidator(function (Validator $validator): void {
            $validator->after(function (Validator $validator): void {
                $unknownPermissions = collect($this->permission_ids)
                    ->diff(Permission::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all())
                    ->values();

                if ($unknownPermissions->isNotEmpty()) {
                    $validator->errors()->add('permission_ids', 'Se detectaron permisos fuera del catálogo permitido.');
                }
            });
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'first_name' => trim($this->first_name),
            'last_name' => trim($this->last_name),
            'email' => trim($this->email),
            'phone' => $this->normalizeNullableString($this->phone),
            'photo' => $this->photo,
            'password' => $this->normalizeNullableString($this->password),
            'is_active' => $this->is_active,
            'role_id' => $this->role_id,
            'location_ids' => collect($this->location_ids)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'permission_ids' => collect($this->permission_ids)
                ->map(fn (mixed $permissionId): int => (int) $permissionId)
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'password' => [
                $this->userId === null ? 'required' : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'password_confirmation' => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'location_ids' => ['array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::exists('permissions', 'id')],
        ];
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
