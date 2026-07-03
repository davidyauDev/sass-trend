<?php

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function inventoryCatalogs(): array
{
    return [
        'brand' => ProductBrand::query()->create(['name' => 'Loreal']),
        'category' => ProductCategory::query()->create(['name' => 'Cuidado capilar']),
        'presentation' => ProductPresentation::query()->create(['name' => 'Unidad']),
    ];
}

test('puede ver el inventario de productos', function () {
    actingAs(User::factory()->create());

    $catalogs = inventoryCatalogs();

    Product::query()->create([
        'name' => 'Shampoo nutritivo',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 45,
    ]);

    $response = $this->get(route('products.index'));

    $response
        ->assertOk()
        ->assertSee('Inventario')
        ->assertSee('Shampoo nutritivo');
});

test('puede filtrar productos por marca y categoria', function () {
    actingAs(User::factory()->create());

    $brandA = ProductBrand::query()->create(['name' => 'Redken']);
    $brandB = ProductBrand::query()->create(['name' => 'Loreal']);
    $categoryA = ProductCategory::query()->create(['name' => 'Capilar']);
    $categoryB = ProductCategory::query()->create(['name' => 'Skin care']);
    $presentation = ProductPresentation::query()->create(['name' => 'Unidad']);

    Product::query()->create([
        'name' => 'Shampoo Redken',
        'brand_id' => $brandA->id,
        'category_id' => $categoryA->id,
        'presentation_id' => $presentation->id,
        'public_sale_price' => 80,
    ]);

    Product::query()->create([
        'name' => 'Crema Loreal',
        'brand_id' => $brandB->id,
        'category_id' => $categoryB->id,
        'presentation_id' => $presentation->id,
        'public_sale_price' => 90,
    ]);

    $response = $this->get(route('products.index', [
        'brand_id' => $brandA->id,
        'category_id' => $categoryA->id,
    ]));

    $response
        ->assertOk()
        ->assertSee('Shampoo Redken')
        ->assertDontSee('Crema Loreal');
});

test('puede crear un producto', function () {
    actingAs(User::factory()->create());

    $catalogs = inventoryCatalogs();

    $response = $this->postJson(route('products.store'), [
        'name' => 'Crema facial',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 99.9,
        'current_stock' => 12,
        'purchase_cost' => 45,
        'internal_sale_price' => 75,
        'sale_commission' => 10,
        'commission_type' => 'percent',
        'includes_tax' => true,
        'stock_alarm_enabled' => true,
        'stock_alarm_limit' => 5,
        'stock_alarm_emails' => 'alerts@example.com, second@example.com',
        'is_active' => true,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('message', 'Producto creado correctamente.');

    $this->assertDatabaseHas('products', [
        'name' => 'Crema facial',
        'brand_id' => $catalogs['brand']->id,
        'commission_type' => 'percent',
        'stock_alarm_emails' => 'alerts@example.com, second@example.com',
    ]);
});

test('valida correos de alarma de stock', function () {
    actingAs(User::factory()->create());

    $catalogs = inventoryCatalogs();

    $response = $this->post(route('products.store'), [
        'name' => 'Producto inválido',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'commission_type' => 'percent',
        'stock_alarm_enabled' => true,
        'stock_alarm_limit' => 5,
        'stock_alarm_emails' => 'correo-no-valido',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('stock_alarm_emails');
});

test('puede actualizar y eliminar un producto', function () {
    actingAs(User::factory()->create());

    $catalogs = inventoryCatalogs();

    $product = Product::query()->create([
        'name' => 'Producto base',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'commission_type' => 'percent',
        'public_sale_price' => 20,
    ]);

    $this->putJson(route('products.update', $product), [
        'name' => 'Producto actualizado',
        'brand_id' => $catalogs['brand']->id,
        'category_id' => $catalogs['category']->id,
        'presentation_id' => $catalogs['presentation']->id,
        'public_sale_price' => 30,
        'current_stock' => 8,
        'purchase_cost' => 10,
        'internal_sale_price' => 25,
        'sale_commission' => 4,
        'commission_type' => 'amount',
        'includes_tax' => false,
        'stock_alarm_enabled' => false,
        'is_active' => false,
    ])->assertOk();

    expect($product->refresh()->name)->toBe('Producto actualizado');
    expect($product->refresh()->is_active)->toBeFalse();

    $this->deleteJson(route('products.destroy', $product))
        ->assertOk();

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('puede crear catalogos rapidos', function () {
    actingAs(User::factory()->create());

    $this->postJson(route('product-brands.store'), [
        'name' => 'María Beauty',
    ])->assertCreated();

    $this->postJson(route('product-categories.store'), [
        'name' => 'Cabello',
    ])->assertCreated();

    $this->postJson(route('product-presentations.store'), [
        'name' => 'Paquete',
    ])->assertCreated();

    $this->assertDatabaseHas('product_brands', ['name' => 'María Beauty']);
    $this->assertDatabaseHas('product_categories', ['name' => 'Cabello']);
    $this->assertDatabaseHas('product_presentations', ['name' => 'Paquete']);
});
