<?php

use App\Http\Controllers\TenantAssetController;
use App\Livewire\Administracion\SitioWeb\Settings;
use App\Models\Branch;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('administrador puede actualizar la configuracion del sitio web', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Trend Belleza',
        'slug' => 'trend-belleza',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@trend.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);

    $this->actingAs(User::factory()->administratorGeneral()->create([
        'tenant_id' => $tenant->id,
    ]));

    $settings = WebsiteSetting::current();
    Storage::fake('public');

    Livewire::test(Settings::class)
        ->set('form.site_name', 'SASS Trend Booking')
        ->set('form.tagline', 'Reserva premium')
        ->set('form.primary_color', '#1f6f5f')
        ->set('form.booking_button_label', 'Agendar cita')
        ->set('form.is_active', true)
        ->call('save')
        ->call('openEditor', 'essentials')
        ->assertSet('editingSection', 'essentials')
        ->assertSee('Nombre para mostrar de la ubicación')
        ->set('form.site_name', 'SASS Trend Editado')
        ->call('saveSection')
        ->assertSet('editingSection', null)
        ->call('openEditor', 'description')
        ->assertSet('editingSection', 'description')
        ->assertSee('Cuéntanos un poco sobre este lugar.')
        ->call('closeEditor')
        ->assertSet('editingSection', null)
        ->call('openEditor', 'images')
        ->assertSet('editingSection', 'images')
        ->assertSee('Actualizar imágenes del lugar')
        ->set('form.gallery_uploads', [UploadedFile::fake()->image('salon.jpg', 1200, 800)])
        ->call('saveSection')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();

    expect($settings->refresh())
        ->site_name->toBe('SASS Trend Editado')
        ->tagline->toBe('Reserva premium')
        ->primary_color->toBe('#1f6f5f')
        ->booking_button_label->toBe('Agendar cita')
        ->gallery_paths->toHaveCount(1)
        ->is_active->toBeTrue();

    $galleryPath = $settings->gallery_paths[0];

    expect($settings->galleryUrls()[0])->toContain('/negocios/trend-belleza/archivos/website/gallery/');

    $assetResponse = app(TenantAssetController::class)($tenant, $galleryPath);

    expect($assetResponse->getStatusCode())->toBe(200);

    tenancy()->end();
});

test('perfil web guarda local principal horarios y caracteristicas', function () {
    $tenant = Tenant::query()->create([
        'name' => 'Estudio Aurora',
        'slug' => 'estudio-aurora',
        'owner_name' => 'Owner',
        'owner_email' => 'owner@aurora.pe',
        'plan' => Tenant::PLAN_BASIC,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    tenancy()->initialize($tenant);

    $this->actingAs(User::factory()->administratorGeneral()->create([
        'tenant_id' => $tenant->id,
    ]));

    $branch = Branch::query()->create([
        'name' => 'Aurora Centro',
        'slug' => 'aurora-centro',
        'address' => 'Av. Principal 123',
        'timezone' => 'America/Lima',
        'color' => 'emerald',
        'is_active' => true,
    ]);

    $location = Location::query()->create([
        'name' => 'Aurora Centro',
        'address' => 'Av. Principal 123',
        'timezone' => 'America/Lima',
        'branch_id' => $branch->id,
        'accepts_online_bookings' => true,
        'is_active' => true,
    ]);

    Livewire::test(Settings::class)
        ->set('form.site_name', 'Estudio Aurora')
        ->set('form.primary_location_id', $location->id)
        ->set('form.amenities', ['Wi-Fi', 'Pagos con tarjeta'])
        ->set('form.highlights', ['Atención personalizada'])
        ->set('form.directions', 'Segundo piso, puerta verde.')
        ->set('form.schedule.1.is_open', true)
        ->set('form.schedule.1.opens_at', '10:00')
        ->set('form.schedule.1.closes_at', '19:00')
        ->set('form.is_active', true)
        ->call('openEditor', 'location')
        ->assertSet('editingSection', 'location')
        ->assertSee('Dirección de ubicación')
        ->set('form.location_address', 'Óvalo Aurora 15073, Lima')
        ->call('saveSection')
        ->assertSet('editingSection', null)
        ->call('openEditor', 'hours')
        ->assertSet('editingSection', 'hours')
        ->assertSee('Añade tus horarios de apertura')
        ->set('form.schedule.7.is_open', false)
        ->call('saveSection')
        ->assertSet('editingSection', null)
        ->assertHasNoErrors();

    $settings = WebsiteSetting::current()->refresh();
    $monday = LocationSchedule::query()
        ->where('location_id', $location->id)
        ->where('day_of_week', 1)
        ->firstOrFail();

    expect($settings->primary_location_id)->toBe($location->id)
        ->and($settings->amenities)->toBe(['Wi-Fi', 'Pagos con tarjeta'])
        ->and($settings->highlights)->toBe(['Atención personalizada'])
        ->and($location->refresh()->address)->toBe('Óvalo Aurora 15073, Lima')
        ->and($monday->opens_at)->toStartWith('10:00')
        ->and($monday->closes_at)->toStartWith('19:00');

    tenancy()->end();
    auth()->logout();

    $this->get(route('perfil.publico', ['tenant' => $tenant]))
        ->assertOk()
        ->assertSee('Estudio Aurora');
});
