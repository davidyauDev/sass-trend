<?php

use App\Livewire\Clients\Index as ClientsIndex;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('puede listar clientes', function () {
    $this->actingAs(User::factory()->create());

    Client::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Ruiz',
        'email' => 'ana@example.com',
    ]);

    $response = $this->get(route('clientes.index'));

    $response
        ->assertOk()
        ->assertSee('Clientes')
        ->assertSee('Ana')
        ->assertSee('Ruiz');
});

test('puede buscar clientes', function () {
    $this->actingAs(User::factory()->create());

    Client::factory()->create([
        'first_name' => 'Carlos',
        'last_name' => 'Mendoza',
        'dni' => '44556677',
    ]);

    Client::factory()->create([
        'first_name' => 'Lucia',
        'last_name' => 'Paredes',
        'dni' => '99887766',
    ]);

    Livewire::test(ClientsIndex::class)
        ->set('search', '44556677')
        ->assertSee('Carlos')
        ->assertDontSee('Lucia');
});

test('puede crear cliente desde livewire', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ClientsIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Marina')
        ->set('form.last_name', 'Salas')
        ->set('form.email', 'marina@example.com')
        ->set('form.phone', '999888777')
        ->set('form.dni', '12345678')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('clients', [
        'first_name' => 'Marina',
        'last_name' => 'Salas',
        'email' => 'marina@example.com',
        'dni' => '12345678',
    ]);
});

test('valida nombre y apellido requeridos', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ClientsIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', '')
        ->set('form.last_name', '')
        ->call('save')
        ->assertHasErrors([
            'form.first_name' => 'required',
            'form.last_name' => 'required',
        ]);
});

test('puede eliminar cliente', function () {
    $this->actingAs(User::factory()->create());

    $client = Client::factory()->create();

    Livewire::test(ClientsIndex::class)
        ->call('confirmDelete', $client->id)
        ->call('delete')
        ->assertHasNoErrors();

    expect($client->fresh())->toBeNull();
});
