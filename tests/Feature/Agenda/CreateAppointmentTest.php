<?php

use App\Livewire\Agenda\Index as AgendaIndex;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Agenda\AppointmentStatusCatalog;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('guarda una cita corta para un cliente sin cita previa', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $branch = Branch::factory()->create(['is_active' => true]);
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Depilación de cera - Boso',
        'duration_minutes' => 10,
        'price' => 16,
        'is_active' => true,
    ]);

    Livewire::test(AgendaIndex::class)
        ->set('selectedServiceIds', [$service->id])
        ->set('selectedServiceProfessionals', [$service->id => null])
        ->set('form.branch_id', $branch->id)
        ->set('form.service_id', $service->id)
        ->set('form.title', $service->name)
        ->set('form.starts_at', '2026-07-15T13:15')
        ->set('form.ends_at', '2026-07-15T13:30')
        ->set('form.duration_minutes', '10')
        ->set('form.price', '16')
        ->set('form.currency', 'PEN')
        ->set('form.status_slug', AppointmentStatusCatalog::PENDING)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('appointmentPanelOpen', false)
        ->assertSee('Reservada')
        ->assertSee('Checkout');

    $appointment = Appointment::query()->with(['client', 'status'])->sole();

    expect($appointment->duration_minutes)->toBe(10)
        ->and($appointment->starts_at->format('Y-m-d H:i'))->toBe('2026-07-15 13:15')
        ->and($appointment->client->fullName())->toBe('Cliente sin cita previa')
        ->and($appointment->status->slug)->toBe(AppointmentStatusCatalog::PENDING);
});

test('muestra y navega la agenda de tres dias', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $component = Livewire::test(AgendaIndex::class)
        ->set('selectedDate', '2026-07-15')
        ->set('viewMode', 'three_days')
        ->assertSet('viewMode', 'three_days')
        ->assertSee('3 días');

    expect($component->get('rangeDays'))->toHaveCount(3)
        ->and($component->get('periodLabel'))->toBe('Del 15 al 17 de julio de 2026');

    $component->call('next')->assertSet('selectedDate', '2026-07-18');
    $component->call('previous')->assertSet('selectedDate', '2026-07-15');
});

test('precarga una cita desde un intervalo de quince minutos', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    Livewire::test(AgendaIndex::class)
        ->call('openCreateModalForSlot', '2026-07-15T13:15', $user->id)
        ->assertSet('appointmentPanelOpen', true)
        ->assertSet('selectedDate', '2026-07-15')
        ->assertSet('form.professional_id', $user->id)
        ->assertSet('form.starts_at', '2026-07-15T13:15')
        ->assertSet('form.ends_at', '2026-07-15T14:15')
        ->assertSet('selectedSlotStart', '2026-07-15T13:15')
        ->assertSet('selectedSlotEnd', '2026-07-15T14:15');
});
