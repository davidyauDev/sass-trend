<?php

use App\Livewire\Agenda\WaitlistPanel;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\WaitlistEntry;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    actingAs(User::factory()->administratorGeneral()->create());
});

test('muestra el estado vacío de la lista de espera', function (): void {
    Livewire::test(WaitlistPanel::class)
        ->call('openPanel')
        ->assertSet('open', true)
        ->assertSee('Sin entradas en la lista de espera')
        ->assertSee('No tienes clientes en esta sección de la lista.');
});

test('agrega un cliente a la lista de espera', function (): void {
    $branch = Branch::factory()->create(['is_active' => true]);
    $client = Client::factory()->create();
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->id,
        'is_active' => true,
    ]);

    Livewire::test(WaitlistPanel::class)
        ->call('openPanel')
        ->call('openCreate')
        ->set('form.branchId', $branch->id)
        ->set('form.clientId', $client->id)
        ->set('form.serviceId', $service->id)
        ->set('form.desiredDate', now()->addDay()->toDateString())
        ->set('form.availableFrom', '09:00')
        ->set('form.availableUntil', '12:00')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('creating', false);

    expect(WaitlistEntry::query()->sole())
        ->client_id->toBe($client->id)
        ->service_id->toBe($service->id)
        ->status->toBe(WaitlistEntry::STATUS_WAITING);
});

test('envía una entrada en espera al flujo de reserva', function (): void {
    $entry = WaitlistEntry::factory()->create([
        'desired_date' => now()->addDay()->toDateString(),
    ]);

    Livewire::test(WaitlistPanel::class)
        ->call('openPanel')
        ->call('bookNow', $entry->id)
        ->assertSet('open', false)
        ->assertDispatched('agenda-book-waitlist', entryId: $entry->id);
});
