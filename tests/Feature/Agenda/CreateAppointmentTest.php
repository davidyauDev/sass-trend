<?php

use App\Livewire\Agenda\Index as AgendaIndex;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
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

    AppointmentStatus::query()->create([
        'name' => 'Pendiente',
        'slug' => AppointmentStatusCatalog::PENDING,
        'color' => '#f59e0b',
        'sort_order' => 1,
        'is_terminal' => false,
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
        ->assertSet('appointmentPanelOpen', false);

    $appointment = Appointment::query()->with('client')->sole();

    expect($appointment->duration_minutes)->toBe(10)
        ->and($appointment->starts_at->format('Y-m-d H:i'))->toBe('2026-07-15 13:15')
        ->and($appointment->client->fullName())->toBe('Cliente sin cita previa');
});
