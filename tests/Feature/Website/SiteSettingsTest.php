<?php

use App\Livewire\Administracion\SitioWeb\Settings;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('administrador puede actualizar la configuracion del sitio web', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Belleza',
        'slug' => 'trend-belleza',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);

    $this->actingAs(User::factory()->administratorGeneral()->create([
        'tenant_id' => $tenant->id,
    ]));

    $settings = WebsiteSetting::current();

    Livewire::test(Settings::class)
        ->set('form.site_name', 'SASS Trend Booking')
        ->set('form.tagline', 'Reserva premium')
        ->set('form.primary_color', '#1f6f5f')
        ->set('form.booking_button_label', 'Agendar cita')
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($settings->refresh())
        ->site_name->toBe('SASS Trend Booking')
        ->tagline->toBe('Reserva premium')
        ->primary_color->toBe('#1f6f5f')
        ->booking_button_label->toBe('Agendar cita')
        ->is_active->toBeTrue();

    tenancy()->end();
});
