<?php

use App\Livewire\Administracion\Tenants\Index;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('tenancy.central_domains', ['localhost']);
});

test('general administrators can create tenants from the central panel', function () {
    $admin = User::factory()->administratorGeneral()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openCreateModal')
        ->set('form.name', 'Trend Belleza Miraflores')
        ->set('form.slug', 'trend-miraflores')
        ->set('form.owner_name', 'Maria Dueña')
        ->set('form.owner_email', 'owner@example.com')
        ->set('form.owner_password', 'password-secret')
        ->set('form.owner_password_confirmation', 'password-secret')
        ->set('form.plan', Tenant::PLAN_PRO)
        ->set('form.status', Tenant::STATUS_ACTIVE)
        ->call('save')
        ->assertHasNoErrors();

    $tenant = Tenant::query()->where('slug', 'trend-miraflores')->firstOrFail();

    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE)
        ->and(User::withoutTenancy()->where('email', 'owner@example.com')->exists())->toBeTrue()
        ->and(User::withoutTenancy()->where('email', 'owner@example.com')->value('tenant_id'))->toBe($tenant->id);
});

test('tenant slug must be unique', function () {
    $admin = User::factory()->administratorGeneral()->create();

    Tenant::query()->create([
        'name' => 'Tenant existente',
        'slug' => 'tenant-existente',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@example.com',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('form.name', 'Tenant Existente')
        ->set('form.slug', 'tenant-existente')
        ->set('form.owner_name', 'Otro Owner')
        ->set('form.owner_email', 'other@example.com')
        ->set('form.owner_password', 'password-secret')
        ->set('form.owner_password_confirmation', 'password-secret')
        ->call('save')
        ->assertHasErrors(['form.slug']);
});

test('general administrators can suspend tenants without deleting tenant data', function () {
    $admin = User::factory()->administratorGeneral()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->set('form.name', 'Tenant Bloqueado')
        ->set('form.slug', 'tenant-bloqueado')
        ->set('form.owner_name', 'Bloqueado Owner')
        ->set('form.owner_email', 'blocked@example.com')
        ->set('form.owner_password', 'password-secret')
        ->set('form.owner_password_confirmation', 'password-secret')
        ->call('save')
        ->assertHasNoErrors();

    $tenant = Tenant::query()->where('slug', 'tenant-bloqueado')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('confirmSuspend', $tenant->id)
        ->call('suspend')
        ->assertHasNoErrors();

    $tenant->refresh();

    expect($tenant->status)->toBe(Tenant::STATUS_SUSPENDED)
        ->and($tenant->suspended_at)->not->toBeNull()
        ->and(User::withoutTenancy()->where('email', 'blocked@example.com')->exists())->toBeTrue();
});

test('tenant users cannot access the central tenant panel', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Tenant One',
        'slug' => 'tenant-one',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@tenant.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantUser = User::factory()->administratorGeneral()->create([
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($tenantUser)
        ->get(route('administracion.tenants.index'))
        ->assertForbidden();
});
