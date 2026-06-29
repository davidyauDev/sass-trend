<?php

use App\Livewire\Administracion\Comisiones\Index as CommissionsIndex;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
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

test('puede listar el modulo de comisiones simplificado', function () {
    actingAs(commissionsAdmin());

    $this->get(route('administracion.comisiones.index'))
        ->assertOk()
        ->assertSee('Servicios')
        ->assertSee('Productos')
        ->assertDontSee('Planes');
});

test('solo administradores pueden acceder al modulo de comisiones', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('administracion.comisiones.index'))->assertForbidden();

    Livewire::test(CommissionsIndex::class)
        ->assertForbidden();
});

test('puede actualizar la comision por defecto de un profesional', function () {
    actingAs(commissionsAdmin());

    $professional = Professional::factory()->create([
        'sale_commission' => 10,
        'commission_type' => 'percent',
    ]);

    Livewire::test(CommissionsIndex::class)
        ->call('openProfessionalDefaultModal', $professional->id)
        ->set('professionalDefaultForm.sale_commission', '15')
        ->set('professionalDefaultForm.commission_type', 'amount')
        ->call('saveProfessionalDefaultCommission')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('professionals', [
        'id' => $professional->id,
        'sale_commission' => 15,
        'commission_type' => 'amount',
    ]);
});

test('puede actualizar la comision de un servicio por profesional', function () {
    actingAs(commissionsAdmin());

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Limpieza facial premium',
    ]);

    $professional = Professional::factory()->create();
    $professional->services()->attach($service->id, [
        'sale_commission' => 0,
        'commission_type' => 'percent',
    ]);

    Livewire::test(CommissionsIndex::class)
        ->call('openProfessionalServicesModal', $professional->id)
        ->set('professionalServiceForm.rows.0.sale_commission', '12.5')
        ->set('professionalServiceForm.rows.0.commission_type', 'amount')
        ->call('saveProfessionalServices')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('professional_service_assignments', [
        'professional_id' => $professional->id,
        'service_id' => $service->id,
        'sale_commission' => 12.5,
        'commission_type' => 'amount',
    ]);
});

test('puede actualizar la comision de un producto', function () {
    actingAs(commissionsAdmin());

    $product = Product::factory()->create([
        'sale_commission' => 5,
        'commission_type' => 'percent',
    ]);

    Livewire::test(CommissionsIndex::class)
        ->call('openProductModal', $product->id)
        ->set('productForm.sale_commission', '20')
        ->set('productForm.commission_type', 'amount')
        ->call('saveProductCommission')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'sale_commission' => 20,
        'commission_type' => 'amount',
    ]);
});
