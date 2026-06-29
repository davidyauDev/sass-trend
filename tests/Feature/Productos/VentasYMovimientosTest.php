<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\Professional;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function productCatalogsForSales(): array
{
    return [
        'brand' => ProductBrand::query()->create(['name' => 'Loreal']),
        'category' => ProductCategory::query()->create(['name' => 'Capilar']),
        'presentation' => ProductPresentation::query()->create(['name' => 'Unidad']),
    ];
}

test('puede registrar una venta y descontar stock del local', function () {
    actingAs(User::factory()->create());

    $catalogs = productCatalogsForSales();
    $branch = Branch::factory()->create([
        'name' => 'Local Centro',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Shampoo nutritivo',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 50,
        'current_stock' => 10,
        'commission_type' => 'percent',
    ]);

    $response = $this->postJson(route('products.sales.store'), [
        'branch_id' => $branch->id,
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 50,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Venta registrada correctamente.');

    expect((float) $product->refresh()->current_stock)->toBe(7.0);

    $this->assertDatabaseHas('product_sales', [
        'branch_id' => $branch->id,
        'total' => 150,
    ]);

    $this->assertDatabaseHas('product_sale_items', [
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => 50,
    ]);

    $this->assertDatabaseHas('product_stock_movements', [
        'product_id' => $product->id,
        'branch_id' => $branch->id,
        'movement_type' => 'sale',
        'quantity_delta' => -3,
        'new_stock' => 7,
    ]);
});

test('puede ajustar stock por local y ver el detalle del modal', function () {
    actingAs(User::factory()->create());

    $catalogs = productCatalogsForSales();
    $branchA = Branch::factory()->create([
        'name' => 'Local A',
        'is_active' => true,
    ]);
    $branchB = Branch::factory()->create([
        'name' => 'Local B',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Crema facial',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 80,
        'current_stock' => 15,
        'commission_type' => 'percent',
    ]);

    $this->postJson(route('products.movements.store', $product), [
        'stock_by_branch' => [
            $branchA->id => 8,
            $branchB->id => 7,
        ],
    ])->assertOk();

    expect((float) $product->refresh()->current_stock)->toBe(15.0);

    $this->assertDatabaseHas('product_branch_stocks', [
        'product_id' => $product->id,
        'branch_id' => $branchA->id,
        'current_stock' => 8,
    ]);

    $this->assertDatabaseHas('product_branch_stocks', [
        'product_id' => $product->id,
        'branch_id' => $branchB->id,
        'current_stock' => 7,
    ]);

    $this->assertDatabaseHas('product_stock_movements', [
        'product_id' => $product->id,
        'movement_type' => 'adjustment',
    ]);

    $this->getJson(route('products.movements.show', $product))
        ->assertOk()
        ->assertJsonPath('product.id', $product->id)
        ->assertJsonStructure([
            'product',
            'branches',
            'branchStocks',
            'history',
        ]);
});

test('muestra el reporte de ventas de productos y vendedores', function () {
    $role = Role::query()->create([
        'name' => 'Profesional',
        'slug' => 'professional',
        'is_system' => true,
    ]);

    $seller = User::factory()->create([
        'name' => 'Dalia Yauri',
        'role_id' => $role->id,
    ]);

    actingAs($seller);

    $catalogs = productCatalogsForSales();
    $branch = Branch::factory()->create([
        'name' => 'Local Centro',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Shampoo Reparacion Intensa Keratina 500ml',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 45,
        'current_stock' => 20,
        'commission_type' => 'percent',
        'is_active' => true,
    ]);

    $sale = ProductSale::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $seller->id,
        'sold_at' => now(),
        'total' => 450,
    ]);

    ProductSaleItem::query()->create([
        'product_sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => 45,
        'subtotal' => 450,
    ]);

    $this->get(route('products.sales.index'))
        ->assertOk()
        ->assertSee('Mayor ingreso')
        ->assertSee('Menor ingreso')
        ->assertSee('Por productos')
        ->assertSee('Por vendedor')
        ->assertSee('Dalia Yauri')
        ->assertSee('Shampoo Reparacion Intensa Keratina 500ml');
});

test('puede exportar el reporte de ventas de productos', function () {
    $seller = User::factory()->create();

    actingAs($seller);

    $catalogs = productCatalogsForSales();
    $branch = Branch::factory()->create([
        'name' => 'Local Centro',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Esmalte Gel',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 30,
        'current_stock' => 10,
        'commission_type' => 'percent',
        'is_active' => true,
    ]);

    $sale = ProductSale::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $seller->id,
        'sold_at' => now(),
        'total' => 60,
    ]);

    ProductSaleItem::query()->create([
        'product_sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 30,
        'subtotal' => 60,
    ]);

    $response = $this->get(route('products.sales.export', ['detail' => 'products']));

    $response
        ->assertOk()
        ->assertDownload()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain('Producto,Formato/Presentacion,"Unidades vendidas",Recaudacion')
        ->toContain('"Esmalte Gel",Unidad,2,60');
});

test('incluye en ventas de productos los productos vendidos desde el modulo de ventas', function () {
    $seller = User::factory()->create([
        'name' => 'Caja Principal',
    ]);

    actingAs($seller);

    $catalogs = productCatalogsForSales();
    $branch = Branch::factory()->create([
        'name' => 'Local Centro',
        'is_active' => true,
    ]);

    $product = Product::query()->create([
        'name' => 'Ampolla capilar',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 25,
        'current_stock' => 10,
        'commission_type' => 'percent',
        'is_active' => true,
    ]);

    $professional = Professional::query()->create([
        'public_name' => 'Dalia Yauri',
        'is_active' => true,
    ]);

    $sale = Sale::query()->create([
        'branch_id' => $branch->id,
        'user_id' => $seller->id,
        'sold_at' => now(),
        'status' => 'paid',
        'subtotal' => 25,
        'discount_total' => 0,
        'total' => 25,
        'paid_total' => 25,
        'change_total' => 0,
    ]);

    SaleItem::query()->create([
        'sale_id' => $sale->id,
        'item_type' => 'product',
        'product_id' => $product->id,
        'item_name' => $product->name,
        'item_detail' => 'Unidad',
        'quantity' => 1,
        'unit_price' => 25,
        'subtotal' => 25,
        'meta' => [
            'professional_id' => $professional->id,
            'professional_name' => $professional->public_name,
        ],
    ]);

    $this->get(route('products.sales.index'))
        ->assertOk()
        ->assertSee('Ampolla capilar')
        ->assertSee('Dalia Yauri');
});
