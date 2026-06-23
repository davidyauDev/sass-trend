<?php

use App\Livewire\Administracion\Usuarios\Index as UsersIndex;
use App\Models\Location;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function createGeneralAdmin(array $attributes = []): User
{
    return User::factory()->administratorGeneral()->create($attributes);
}

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
    ]);
});

test('puede listar usuarios', function () {
    actingAs(createGeneralAdmin());

    User::factory()->create([
        'first_name' => 'Laura',
        'last_name' => 'Campos',
        'email' => 'laura@example.com',
    ]);

    $response = $this->get(route('administracion.usuarios.index'));

    $response
        ->assertOk()
        ->assertSee('Usuarios')
        ->assertSee('Laura')
        ->assertSee('Campos');
});

test('puede crear usuario', function () {
    Notification::fake();

    $admin = createGeneralAdmin();
    $location = Location::factory()->create();

    actingAs($admin);

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Mario')
        ->set('form.last_name', 'Soto')
        ->set('form.email', 'mario@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->set('form.role_id', Role::query()->where('slug', 'location_admin')->value('id'))
        ->set('form.location_ids', [$location->id])
        ->set('form.permission_ids', Permission::query()->whereIn('slug', ['users.view', 'locations.view'])->pluck('id')->all())
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'mario@example.com')->firstOrFail();

    expect($user->first_name)->toBe('Mario');
    expect($user->role?->slug)->toBe('location_admin');
    expect($user->locations()->count())->toBe(1);
});

test('valida campos requeridos', function () {
    actingAs(createGeneralAdmin());

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', '')
        ->set('form.last_name', '')
        ->set('form.email', '')
        ->set('form.password', '')
        ->set('form.password_confirmation', '')
        ->set('form.role_id', null)
        ->call('save')
        ->assertHasErrors([
            'form.first_name' => 'required',
            'form.last_name' => 'required',
            'form.email' => 'required',
            'form.password' => 'required',
            'form.role_id' => 'required',
        ]);
});

test('valida email unico', function () {
    actingAs(createGeneralAdmin());

    User::factory()->create([
        'email' => 'duplicado@example.com',
    ]);

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Paula')
        ->set('form.last_name', 'Nuñez')
        ->set('form.email', 'duplicado@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->set('form.role_id', Role::query()->where('slug', 'staff_editor')->value('id'))
        ->call('save')
        ->assertHasErrors([
            'form.email' => 'unique',
        ]);
});

test('puede asignar rol', function () {
    actingAs(createGeneralAdmin());

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Diego')
        ->set('form.last_name', 'Reyes')
        ->set('form.email', 'diego@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->set('form.role_id', Role::query()->where('slug', 'receptionist_editor')->value('id'))
        ->call('save')
        ->assertHasNoErrors();

    expect(User::query()->where('email', 'diego@example.com')->firstOrFail()->role?->slug)
        ->toBe('receptionist_editor');
});

test('puede asignar locales', function () {
    actingAs(createGeneralAdmin());

    $locationA = Location::factory()->create();
    $locationB = Location::factory()->create();

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Sofia')
        ->set('form.last_name', 'Quispe')
        ->set('form.email', 'sofia@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->set('form.role_id', Role::query()->where('slug', 'location_admin')->value('id'))
        ->set('form.location_ids', [$locationA->id, $locationB->id])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'sofia@example.com')->firstOrFail();

    expect($user->locations()->count())->toBe(2);
});

test('puede guardar permisos', function () {
    actingAs(createGeneralAdmin());

    Livewire::test(UsersIndex::class)
        ->call('openCreateModal')
        ->set('form.first_name', 'Claudia')
        ->set('form.last_name', 'Paz')
        ->set('form.email', 'claudia@example.com')
        ->set('form.password', 'password123')
        ->set('form.password_confirmation', 'password123')
        ->set('form.role_id', Role::query()->where('slug', 'staff_editor')->value('id'))
        ->set('form.permission_ids', Permission::query()->whereIn('slug', ['bookings.view', 'bookings.update', 'clients.view', 'users.view'])->pluck('id')->all())
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'claudia@example.com')->firstOrFail();
    $permissionId = Permission::query()->where('slug', 'users.view')->value('id');

    $this->assertDatabaseHas('user_permissions', [
        'user_id' => $user->id,
        'permission_id' => $permissionId,
        'allowed' => true,
    ]);
});

test('no permite eliminar al administrador general principal', function () {
    $admin = createGeneralAdmin([
        'is_primary_admin' => true,
    ]);

    actingAs($admin);

    Livewire::test(UsersIndex::class)
        ->call('confirmDelete', $admin->id)
        ->call('delete')
        ->assertHasErrors(['deletion']);
});

test('solo administrador general puede crear usuarios', function () {
    $user = User::factory()->create([
        'role_id' => Role::query()->where('slug', 'receptionist_viewer')->value('id'),
        'is_active' => true,
    ]);

    actingAs($user);

    $this->get(route('administracion.usuarios.index'))->assertForbidden();

    Livewire::test(UsersIndex::class)
        ->assertForbidden();
});
