<?php

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
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
