<?php

use App\Livewire\Administracion\Servicios\Index as ServicesIndex;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Agenda\AppointmentStatusCatalog;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function createAdminUser(): User
{
    return User::factory()->administratorGeneral()->create();
}

test('puede listar servicios', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create([
        'name' => 'Faciales',
    ]);

    Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Limpieza Premium',
    ]);

    $response = $this->get(route('administracion.servicios.index'));

    $response
        ->assertOk()
        ->assertSee('Servicios')
        ->assertSee('Limpieza Premium')
        ->assertSee('Faciales');
});

test('puede crear servicio', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();
    $professional = User::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Masaje deportivo')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '120')
        ->set('form.duration_minutes', '60')
        ->set('form.professional_ids', [$professional->id])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('services', [
        'name' => 'Masaje deportivo',
        'service_category_id' => $category->id,
        'duration_minutes' => 60,
    ]);
});

test('puede crear servicio con nueva categoria', function () {
    actingAs(createAdminUser());

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Limpieza express')
        ->set('form.service_category_id', null)
        ->set('form.new_category_name', 'Faciales premium')
        ->set('form.price', '95')
        ->set('form.duration_minutes', '40')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('service_categories', [
        'name' => 'Faciales premium',
    ]);

    $this->assertDatabaseHas('services', [
        'name' => 'Limpieza express',
    ]);
});

test('valida nombre categoria precio y duracion', function () {
    actingAs(createAdminUser());

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', '')
        ->set('form.service_category_id', null)
        ->set('form.price', '')
        ->set('form.duration_minutes', '')
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
            'form.price' => 'required',
            'form.duration_minutes' => 'required',
            'form.service_category_id',
        ]);
});

test('puede asignar profesionales', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();
    $professionalA = User::factory()->create();
    $professionalB = User::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Consulta integral')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '80')
        ->set('form.duration_minutes', '45')
        ->set('form.professional_ids', [$professionalA->id, $professionalB->id])
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::query()->where('name', 'Consulta integral')->firstOrFail();

    expect($service->professionals()->count())->toBe(2);
});

test('puede activar y desactivar reserva online', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Servicio web')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '40')
        ->set('form.duration_minutes', '30')
        ->set('form.is_bookable_online', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Service::query()->where('name', 'Servicio web')->firstOrFail()->is_bookable_online)->toBeFalse();
});

test('puede activar y desactivar servicio', function () {
    actingAs(createAdminUser());

    $service = Service::factory()->create([
        'is_active' => true,
    ]);

    Livewire::test(ServicesIndex::class)
        ->call('toggleStatus', $service->id)
        ->assertHasNoErrors();

    expect($service->refresh()->is_active)->toBeFalse();
});

test('puede configurar pago online', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Consulta premium')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '150')
        ->set('form.duration_minutes', '50')
        ->set('form.online_payment_type', 'deposit_required')
        ->set('form.deposit_amount', '50')
        ->set('form.deposit_percentage', '30')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('services', [
        'name' => 'Consulta premium',
        'online_payment_type' => 'deposit_required',
        'deposit_amount' => 50,
        'deposit_percentage' => 30,
    ]);
});

test('puede guardar horario especial', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Servicio horario')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '90')
        ->set('form.duration_minutes', '30')
        ->set('form.has_special_schedule', true)
        ->set('form.schedules.0.is_active', true)
        ->set('form.schedules.0.starts_at', '08:00')
        ->set('form.schedules.0.ends_at', '12:00')
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::query()->where('name', 'Servicio horario')->firstOrFail();

    $this->assertDatabaseHas('service_schedules', [
        'service_id' => $service->id,
        'day_of_week' => 1,
        'is_active' => true,
        'starts_at' => '08:00',
        'ends_at' => '12:00',
    ]);
});

test('rechaza horario invalido', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Servicio inválido')
        ->set('form.service_category_id', $category->id)
        ->set('form.price', '50')
        ->set('form.duration_minutes', '30')
        ->set('form.has_special_schedule', true)
        ->set('form.schedules.0.is_active', true)
        ->set('form.schedules.0.starts_at', '18:00')
        ->set('form.schedules.0.ends_at', '09:00')
        ->call('save')
        ->assertHasErrors([
            'form.schedules.0.ends_at',
        ]);
});

test('no elimina servicios con citas asociadas y los desactiva', function () {
    actingAs(createAdminUser());

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
    ]);

    $branch = Branch::query()->create([
        'name' => 'Miraflores',
        'slug' => 'miraflores',
        'address' => 'Av. Larco 1234',
        'phone' => '987654321',
        'email' => 'miraflores@example.com',
        'timezone' => 'America/Lima',
        'color' => 'sky',
        'is_active' => true,
    ]);

    $client = Client::factory()->create();
    $status = AppointmentStatus::query()->create([
        'name' => 'Confirmado',
        'slug' => AppointmentStatusCatalog::CONFIRMED,
        'color' => 'emerald',
        'sort_order' => 1,
        'is_terminal' => false,
    ]);

    Appointment::query()->create([
        'reference_code' => 'APT-9999',
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'service_id' => $service->id,
        'resource_id' => null,
        'professional_id' => null,
        'appointment_status_id' => $status->id,
        'title' => 'Cita de prueba',
        'starts_at' => Carbon::now()->addDay(),
        'ends_at' => Carbon::now()->addDay()->addHour(),
        'duration_minutes' => 60,
        'timezone' => 'America/Lima',
        'price' => 100,
        'currency' => 'PEN',
    ]);

    Livewire::test(ServicesIndex::class)
        ->call('confirmDelete', $service->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect($service->refresh()->is_active)->toBeFalse();
    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'is_active' => false,
    ]);
});

test('solo administrador puede crear servicios', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('administracion.servicios.index'))->assertForbidden();

    Livewire::test(ServicesIndex::class)
        ->assertForbidden();
});
