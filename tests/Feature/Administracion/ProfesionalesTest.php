<?php

use App\Livewire\Administracion\Profesionales\Index as ProfessionalsIndex;
use App\Models\Location;
use App\Models\Professional;
use App\Models\ProfessionalGroup;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Users\UserRoleCatalog;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

function createProfessionalAdmin(): User
{
    return User::factory()->administratorGeneral()->create();
}

test('puede listar la pagina de profesionales', function () {
    actingAs(createProfessionalAdmin());

    $response = $this->get(route('administracion.profesionales.index'));

    $response
        ->assertOk()
        ->assertSee('Profesionales')
        ->assertSee('Grupos Personalizados');
});

test('puede crear profesional con usuario, horarios y servicios', function () {
    actingAs(createProfessionalAdmin());

    $location = Location::factory()->create(['is_active' => true]);
    $category = ServiceCategory::factory()->create(['name' => 'Cabello']);
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Corte premium',
    ]);

    Livewire::test(ProfessionalsIndex::class)
        ->call('openCreateModal')
        ->set('form.public_name', 'David Yauri')
        ->set('form.has_system_access', true)
        ->set('form.email', 'david.professional@test.pe')
        ->set('form.accepts_online_bookings', true)
        ->set('form.service_ids', [$service->id])
        ->set('form.schedules.0.is_working', true)
        ->set('form.schedules.0.starts_at', '09:00')
        ->set('form.schedules.0.ends_at', '18:00')
        ->call('addBreak', 0)
        ->set('form.schedules.0.breaks.0.starts_at', '13:00')
        ->set('form.schedules.0.breaks.0.ends_at', '14:00')
        ->call('save')
        ->assertHasNoErrors();

    $professional = Professional::query()->where('public_name', 'David Yauri')->firstOrFail();

    expect($professional->user_id)->not->toBeNull();
    expect($professional->services()->whereKey($service->id)->exists())->toBeTrue();
    expect($professional->locations()->whereKey($location->id)->exists())->toBeTrue();

    $this->assertDatabaseHas('users', [
        'id' => $professional->user_id,
        'email' => 'david.professional@test.pe',
        'role_id' => Role::query()->where('slug', UserRoleCatalog::PROFESSIONAL)->value('id'),
    ]);

    $this->assertDatabaseHas('professional_schedules', [
        'professional_id' => $professional->id,
        'day_of_week' => 1,
        'is_working' => true,
        'starts_at' => '09:00',
        'ends_at' => '18:00',
    ]);

    $scheduleId = $professional->schedules()->where('day_of_week', 1)->value('id');

    $this->assertDatabaseHas('professional_schedule_breaks', [
        'professional_schedule_id' => $scheduleId,
        'starts_at' => '13:00',
        'ends_at' => '14:00',
    ]);
});

test('valida acceso y reserva online del profesional', function () {
    actingAs(createProfessionalAdmin());

    Livewire::test(ProfessionalsIndex::class)
        ->call('openCreateModal')
        ->set('form.public_name', 'Sin acceso')
        ->set('form.has_system_access', false)
        ->set('form.accepts_online_bookings', true)
        ->call('save')
        ->assertHasNoErrors();

    $professional = Professional::query()->where('public_name', 'Sin acceso')->firstOrFail();
    $technicalUser = User::query()->findOrFail($professional->user_id);

    expect($professional->accepts_online_bookings)->toBeTrue()
        ->and($professional->has_system_access)->toBeFalse()
        ->and($technicalUser->is_active)->toBeFalse()
        ->and($technicalUser->invited_at)->toBeNull();

    Livewire::test(ProfessionalsIndex::class)
        ->call('openCreateModal')
        ->set('form.public_name', 'Con acceso')
        ->set('form.has_system_access', true)
        ->set('form.email', '')
        ->call('save')
        ->assertHasErrors([
            'form.email',
        ]);
});

test('puede activar reservas online al editar un profesional', function () {
    actingAs(createProfessionalAdmin());

    Location::factory()->create(['is_active' => true]);
    $professional = Professional::factory()->create([
        'user_id' => null,
        'email' => null,
        'has_system_access' => false,
        'accepts_online_bookings' => false,
        'is_active' => true,
    ]);

    Livewire::test(ProfessionalsIndex::class)
        ->call('openEditModal', $professional->id)
        ->assertSee('Acepta reservas online')
        ->assertSee('Acceso al sistema')
        ->assertSee('Profesional activo')
        ->set('form.accepts_online_bookings', true)
        ->call('save')
        ->assertHasNoErrors();

    $professional->refresh();
    $technicalUser = User::query()->findOrFail($professional->user_id);

    expect($professional->accepts_online_bookings)->toBeTrue()
        ->and($professional->has_system_access)->toBeFalse()
        ->and($technicalUser->is_active)->toBeFalse()
        ->and($technicalUser->invited_at)->toBeNull();
});

test('puede guardar un profesional con horarios existentes de mysql', function () {
    actingAs(createProfessionalAdmin());

    Location::factory()->create(['is_active' => true]);
    $professional = Professional::factory()->create([
        'accepts_online_bookings' => false,
        'has_system_access' => false,
    ]);
    $schedule = $professional->schedules()->create([
        'day_of_week' => 1,
        'is_working' => true,
        'starts_at' => '10:00:00',
        'ends_at' => '19:00:00',
    ]);
    $schedule->breaks()->create([
        'starts_at' => '13:00:00',
        'ends_at' => '14:00:00',
    ]);

    Livewire::test(ProfessionalsIndex::class)
        ->call('openEditModal', $professional->id)
        ->assertSet('form.schedules.0.starts_at', '10:00')
        ->assertSet('form.schedules.0.ends_at', '19:00')
        ->assertSet('form.schedules.0.breaks.0.starts_at', '13:00')
        ->assertSet('form.schedules.0.breaks.0.ends_at', '14:00')
        ->call('save')
        ->assertHasNoErrors();

    $monday = $professional->fresh()->schedules()->where('day_of_week', 1)->firstOrFail();

    expect($monday->starts_at)->toStartWith('10:00')
        ->and($monday->ends_at)->toStartWith('19:00');
});

test('puede crear y editar un grupo personalizado', function () {
    actingAs(createProfessionalAdmin());

    $location = Location::factory()->create(['is_active' => true, 'name' => 'Miraflores']);
    $professional = Professional::factory()->create([
        'public_name' => 'Camila Rojas',
        'is_active' => true,
    ]);
    $professional->locations()->sync([$location->id]);

    Livewire::test(ProfessionalsIndex::class)
        ->set('sectionTab', 'groups')
        ->call('openCreateGroupModal')
        ->set('groupForm.name', 'Equipo mañana')
        ->set('groupForm.location_id', $location->id)
        ->call('saveGroup')
        ->assertHasNoErrors();

    $group = ProfessionalGroup::query()->where('name', 'Equipo mañana')->firstOrFail();

    Livewire::test(ProfessionalsIndex::class)
        ->set('sectionTab', 'groups')
        ->call('openEditGroupModal', $group->id)
        ->set('groupForm.member_ids', [$professional->id])
        ->call('saveGroup')
        ->assertHasNoErrors();

    expect($group->refresh()->professionals()->whereKey($professional->id)->exists())->toBeTrue();
});

test('solo administradores pueden acceder al modulo', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('administracion.profesionales.index'))->assertForbidden();

    Livewire::test(ProfessionalsIndex::class)
        ->assertForbidden();
});
