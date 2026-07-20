<?php

use App\Livewire\Agenda\Index as AgendaIndex;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Agenda\AppointmentStatusCatalog;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    $component = Livewire::test(AgendaIndex::class)
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

    $component
        ->set('noteDraft', 'Cliente solicita atención especial.')
        ->call('addNote')
        ->assertHasNoErrors()
        ->assertSet('noteDraft', '')
        ->assertSee('Cliente solicita atención especial.');

    expect(AppointmentNote::query()->sole()->note)->toBe('Cliente solicita atención especial.');

    $component
        ->call('openCancellationConfirmation')
        ->assertSet('cancellationPanelOpen', true)
        ->assertSee('¿Seguro que quieres cancelar?')
        ->assertSee('Cancelar cita')
        ->call('closeCancellationConfirmation')
        ->assertSet('cancellationPanelOpen', false);

    expect($appointment->fresh()->status->slug)->toBe(AppointmentStatusCatalog::PENDING);

    $component
        ->call('openCancellationConfirmation')
        ->set('cancellationReason', 'duplicate')
        ->call('confirmCancellation')
        ->assertSet('cancellationPanelOpen', false)
        ->assertSet('selectedAppointmentId', null);

    $appointment = $appointment->fresh(['status']);

    expect($appointment->status->slug)->toBe(AppointmentStatusCatalog::CANCELLED)
        ->and($appointment->cancellation_reason)->toBe('Cita duplicada.')
        ->and($component->get('appointments'))->toHaveCount(0);
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

    $component
        ->call('openCreateModalForDateAndProfessional', '2026-07-16', $user->id)
        ->assertSet('appointmentPanelOpen', true)
        ->assertSet('selectedDate', '2026-07-16')
        ->assertSet('form.professional_id', $user->id)
        ->call('closeModal')
        ->call('openDayView', '2026-07-17')
        ->assertSet('viewMode', 'day')
        ->assertSet('selectedDate', '2026-07-17');
});

test('precarga los servicios y conserva el panel listo entre aperturas', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $category = ServiceCategory::factory()->create(['name' => 'Cabello']);
    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Corte clásico',
        'is_active' => true,
    ]);
    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Servicio oculto',
        'is_active' => false,
    ]);

    Livewire::test(AgendaIndex::class)
        ->call('preloadAppointmentPanel')
        ->assertSet('appointmentPanelLoaded', true)
        ->assertSet('appointmentPanelOpen', false)
        ->assertSee('Corte clásico')
        ->assertDontSee('Servicio oculto')
        ->call('openCreateModal')
        ->assertSet('appointmentPanelOpen', true)
        ->call('closeModal')
        ->assertSet('appointmentPanelLoaded', true)
        ->assertSet('appointmentPanelOpen', false)
        ->call('openCreateModal')
        ->assertSee('Corte clásico');
});

test('selecciona un servicio sin consultar el calendario por cada dia del mes', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
    ]);
    $component = Livewire::test(AgendaIndex::class)
        ->call('preloadAppointmentPanel')
        ->call('openCreateModal');
    $calendarQueries = [
        'appointments' => 0,
        'schedule_blocks' => 0,
    ];

    DB::listen(function (QueryExecuted $query) use (&$calendarQueries): void {
        foreach (array_keys($calendarQueries) as $table) {
            if (preg_match('/from ["`]?'.preg_quote($table, '/').'["`]?/i', $query->sql) === 1) {
                $calendarQueries[$table]++;
            }
        }
    });

    $component
        ->call('selectAppointmentService', $service->id)
        ->assertSet('appointmentStep', 'services');

    expect($calendarQueries)
        ->appointments->toBe(1)
        ->schedule_blocks->toBe(1);
});

test('carga el catalogo de checkout solo cuando se solicita', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $branch = Branch::factory()->create(['is_active' => true]);
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
    ]);
    $product = Product::factory()->create(['name' => 'Shampoo profesional']);
    $component = Livewire::test(AgendaIndex::class)
        ->set('selectedServiceIds', [$service->id])
        ->set('selectedServiceProfessionals', [$service->id => null])
        ->set('form.branch_id', $branch->id)
        ->set('form.service_id', $service->id)
        ->set('form.title', $service->name)
        ->set('form.starts_at', '2026-07-15T13:15')
        ->set('form.ends_at', '2026-07-15T14:15')
        ->set('form.duration_minutes', '60')
        ->set('form.price', '100')
        ->set('form.currency', 'PEN')
        ->set('form.status_slug', AppointmentStatusCatalog::PENDING)
        ->call('save')
        ->call('closeDrawer');
    $appointment = Appointment::query()->sole();
    $productQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$productQueries): void {
        if (preg_match('/from ["`]?products["`]?/i', $query->sql) === 1) {
            $productQueries++;
        }
    });

    $component
        ->call('openDrawer', $appointment->id)
        ->assertSet('checkoutCatalogLoaded', false)
        ->assertDontSee($product->name);

    expect($productQueries)->toBe(0);

    $component
        ->call('loadCheckoutCatalog')
        ->assertSet('checkoutCatalogLoaded', true)
        ->assertSee($product->name)
        ->call('loadCheckoutCatalog');

    expect($productQueries)->toBe(1);
});

test('el polling no renderiza la agenda mientras hay un panel abierto', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $component = Livewire::test(AgendaIndex::class)->call('openCreateModal');
    $calendarQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$calendarQueries): void {
        if (preg_match('/from ["`]?(appointments|schedule_blocks)["`]?/i', $query->sql) === 1) {
            $calendarQueries++;
        }
    });

    $component->call('pollAgenda')->assertSet('appointmentPanelOpen', true);

    expect($calendarQueries)->toBe(0);
});

test('precarga una cita desde un intervalo de quince minutos', function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);

    $user = User::factory()->administratorGeneral()->create();
    actingAs($user);

    $category = ServiceCategory::factory()->create();
    $firstService = Service::factory()->create([
        'service_category_id' => $category->id,
        'duration_minutes' => 90,
        'price' => 25,
        'is_active' => true,
    ]);
    $secondService = Service::factory()->create([
        'service_category_id' => $category->id,
        'duration_minutes' => 30,
        'price' => 15,
        'is_active' => true,
    ]);

    $component = Livewire::test(AgendaIndex::class)
        ->call('openCreateModalForSlot', '2026-07-15T13:15', $user->id)
        ->assertSet('appointmentPanelOpen', true)
        ->assertSet('appointmentStartedFromCalendarSlot', true)
        ->assertSet('selectedDate', '2026-07-15')
        ->assertSet('form.professional_id', $user->id)
        ->assertSet('form.starts_at', '2026-07-15T13:15')
        ->assertSet('form.ends_at', '2026-07-15T14:15')
        ->assertSet('selectedSlotStart', '2026-07-15T13:15')
        ->assertSet('selectedSlotEnd', '2026-07-15T14:15');

    $component
        ->call('selectAppointmentService', $firstService->id)
        ->assertSet('appointmentStep', 'summary')
        ->assertSet('form.professional_id', $user->id)
        ->assertSet('form.starts_at', '2026-07-15T13:15')
        ->assertSet('form.ends_at', '2026-07-15T14:45')
        ->assertSet('form.duration_minutes', '90')
        ->assertSee('Checkout')
        ->assertSee('Guardar');

    $component
        ->call('showServiceStep')
        ->call('selectAppointmentService', $secondService->id)
        ->assertSet('appointmentStep', 'summary')
        ->assertSet('form.ends_at', '2026-07-15T15:15')
        ->assertSet('form.duration_minutes', '120')
        ->assertSet('form.price', '40');
});
