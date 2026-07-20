<?php

use App\Actions\Locations\SaveLocationSchedulesAction;
use App\Livewire\Administracion\Locales\Index as LocationsIndex;
use App\Models\Location;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

test('puede listar locales', function () {
    actingAs(User::factory()->create());

    Location::factory()->create([
        'name' => 'Local Miraflores',
        'address' => 'Av. Larco 123',
        'email' => 'miraflores@example.com',
    ]);

    $response = $this->get(route('locales.index'));

    $response
        ->assertOk()
        ->assertSee('Locales')
        ->assertSee('Local Miraflores')
        ->assertSee('Av. Larco 123');
});

test('puede crear un local', function () {
    actingAs(User::factory()->create());

    Livewire::test(LocationsIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Local San Isidro')
        ->set('form.address', 'Av. Javier Prado 456')
        ->set('form.phone', '999111222')
        ->set('form.email', 'sanisidro@example.com')
        ->set('form.timezone', 'America/Lima')
        ->set('form.accepts_online_bookings', true)
        ->set('form.secondary_phone', '988777666')
        ->set('form.description', 'Sede principal')
        ->set('form.schedules.0.is_open', true)
        ->set('form.schedules.0.opens_at', '09:00')
        ->set('form.schedules.0.closes_at', '18:00')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('locations', [
        'name' => 'Local San Isidro',
        'address' => 'Av. Javier Prado 456',
        'email' => 'sanisidro@example.com',
        'accepts_online_bookings' => true,
    ]);
});

test('valida nombre requerido', function () {
    actingAs(User::factory()->create());

    Livewire::test(LocationsIndex::class)
        ->call('openCreateModal')
        ->set('form.name', '')
        ->set('form.address', 'Av. Primavera 250')
        ->call('save')
        ->assertHasErrors([
            'form.name' => 'required',
        ]);
});

test('valida direccion requerida', function () {
    actingAs(User::factory()->create());

    Livewire::test(LocationsIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Local Surco')
        ->set('form.address', '')
        ->call('save')
        ->assertHasErrors([
            'form.address' => 'required',
        ]);
});

test('puede guardar horarios', function () {
    actingAs(User::factory()->create());

    Livewire::test(LocationsIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Local Horarios')
        ->set('form.address', 'Av. Los Fresnos 700')
        ->set('form.schedules.0.is_open', true)
        ->set('form.schedules.0.opens_at', '08:00')
        ->set('form.schedules.0.closes_at', '17:00')
        ->set('form.schedules.5.is_open', true)
        ->set('form.schedules.5.opens_at', '10:00')
        ->set('form.schedules.5.closes_at', '14:00')
        ->call('save')
        ->assertHasNoErrors();

    $location = Location::query()->where('name', 'Local Horarios')->firstOrFail();

    $this->assertDatabaseHas('location_schedules', [
        'location_id' => $location->id,
        'day_of_week' => 1,
        'is_open' => true,
        'opens_at' => '08:00',
        'closes_at' => '17:00',
    ]);

    $this->assertDatabaseHas('location_schedules', [
        'location_id' => $location->id,
        'day_of_week' => 6,
        'is_open' => true,
        'opens_at' => '10:00',
        'closes_at' => '14:00',
    ]);
});

test('actualiza horarios antiguos sin duplicar la clave unica', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Horarios',
        'slug' => 'trend-horarios',
        'owner_name' => 'Owner',
        'owner_email' => 'owner-horarios@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);

    try {
        $location = Location::factory()->create();
        $timestamp = now();

        DB::table('location_schedules')->insert([
            'tenant_id' => null,
            'location_id' => $location->id,
            'day_of_week' => 1,
            'is_open' => false,
            'opens_at' => null,
            'closes_at' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $schedules = collect(range(1, 7))
            ->map(fn (int $day): array => [
                'day_of_week' => $day,
                'is_open' => $day === 1,
                'opens_at' => $day === 1 ? '09:00' : null,
                'closes_at' => $day === 1 ? '18:00' : null,
            ])
            ->all();

        app(SaveLocationSchedulesAction::class)->handle($location, $schedules);

        expect(DB::table('location_schedules')->where('location_id', $location->id)->count())->toBe(7);

        $this->assertDatabaseHas('location_schedules', [
            'tenant_id' => $tenant->id,
            'location_id' => $location->id,
            'day_of_week' => 1,
            'is_open' => true,
            'opens_at' => '09:00',
            'closes_at' => '18:00',
        ]);
    } finally {
        tenancy()->end();
    }
});

test('puede activar y desactivar reservas online', function () {
    actingAs(User::factory()->create());

    $location = Location::factory()->create([
        'accepts_online_bookings' => false,
    ]);

    Livewire::test(LocationsIndex::class)
        ->call('openEditModal', $location->id)
        ->set('form.accepts_online_bookings', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($location->fresh()->accepts_online_bookings)->toBeTrue();
});

test('puede eliminar local', function () {
    actingAs(User::factory()->create());

    $location = Location::factory()->create();

    Livewire::test(LocationsIndex::class)
        ->call('confirmDelete', $location->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect($location->fresh())->toBeNull();
});

test('no permite horario invalido donde cierre sea antes de apertura', function () {
    actingAs(User::factory()->create());

    Livewire::test(LocationsIndex::class)
        ->call('openCreateModal')
        ->set('form.name', 'Local Inválido')
        ->set('form.address', 'Av. Error 404')
        ->set('form.schedules.0.is_open', true)
        ->set('form.schedules.0.opens_at', '18:00')
        ->set('form.schedules.0.closes_at', '09:00')
        ->call('save')
        ->assertHasErrors([
            'form.schedules.0.closes_at',
        ]);
});
