<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleReceiptController;
use App\Http\Middleware\EnsureTenantIsActive;
use App\Livewire\Administracion\Locales\Index as LocationsIndex;
use App\Livewire\Administracion\Empresa\Settings as CompanySettings;
use App\Livewire\Administracion\Profesionales\Index as ProfessionalsIndex;
use App\Livewire\Administracion\Servicios\Index as ServicesIndex;
use App\Livewire\Administracion\Tenants\Index as TenantsIndex;
use App\Livewire\Administracion\Usuarios\Index as UsersIndex;
use App\Livewire\Administracion\Comisiones\Index as CommissionsIndex;
use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Sales\Index as SalesIndex;
use App\Livewire\SitioWeb\Booking as PublicBooking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', function (): RedirectResponse {
        return redirect()->route('sales.index');
    })->name('dashboard');

    Route::get('administracion/tenants', TenantsIndex::class)->name('administracion.tenants.index');
});

Route::prefix('negocios/{tenant:slug}')
    ->middleware(['tenant.route', EnsureTenantIsActive::class])
    ->group(function (): void {
        Route::get('reservas', PublicBooking::class)->name('reservas.index');
    });

Route::middleware(['auth', 'verified', EnsureTenantIsActive::class])->group(function (): void {
    Route::get('ventas', SalesIndex::class)->name('sales.index');
    Route::get('ventas/export', [SaleReceiptController::class, 'export'])->name('sales.export');
    Route::get('ventas/{sale}/comprobante', [SaleReceiptController::class, 'show'])->whereNumber('sale')->name('sales.receipt.show');
    Route::get('clientes', ClientsIndex::class)->name('clientes.index');
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/ventas', [ProductController::class, 'salesIndex'])->name('products.sales.index');
    Route::post('products/ventas', [ProductController::class, 'salesStore'])->name('products.sales.store');
    Route::get('products/movimientos', [ProductController::class, 'movementsIndex'])->name('products.movements.index');
    Route::get('products/movimientos/{product}', [ProductController::class, 'movementShow'])->whereNumber('product')->name('products.movements.show');
    Route::post('products/movimientos/{product}', [ProductController::class, 'movementStore'])->whereNumber('product')->name('products.movements.store');
    Route::put('products/{product}', [ProductController::class, 'update'])->whereNumber('product')->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->whereNumber('product')->name('products.destroy');
    Route::post('product-brands', [ProductController::class, 'storeBrand'])->name('product-brands.store');
    Route::post('product-categories', [ProductController::class, 'storeCategory'])->name('product-categories.store');
    Route::post('product-presentations', [ProductController::class, 'storePresentation'])->name('product-presentations.store');
    Route::get('locales', LocationsIndex::class)->name('locales.index');
    Route::get('administracion/comisiones', CommissionsIndex::class)->name('administracion.comisiones.index');
    Route::get('administracion/profesionales', ProfessionalsIndex::class)->name('administracion.profesionales.index');
    Route::get('administracion/empresa', CompanySettings::class)->name('administracion.empresa');
    Route::get('administracion/sitio-web', CompanySettings::class)->name('administracion.sitio-web');
    Route::get('administracion/usuarios', UsersIndex::class)->name('administracion.usuarios.index');
    Route::get('administracion/servicios', ServicesIndex::class)->name('administracion.servicios.index');

    Route::get('comisiones', fn (): RedirectResponse => redirect()->route('administracion.comisiones.index'))
        ->name('comisiones.index');
});

require __DIR__.'/settings.php';
