<?php

namespace App\Livewire\Forms;

use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Form;

class TenantForm extends Form
{
    public string $name = '';

    public string $slug = '';

    public string $owner_name = '';

    public string $owner_email = '';

    public string $owner_password = '';

    public string $owner_password_confirmation = '';

    public string $plan = Tenant::PLAN_BASIC;

    public string $status = Tenant::STATUS_ACTIVE;

    public function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->owner_name = '';
        $this->owner_email = '';
        $this->owner_password = '';
        $this->owner_password_confirmation = '';
        $this->plan = Tenant::PLAN_BASIC;
        $this->status = Tenant::STATUS_ACTIVE;
    }

    public function normalizeSlug(): void
    {
        $this->slug = Str::slug($this->slug !== '' ? $this->slug : $this->name);
    }

    /**
     * @return array{name:string,slug:string,owner_name:string,owner_email:string,owner_password:string,plan:string,status:string}
     */
    public function payload(): array
    {
        $this->normalizeSlug();

        return [
            'name' => trim($this->name),
            'slug' => $this->slug,
            'owner_name' => trim($this->owner_name),
            'owner_email' => Str::lower(trim($this->owner_email)),
            'owner_password' => $this->owner_password,
            'plan' => $this->plan,
            'status' => $this->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('tenants', 'slug'),
            ],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
            'plan' => ['required', Rule::in([Tenant::PLAN_BASIC, Tenant::PLAN_PRO, Tenant::PLAN_ENTERPRISE])],
            'status' => ['required', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_PENDING])],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'slug.regex' => 'El subdominio solo puede usar letras minúsculas, números y guiones simples.',
        ];
    }
}
