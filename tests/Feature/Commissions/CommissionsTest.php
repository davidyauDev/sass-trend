<?php

use App\Actions\Agenda\ChangeAppointmentStatusAction;
use App\Livewire\Commissions\Index as CommissionsIndex;
use App\Models\Appointment;
use App\Models\AppointmentStatus;
use App\Models\Branch;
use App\Models\Client;
use App\Models\CommissionRule;
use App\Models\CommissionType;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Agenda\AppointmentStatusCatalog;
use App\Services\Commissions\CommissionSourceCatalog;
use App\Services\Commissions\CommissionTypeCatalog;
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

function commissionsAdmin(): User
{
    return User::factory()->administratorGeneral()->create();
}

test('puede listar el modulo de comisiones', function () {
    actingAs(commissionsAdmin());

    $this->get(route('comisiones.index'))
        ->assertOk()
        ->assertSee('Commission Management');
});

test('solo administradores pueden acceder al modulo de comisiones', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('comisiones.index'))->assertForbidden();

    Livewire::test(CommissionsIndex::class)
        ->assertForbidden();
});

test('puede crear una regla de comision', function () {
    actingAs(commissionsAdmin());

    $type = CommissionType::factory()->create([
        'slug' => CommissionTypeCatalog::PERCENTAGE,
    ]);

    Livewire::test(CommissionsIndex::class)
        ->call('openRuleModal')
        ->set('ruleForm.name', 'Cita completada 10%')
        ->set('ruleForm.commission_type_id', $type->id)
        ->set('ruleForm.source_type', CommissionSourceCatalog::APPOINTMENT)
        ->set('ruleForm.priority', '10')
        ->set('ruleForm.calculation_mode', 'percentage')
        ->set('ruleForm.percentage', '10')
        ->call('saveRule')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('commission_rules', [
        'name' => 'Cita completada 10%',
        'percentage' => 10,
    ]);
});

test('completar una cita genera comision', function () {
    actingAs(commissionsAdmin());

    $branch = Branch::query()->create([
        'name' => 'Miraflores',
        'slug' => 'miraflores',
        'address' => 'Av. Larco 1234',
        'timezone' => 'America/Lima',
        'color' => 'sky',
        'is_active' => true,
    ]);

    $category = ServiceCategory::query()->create([
        'name' => 'Faciales',
        'slug' => 'faciales',
        'is_active' => true,
    ]);

    $service = Service::query()->create([
        'service_category_id' => $category->id,
        'name' => 'Limpieza facial premium',
        'price' => 200,
        'duration_minutes' => 60,
        'is_active' => true,
        'is_bookable_online' => true,
        'description' => null,
        'image_path' => null,
        'online_payment_type' => null,
        'deposit_amount' => null,
        'deposit_percentage' => null,
        'is_video_conference' => false,
        'is_home_service' => false,
        'has_special_schedule' => false,
    ]);

    $professional = User::factory()->create();
    $professional->services()->attach($service->id);

    $type = CommissionType::query()->create([
        'name' => 'Percentage Commission',
        'slug' => CommissionTypeCatalog::PERCENTAGE,
        'calculation_basis' => 'percentage',
        'is_active' => true,
    ]);

    CommissionRule::query()->create([
        'branch_id' => $branch->id,
        'service_id' => $service->id,
        'service_category_id' => $category->id,
        'commission_type_id' => $type->id,
        'name' => 'Default appointment commission',
        'slug' => 'default-appointment-commission',
        'priority' => 10,
        'source_type' => CommissionSourceCatalog::APPOINTMENT,
        'calculation_mode' => 'percentage',
        'percentage' => 10,
        'fixed_amount' => null,
        'min_revenue' => null,
        'min_quantity' => null,
        'condition_json' => null,
        'is_active' => true,
    ]);

    $client = Client::factory()->create();
    $statusPending = AppointmentStatus::query()->create([
        'name' => 'Pending',
        'slug' => AppointmentStatusCatalog::PENDING,
        'color' => 'zinc',
        'sort_order' => 1,
        'is_terminal' => false,
    ]);

    $appointment = Appointment::query()->create([
        'reference_code' => 'APT-1000',
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'service_id' => $service->id,
        'resource_id' => null,
        'professional_id' => $professional->id,
        'appointment_status_id' => $statusPending->id,
        'title' => 'Cita demo',
        'starts_at' => now()->addHour(),
        'ends_at' => now()->addHours(2),
        'duration_minutes' => 60,
        'timezone' => 'America/Lima',
        'price' => 200,
        'currency' => 'PEN',
    ]);

    $statusCompleted = AppointmentStatus::query()->create([
        'name' => 'Completed',
        'slug' => AppointmentStatusCatalog::COMPLETED,
        'color' => 'emerald',
        'sort_order' => 2,
        'is_terminal' => false,
    ]);

    app(ChangeAppointmentStatusAction::class)->handle(
        commissionsAdmin(),
        $appointment,
        $statusCompleted->slug,
    );

    $this->assertDatabaseHas('professional_commissions', [
        'user_id' => $professional->id,
        'branch_id' => $branch->id,
        'source_type' => CommissionSourceCatalog::APPOINTMENT,
        'source_reference' => (string) $appointment->id,
    ]);
});
