<?php

use App\Actions\Sales\CreateSaleAction;
use App\Actions\Sales\DeleteSaleAction;
use App\Livewire\Sales\Index as SalesIndex;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductBranchStock;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Sales\SalePaymentMethodCatalog;
use App\Services\Sales\SaleStatusCatalog;
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

function salesAdmin(): User
{
    return User::factory()->administratorGeneral()->create();
}

function createSalesFixtures(): array
{
    $branch = Branch::factory()->create([
        'name' => 'David Yauri',
        'slug' => 'david-yauri',
        'is_active' => true,
    ]);

    $client = Client::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Pérez',
        'email' => 'cliente@test.pe',
        'phone' => '999999999',
    ]);

    $category = ServiceCategory::factory()->create([
        'name' => 'Otros',
        'slug' => 'otros',
    ]);

    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'name' => 'Corte de Cabello',
        'price' => 100,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'name' => 'Shampooo',
        'public_sale_price' => 100,
        'is_active' => true,
    ]);

    ProductBranchStock::query()->create([
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'current_stock' => 5,
    ]);

    return compact('branch', 'client', 'category', 'service', 'product');
}

test('puede listar el modulo de ventas', function () {
    actingAs(salesAdmin());

    $this->get(route('sales.index'))
        ->assertOk()
        ->assertSee('Ventas')
        ->assertSee('Nueva venta');
});

test('puede registrar una venta mixta desde livewire y descontar stock del producto', function () {
    actingAs(salesAdmin());

    ['branch' => $branch, 'client' => $client, 'service' => $service, 'product' => $product] = createSalesFixtures();

    Livewire::test(SalesIndex::class)
        ->call('openCreateSale')
        ->set('saleForm.branch_id', $branch->id)
        ->call('selectClient', $client->id)
        ->call('addProductToCart', $product->id)
        ->call('addServiceToCart', $service->id)
        ->call('proceedToPayment')
        ->call('completeSale', SalePaymentMethodCatalog::BANK_TRANSFER)
        ->assertHasNoErrors()
        ->assertSet('drawerStep', 'success');

    $sale = Sale::query()
        ->with(['items', 'payments'])
        ->latest('id')
        ->firstOrFail();

    expect($sale->status)->toBe(SaleStatusCatalog::PAID);
    expect((float) $sale->total)->toBe(200.0);
    expect($sale->items)->toHaveCount(2);
    expect($sale->payments)->toHaveCount(1);

    $this->assertDatabaseHas('sale_payments', [
        'sale_id' => $sale->id,
        'method' => SalePaymentMethodCatalog::BANK_TRANSFER,
        'amount' => 200,
    ]);

    $this->assertDatabaseHas('product_branch_stocks', [
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'current_stock' => 4,
    ]);
});

test('continuar sin cliente abre la busqueda de clientes', function () {
    actingAs(salesAdmin());

    ['branch' => $branch, 'service' => $service] = createSalesFixtures();

    Livewire::test(SalesIndex::class)
        ->call('openCreateSale')
        ->set('saleForm.branch_id', $branch->id)
        ->call('addServiceToCart', $service->id)
        ->call('proceedToPayment')
        ->assertSet('drawerStep', 'client-search');
});

test('eliminar una venta repone el stock del producto', function () {
    $admin = salesAdmin();
    actingAs($admin);

    ['branch' => $branch, 'client' => $client, 'product' => $product] = createSalesFixtures();

    $sale = app(CreateSaleAction::class)->handle($admin, [
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'notes' => null,
        'status' => SaleStatusCatalog::PAID,
        'items' => [[
            'item_type' => 'product',
            'service_id' => null,
            'product_id' => $product->id,
            'item_name' => $product->name,
            'item_detail' => '100ml',
            'quantity' => 2,
            'unit_price' => 100,
            'meta' => null,
        ]],
        'payments' => [[
            'method' => SalePaymentMethodCatalog::CASH,
            'amount' => 200,
            'reference' => null,
        ]],
    ]);

    expect((float) ProductBranchStock::query()
        ->where('product_id', $product->id)
        ->where('branch_id', $branch->id)
        ->value('current_stock'))->toBe(3.0);

    app(DeleteSaleAction::class)->handle($admin, $sale->fresh());

    $this->assertSoftDeleted('sales', [
        'id' => $sale->id,
    ]);

    $this->assertDatabaseHas('product_branch_stocks', [
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'current_stock' => 5,
    ]);
});

test('puede ver comprobante y exportar ventas', function () {
    $admin = salesAdmin();
    actingAs($admin);

    ['branch' => $branch, 'client' => $client, 'service' => $service] = createSalesFixtures();

    $sale = app(CreateSaleAction::class)->handle($admin, [
        'branch_id' => $branch->id,
        'client_id' => $client->id,
        'notes' => 'Venta de prueba',
        'status' => SaleStatusCatalog::PAID,
        'items' => [[
            'item_type' => 'service',
            'service_id' => $service->id,
            'product_id' => null,
            'item_name' => $service->name,
            'item_detail' => '30 min',
            'quantity' => 1,
            'unit_price' => 100,
            'meta' => null,
        ]],
        'payments' => [[
            'method' => SalePaymentMethodCatalog::DEBIT_CARD,
            'amount' => 100,
            'reference' => 'TB-100',
        ]],
    ]);

    $this->get(route('sales.receipt.show', $sale))
        ->assertOk()
        ->assertSee('Comprobante de pago')
        ->assertSee((string) $sale->ticket_number);

    $exportResponse = $this->get(route('sales.export', [
        'period' => 'all',
        'status' => SaleStatusCatalog::PAID,
    ]));

    $exportResponse->assertOk();

    expect($exportResponse->streamedContent())
        ->toContain('Venta')
        ->toContain((string) $sale->sale_number)
        ->toContain('Tarjeta de Débito');
});

test('solo usuarios autorizados pueden acceder al modulo de ventas', function () {
    $viewerRoleId = Role::query()->where('slug', 'receptionist_viewer')->value('id');

    actingAs(User::factory()->create([
        'role_id' => $viewerRoleId,
        'is_active' => true,
    ]));

    $this->get(route('sales.index'))->assertForbidden();

    Livewire::test(SalesIndex::class)
        ->assertForbidden();
});
