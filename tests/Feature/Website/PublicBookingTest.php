<?php

use App\Livewire\SitioWeb\Booking;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Professional;
use App\Models\ProfessionalSchedule;
use App\Models\ProfessionalScheduleBreak;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebsiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('sitio publico devuelve 404 cuando esta inactivo', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Belleza',
        'slug' => 'trend-belleza',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);
    WebsiteSetting::current()->update(['is_active' => false]);
    tenancy()->end();

    $this->get(route('reservas.index', ['tenant' => $tenant]))->assertNotFound();
});

test('una reserva publica crea cliente y cita', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Belleza',
        'slug' => 'trend-belleza',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);
    WebsiteSetting::current()->update(['is_active' => true]);

    $branch = Branch::query()->create([
        'name' => 'Miraflores',
        'slug' => 'miraflores-test',
        'address' => 'Av. Larco 1234',
        'phone' => '999111222',
        'email' => 'miraflores@test.pe',
        'timezone' => 'America/Lima',
        'color' => 'sky',
        'is_active' => true,
    ]);

    $location = Location::query()->create([
        'name' => 'SASS Trend Miraflores',
        'address' => 'Av. Larco 1234',
        'phone' => '999111222',
        'email' => 'miraflores@test.pe',
        'timezone' => 'America/Lima',
        'branch_id' => null,
        'accepts_online_bookings' => true,
        'is_active' => true,
    ]);

    LocationSchedule::query()->create([
        'location_id' => $location->id,
        'day_of_week' => CarbonImmutable::now()->isoWeekday(),
        'is_open' => true,
        'opens_at' => '09:00',
        'closes_at' => '18:00',
    ]);

    $professionalUser = User::factory()->create([
        'first_name' => 'Camila',
        'last_name' => 'Rojas',
        'name' => 'Camila Rojas',
        'email' => 'camila.booking@test.pe',
        'is_active' => false,
    ]);
    $professionalUser->locations()->sync([$location->id]);

    $professional = Professional::query()->create([
        'user_id' => $professionalUser->id,
        'public_name' => 'Camila Rojas',
        'email' => 'camila.booking@test.pe',
        'accepts_online_bookings' => true,
        'has_system_access' => false,
        'is_active' => true,
    ]);
    $professional->locations()->sync([$location->id]);

    $schedule = ProfessionalSchedule::query()->create([
        'professional_id' => $professional->id,
        'day_of_week' => CarbonImmutable::now()->isoWeekday(),
        'is_working' => true,
        'starts_at' => '09:00',
        'ends_at' => '18:00',
    ]);

    ProfessionalScheduleBreak::query()->create([
        'professional_schedule_id' => $schedule->id,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Faciales',
        'slug' => 'faciales-test',
        'description' => 'Categoria de prueba',
        'is_active' => true,
    ]);

    $service = Service::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Limpieza facial',
        'price' => 150,
        'duration_minutes' => 60,
        'is_active' => true,
        'is_bookable_online' => true,
        'has_special_schedule' => false,
    ]);
    $service->professionalProfiles()->sync([$professional->id]);
    $professionalUser->services()->sync([$service->id]);

    $startsAt = CarbonImmutable::now()->startOfDay()->setTime(9, 0)->toDateTimeString();

    Livewire::test(Booking::class)
        ->assertSee('Servicios')
        ->assertSee('Limpieza facial')
        ->assertSee('Faciales')
        ->assertSee('Equipo')
        ->assertSee('Camila Rojas')
        ->call('chooseService', $service->id)
        ->assertSet('service_id', $service->id)
        ->assertDispatched('open-booking', categoryId: $category->id)
        ->call('continueBooking')
        ->assertSet('bookingStep', 2)
        ->call('selectBookingProfessional', $professional->id)
        ->assertSet('professional_id', $professional->id)
        ->call('continueBooking')
        ->assertSet('bookingStep', 3)
        ->call('selectSlot', $startsAt)
        ->call('continueBooking')
        ->assertSet('bookingStep', 4)
        ->set('location_id', $location->id)
        ->set('service_id', $service->id)
        ->set('professional_id', $professional->id)
        ->set('selected_date', CarbonImmutable::now()->toDateString())
        ->set('selected_starts_at', $startsAt)
        ->set('first_name', 'Maria')
        ->set('last_name', 'Lopez')
        ->set('email', 'maria.booking@test.pe')
        ->set('phone', '987654321')
        ->set('notes', 'Primera visita')
        ->call('submit')
        ->assertHasNoErrors();

    expect(Client::query()->where('email', 'maria.booking@test.pe')->exists())->toBeTrue();

    $appointment = Appointment::query()->with(['client', 'service', 'professional'])->first();

    expect($appointment)->not->toBeNull();
    expect($appointment->client->email)->toBe('maria.booking@test.pe');
    expect($appointment->service->id)->toBe($service->id);
    expect($appointment->professional->id)->toBe($professionalUser->id);

    tenancy()->end();
});

test('la reserva publica excluye horarios dentro de descansos del profesional', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Belleza',
        'slug' => 'trend-belleza-breaks',
        'owner_name' => 'Owner',
        'owner_email' => 'owner-breaks@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);
    WebsiteSetting::current()->update(['is_active' => true]);

    $branch = Branch::query()->create([
        'name' => 'Surco',
        'slug' => 'surco-breaks',
        'address' => 'Av. Benavides 123',
        'phone' => '999111999',
        'email' => 'surco@test.pe',
        'timezone' => 'America/Lima',
        'color' => 'violet',
        'is_active' => true,
    ]);

    $location = Location::query()->create([
        'name' => 'SASS Trend Surco',
        'address' => 'Av. Benavides 123',
        'phone' => '999111999',
        'email' => 'surco@test.pe',
        'timezone' => 'America/Lima',
        'branch_id' => $branch->id,
        'accepts_online_bookings' => true,
        'is_active' => true,
    ]);

    LocationSchedule::query()->create([
        'location_id' => $location->id,
        'day_of_week' => CarbonImmutable::now()->isoWeekday(),
        'is_open' => true,
        'opens_at' => '09:00',
        'closes_at' => '18:00',
    ]);

    $professionalUser = User::factory()->create([
        'name' => 'Valeria Nuñez',
        'email' => 'valeria.booking@test.pe',
        'is_active' => true,
    ]);
    $professionalUser->locations()->sync([$location->id]);

    $professional = Professional::query()->create([
        'user_id' => $professionalUser->id,
        'public_name' => 'Valeria Nuñez',
        'email' => 'valeria.booking@test.pe',
        'accepts_online_bookings' => true,
        'has_system_access' => true,
        'is_active' => true,
    ]);
    $professional->locations()->sync([$location->id]);

    $schedule = ProfessionalSchedule::query()->create([
        'professional_id' => $professional->id,
        'day_of_week' => CarbonImmutable::now()->isoWeekday(),
        'is_working' => true,
        'starts_at' => '09:00',
        'ends_at' => '18:00',
    ]);
    ProfessionalScheduleBreak::query()->create([
        'professional_schedule_id' => $schedule->id,
        'starts_at' => '12:00',
        'ends_at' => '14:00',
    ]);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'duration_minutes' => 60,
        'is_bookable_online' => true,
        'has_special_schedule' => false,
    ]);
    $service->professionalProfiles()->sync([$professional->id]);
    $professionalUser->services()->sync([$service->id]);

    $component = Livewire::test(Booking::class)
        ->set('location_id', $location->id)
        ->set('service_id', $service->id)
        ->set('professional_id', $professional->id)
        ->set('selected_date', CarbonImmutable::now()->toDateString());

    $slots = $component->get('availableSlots');

    expect(collect($slots)->pluck('starts_at'))->not->toContain(
        CarbonImmutable::now()->startOfDay()->setTime(12, 0)->toDateTimeString(),
        CarbonImmutable::now()->startOfDay()->setTime(12, 30)->toDateTimeString(),
        CarbonImmutable::now()->startOfDay()->setTime(13, 0)->toDateTimeString(),
    )->and(collect($slots)->pluck('starts_at'))->toContain(
        CarbonImmutable::now()->startOfDay()->setTime(9, 15)->toDateTimeString(),
    );

    tenancy()->end();
});
