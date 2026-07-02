<?php

use App\Livewire\Administracion\Comisiones\Report as CommissionReport;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Commissions\CommissionReportWorkbookExport;
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

function commissionsReportAdmin(): User
{
    return User::factory()->administratorGeneral()->create();
}

test('puede listar el reporte de comisiones', function () {
    actingAs(commissionsReportAdmin());

    $this->get(route('administracion.comisiones.reporte'))
        ->assertOk()
        ->assertSee('Reporte de comisiones')
        ->assertSee('Ventas de servicios')
        ->assertSee('Venta de productos')
        ->assertDontSee('Ventas de planes')
        ->assertDontSee('Cobros por ventas internas');
});

test('solo administradores pueden acceder al reporte de comisiones', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('administracion.comisiones.reporte'))->assertForbidden();

    Livewire::test(CommissionReport::class)
        ->assertForbidden();
});

test('puede calcular el reporte de comisiones desde ventas reales', function () {
    actingAs(commissionsReportAdmin());

    $branch = Branch::factory()->create([
        'name' => 'Santa Anita',
        'is_active' => true,
    ]);

    $professional = Professional::factory()->create([
        'public_name' => 'DALIA YAURI',
        'is_active' => true,
        'sale_commission' => 10,
        'commission_type' => 'percent',
    ]);

    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Corte premium',
        'price' => 100,
    ]);

    $professional->services()->attach($service->id, [
        'sale_commission' => 20,
        'commission_type' => 'percent',
    ]);

    $product = Product::factory()->create([
        'name' => 'Shampoo expert',
        'public_sale_price' => 40,
        'sale_commission' => 5,
        'commission_type' => 'amount',
    ]);

    $sale = Sale::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'paid',
        'sold_at' => now(),
        'subtotal' => 140,
        'discount_total' => 0,
        'total' => 140,
        'paid_total' => 140,
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'item_type' => 'service',
        'service_id' => $service->id,
        'product_id' => null,
        'item_name' => $service->name,
        'item_detail' => '60 min',
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
        'meta' => [
            'professional_id' => $professional->id,
            'professional_name' => $professional->public_name,
        ],
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'item_type' => 'product',
        'service_id' => null,
        'product_id' => $product->id,
        'item_name' => $product->name,
        'item_detail' => '250 ml',
        'quantity' => 1,
        'unit_price' => 40,
        'subtotal' => 40,
        'meta' => [
            'professional_id' => $professional->id,
            'professional_name' => $professional->public_name,
        ],
    ]);

    Livewire::test(CommissionReport::class)
        ->assertSee('DALIA YAURI')
        ->assertSee('S/100')
        ->assertSee('S/40')
        ->assertSee('S/140')
        ->assertSee('S/25');
});

test('puede exportar el reporte de comisiones con hojas detalladas por profesional', function () {
    actingAs(commissionsReportAdmin());

    $branch = Branch::factory()->create([
        'name' => 'Santa Anita',
        'is_active' => true,
    ]);

    $professionalA = Professional::factory()->create([
        'public_name' => 'David Yauri',
        'is_active' => true,
        'sale_commission' => 5,
        'commission_type' => 'percent',
    ]);

    $professionalB = Professional::factory()->create([
        'public_name' => 'DALIA YAURI',
        'is_active' => true,
        'sale_commission' => 5,
        'commission_type' => 'percent',
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Otros',
    ]);

    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Corte de cabello',
        'price' => 50,
    ]);

    $service->professionalProfiles()->attach($professionalA->id, [
        'sale_commission' => 5,
        'commission_type' => 'percent',
    ]);

    $product = Product::factory()->create([
        'name' => 'Esmalte gel',
        'public_sale_price' => 18.5,
        'sale_commission' => 5,
        'commission_type' => 'percent',
    ]);

    $sale = Sale::factory()->create([
        'branch_id' => $branch->id,
        'status' => 'paid',
        'sold_at' => now(),
        'subtotal' => 68.5,
        'discount_total' => 0,
        'total' => 68.5,
        'paid_total' => 68.5,
        'notes' => 'Venta de prueba',
        'user_id' => commissionsReportAdmin()->id,
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'item_type' => 'service',
        'service_id' => $service->id,
        'product_id' => null,
        'item_name' => $service->name,
        'item_detail' => '30 min',
        'quantity' => 1,
        'unit_price' => 50,
        'subtotal' => 50,
        'meta' => [
            'professional_id' => $professionalA->id,
            'professional_name' => $professionalA->public_name,
        ],
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'item_type' => 'product',
        'service_id' => null,
        'product_id' => $product->id,
        'item_name' => $product->name,
        'item_detail' => '15 ml',
        'quantity' => 1,
        'unit_price' => 18.5,
        'subtotal' => 18.5,
        'meta' => [
            'professional_id' => $professionalB->id,
            'professional_name' => $professionalB->public_name,
        ],
    ]);

    $path = app(CommissionReportWorkbookExport::class)->export([
        'period' => 'last_7_days',
        'branchId' => (string) $branch->id,
        'userType' => 'active_professionals',
        'professionalId' => 'all',
    ]);

    $zip = new ZipArchive();

    expect($zip->open($path))->toBeTrue();

    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $summaryXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $dailyXml = $zip->getFromName('xl/worksheets/sheet2.xml');
    $productionXml = $zip->getFromName('xl/worksheets/sheet3.xml');

    $zip->close();

    expect($workbookXml)
        ->toContain('resumen')
        ->toContain('Recaudaciones por fecha')
        ->toContain('produccion')
        ->toContain('David Yauri')
        ->toContain('DALIA YAURI');

    expect($summaryXml)
        ->toContain('Total comisiones')
        ->toContain('Comisiones Netas')
        ->toContain('David Yauri')
        ->toContain('DALIA YAURI');

    expect($dailyXml)
        ->toContain('Total ventas')
        ->toContain('Total comisiones');

    expect($productionXml)
        ->toContain('Id pago')
        ->toContain('Comisión a pagar')
        ->toContain('Medios de pago');
});
