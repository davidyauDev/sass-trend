<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Services\Services\ServicePaymentTypeCatalog;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $locations = $this->seedLocations();
            $users = $this->seedUsers($locations);
            $this->seedClients();
            $categories = $this->seedServiceCategories();
            $services = $this->seedServices($categories, $users);

            $this->seedLocationAssignments($users, $locations);
            $this->seedCustomPermissions($users);
        });
    }

    /**
     * @return array<string, Location>
     */
    private function seedLocations(): array
    {
        $definitions = [
            [
                'code' => 'miraflores',
                'name' => 'SASS Trend Miraflores',
                'address' => 'Av. Larco 1234, Miraflores, Lima',
                'phone' => '987654321',
                'email' => 'miraflores@sasstrend.pe',
                'timezone' => 'America/Lima',
                'accepts_online_bookings' => true,
                'secondary_phone' => '014567890',
                'description' => 'Sede principal enfocada en tratamientos faciales, cejas y atención premium.',
                'image_path' => null,
                'is_active' => true,
                'schedules' => [
                    1 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                    2 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                    3 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                    4 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                    5 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '20:00'],
                    6 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '18:00'],
                    7 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '14:00'],
                ],
            ],
            [
                'code' => 'san-isidro',
                'name' => 'SASS Trend San Isidro',
                'address' => 'Av. Jorge Basadre 428, San Isidro, Lima',
                'phone' => '987654322',
                'email' => 'sanisidro@sasstrend.pe',
                'timezone' => 'America/Lima',
                'accepts_online_bookings' => true,
                'secondary_phone' => '014567891',
                'description' => 'Sucursal corporativa para agendas ejecutivas y servicios express.',
                'image_path' => null,
                'is_active' => true,
                'schedules' => [
                    1 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '21:00'],
                    2 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '21:00'],
                    3 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '21:00'],
                    4 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '21:00'],
                    5 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '21:00'],
                    6 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '18:00'],
                    7 => ['is_open' => false, 'opens_at' => null, 'closes_at' => null],
                ],
            ],
            [
                'code' => 'surco',
                'name' => 'SASS Trend Surco',
                'address' => 'Av. Caminos del Inca 345, Santiago de Surco, Lima',
                'phone' => '987654323',
                'email' => 'surco@sasstrend.pe',
                'timezone' => 'America/Lima',
                'accepts_online_bookings' => true,
                'secondary_phone' => '014567892',
                'description' => 'Sede con foco en masajes, bienestar corporal y atención de fin de semana.',
                'image_path' => null,
                'is_active' => true,
                'schedules' => [
                    1 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                    2 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                    3 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                    4 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                    5 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '19:00'],
                    6 => ['is_open' => true, 'opens_at' => '09:00', 'closes_at' => '17:00'],
                    7 => ['is_open' => false, 'opens_at' => null, 'closes_at' => null],
                ],
            ],
            [
                'code' => 'la-molina',
                'name' => 'SASS Trend La Molina',
                'address' => 'Av. Raúl Ferrero 1025, La Molina, Lima',
                'phone' => '987654324',
                'email' => 'lamolina@sasstrend.pe',
                'timezone' => 'America/Lima',
                'accepts_online_bookings' => false,
                'secondary_phone' => '014567893',
                'description' => 'Local orientado a servicios familiares, consultas y sesiones programadas.',
                'image_path' => null,
                'is_active' => true,
                'schedules' => [
                    1 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '19:00'],
                    2 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '19:00'],
                    3 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '19:00'],
                    4 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '19:00'],
                    5 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '19:00'],
                    6 => ['is_open' => true, 'opens_at' => '10:00', 'closes_at' => '15:00'],
                    7 => ['is_open' => false, 'opens_at' => null, 'closes_at' => null],
                ],
            ],
        ];

        $locations = [];

        foreach ($definitions as $definition) {
            $code = $definition['code'];
            $schedules = $definition['schedules'];

            unset($definition['code'], $definition['schedules']);

            $location = Location::query()->updateOrCreate(
                ['name' => $definition['name']],
                $definition,
            );

            $locations[$code] = $location;
            $this->seedLocationSchedules($location, $schedules);
        }

        return $locations;
    }

    /**
     * @param  array<string, Location>  $locations
     * @return array<string, User>
     */
    private function seedUsers(array $locations): array
    {
        $roles = Role::query()
            ->whereIn('slug', [
                UserRoleCatalog::GENERAL_ADMIN,
                UserRoleCatalog::LOCATION_ADMIN,
                UserRoleCatalog::RECEPTIONIST_EDITOR,
                UserRoleCatalog::RECEPTIONIST_VIEWER,
                UserRoleCatalog::STAFF_EDITOR,
                UserRoleCatalog::STAFF_VIEWER,
            ])
            ->get()
            ->keyBy('slug');

        $definitions = [
            [
                'code' => 'ana-lucia-torres',
                'first_name' => 'Ana Lucía',
                'last_name' => 'Torres Mendoza',
                'email' => 'ana.torres@sasstrend.pe',
                'phone' => '987100201',
                'role_slug' => UserRoleCatalog::GENERAL_ADMIN,
                'is_active' => true,
                'is_primary_admin' => true,
                'invited_at' => now()->subMonths(6),
                'invitation_accepted_at' => now()->subMonths(6)->addDay(),
                'email_verified_at' => now()->subMonths(6)->addDay(),
                'locations' => [],
            ],
            [
                'code' => 'ricardo-paredes',
                'first_name' => 'Ricardo',
                'last_name' => 'Paredes León',
                'email' => 'ricardo.paredes@sasstrend.pe',
                'phone' => '987100202',
                'role_slug' => UserRoleCatalog::GENERAL_ADMIN,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(5),
                'invitation_accepted_at' => now()->subMonths(5)->addDay(),
                'email_verified_at' => now()->subMonths(5)->addDay(),
                'locations' => ['miraflores', 'san-isidro'],
            ],
            [
                'code' => 'carla-medina',
                'first_name' => 'Carla',
                'last_name' => 'Medina Rojas',
                'email' => 'carla.medina@sasstrend.pe',
                'phone' => '987100203',
                'role_slug' => UserRoleCatalog::LOCATION_ADMIN,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(4),
                'invitation_accepted_at' => now()->subMonths(4)->addDay(),
                'email_verified_at' => now()->subMonths(4)->addDay(),
                'locations' => ['miraflores', 'san-isidro', 'surco', 'la-molina'],
            ],
            [
                'code' => 'lucia-quispe',
                'first_name' => 'Lucía',
                'last_name' => 'Quispe Herrera',
                'email' => 'lucia.quispe@sasstrend.pe',
                'phone' => '987100204',
                'role_slug' => UserRoleCatalog::RECEPTIONIST_EDITOR,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(3),
                'invitation_accepted_at' => now()->subMonths(3)->addDay(),
                'email_verified_at' => now()->subMonths(3)->addDay(),
                'locations' => ['miraflores', 'la-molina'],
            ],
            [
                'code' => 'diego-salazar',
                'first_name' => 'Diego',
                'last_name' => 'Salazar Pino',
                'email' => 'diego.salazar@sasstrend.pe',
                'phone' => '987100205',
                'role_slug' => UserRoleCatalog::RECEPTIONIST_VIEWER,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(2),
                'invitation_accepted_at' => now()->subMonths(2)->addDay(),
                'email_verified_at' => now()->subMonths(2)->addDay(),
                'locations' => ['san-isidro'],
            ],
            [
                'code' => 'camila-rojas',
                'first_name' => 'Camila',
                'last_name' => 'Rojas Silva',
                'email' => 'camila.rojas@sasstrend.pe',
                'phone' => '987100206',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(3),
                'invitation_accepted_at' => now()->subMonths(3)->addDay(),
                'email_verified_at' => now()->subMonths(3)->addDay(),
                'locations' => ['miraflores', 'san-isidro'],
            ],
            [
                'code' => 'kevin-torres',
                'first_name' => 'Kevin',
                'last_name' => 'Torres Valdivia',
                'email' => 'kevin.torres@sasstrend.pe',
                'phone' => '987100207',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(2),
                'invitation_accepted_at' => now()->subMonths(2)->addDay(),
                'email_verified_at' => now()->subMonths(2)->addDay(),
                'locations' => ['surco'],
            ],
            [
                'code' => 'valeria-nunez',
                'first_name' => 'Valeria',
                'last_name' => 'Núñez Castro',
                'email' => 'valeria.nunez@sasstrend.pe',
                'phone' => '987100208',
                'role_slug' => UserRoleCatalog::STAFF_VIEWER,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subMonths(1),
                'invitation_accepted_at' => now()->subMonths(1)->addDay(),
                'email_verified_at' => now()->subMonths(1)->addDay(),
                'locations' => ['la-molina'],
            ],
            [
                'code' => 'fernando-chavez',
                'first_name' => 'Fernando',
                'last_name' => 'Chávez Montalvo',
                'email' => 'fernando.chavez@sasstrend.pe',
                'phone' => '987100209',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'is_active' => false,
                'is_primary_admin' => false,
                'invited_at' => now()->subWeeks(3),
                'invitation_accepted_at' => null,
                'email_verified_at' => null,
                'locations' => ['surco', 'la-molina'],
            ],
            [
                'code' => 'sofia-ramos',
                'first_name' => 'Sofía',
                'last_name' => 'Ramos Castillo',
                'email' => 'sofia.ramos@sasstrend.pe',
                'phone' => '987100210',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now()->subWeeks(2),
                'invitation_accepted_at' => now()->subWeeks(2)->addDay(),
                'email_verified_at' => now()->subWeeks(2)->addDay(),
                'locations' => ['san-isidro', 'la-molina'],
            ],
        ];

        $users = [];

        foreach ($definitions as $definition) {
            $role = $roles->get($definition['role_slug']);

            if ($role === null) {
                throw new \RuntimeException("Falta el rol {$definition['role_slug']} para sembrar usuarios.");
            }

            $code = $definition['code'];
            $emailVerifiedAt = $definition['email_verified_at'];
            $locationsToAssign = $definition['locations'];

            unset(
                $definition['code'],
                $definition['role_slug'],
                $definition['locations'],
                $definition['email_verified_at'],
            );

            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => trim($definition['first_name'].' '.$definition['last_name']),
                    'first_name' => $definition['first_name'],
                    'last_name' => $definition['last_name'],
                    'phone' => $definition['phone'],
                    'role_id' => $role->id,
                    'password' => 'password',
                    'is_active' => $definition['is_active'],
                    'is_primary_admin' => $definition['is_primary_admin'],
                    'invited_at' => $definition['invited_at'],
                    'invitation_accepted_at' => $definition['invitation_accepted_at'],
                ],
            );

            $user->forceFill([
                'email_verified_at' => $emailVerifiedAt,
            ])->save();

            $users[$code] = $user;
            $users[$definition['email']] = $user;
            $this->syncUserLocations($user, $locationsToAssign, $locations);
        }

        return $users;
    }

    private function seedClients(): void
    {
        $definitions = [
            [
                'client_number' => 'CLI-1001',
                'first_name' => 'María Fernanda',
                'last_name' => 'López Huamán',
                'birth_date' => '1991-05-18',
                'dni' => '74251689',
                'gender' => 'Femenino',
                'email' => 'maria.lopez@gmail.com',
                'phone' => '987300101',
                'address' => 'Av. Pardo 455',
                'district' => 'Miraflores',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1002',
                'first_name' => 'José Antonio',
                'last_name' => 'Ramírez Salazar',
                'birth_date' => '1986-11-03',
                'dni' => '68852147',
                'gender' => 'Masculino',
                'email' => 'jose.ramirez@hotmail.com',
                'phone' => '987300102',
                'address' => 'Av. Arequipa 1830',
                'district' => 'Lince',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1003',
                'first_name' => 'Valeria',
                'last_name' => 'Paredes Rivas',
                'birth_date' => '1998-02-14',
                'dni' => '72134856',
                'gender' => 'Femenino',
                'email' => 'valeria.paredes@outlook.com',
                'phone' => '987300103',
                'address' => 'Calle Los Eucaliptos 210',
                'district' => 'San Isidro',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1004',
                'first_name' => 'Carlos Eduardo',
                'last_name' => 'Vega Torres',
                'birth_date' => '1984-07-22',
                'dni' => '73491528',
                'gender' => 'Masculino',
                'email' => 'carlos.vega@gmail.com',
                'phone' => '987300104',
                'address' => 'Jr. Bolívar 314',
                'district' => 'Pueblo Libre',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1005',
                'first_name' => 'Andrea Lucía',
                'last_name' => 'Castillo Peña',
                'birth_date' => '1993-09-30',
                'dni' => '70563149',
                'gender' => 'Femenino',
                'email' => 'andrea.castillo@gmail.com',
                'phone' => '987300105',
                'address' => 'Av. Benavides 512',
                'district' => 'Santiago de Surco',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1006',
                'first_name' => 'Diego Alejandro',
                'last_name' => 'Rojas Silva',
                'birth_date' => '1989-01-11',
                'dni' => '71642895',
                'gender' => 'Masculino',
                'email' => 'diego.rojas@gmail.com',
                'phone' => '987300106',
                'address' => 'Av. Primavera 721',
                'district' => 'Santiago de Surco',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1007',
                'first_name' => 'Camila',
                'last_name' => 'Núñez Ortega',
                'birth_date' => '1996-04-27',
                'dni' => '75921436',
                'gender' => 'Femenino',
                'email' => 'camila.nunez@gmail.com',
                'phone' => '987300107',
                'address' => 'Av. La Molina 890',
                'district' => 'La Molina',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1008',
                'first_name' => 'Paola',
                'last_name' => 'Herrera Castañeda',
                'birth_date' => '1990-08-08',
                'dni' => '72894561',
                'gender' => 'Femenino',
                'email' => 'paola.herrera@gmail.com',
                'phone' => '987300108',
                'address' => 'Av. Angamos Oeste 124',
                'district' => 'Miraflores',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1009',
                'first_name' => 'Luis Miguel',
                'last_name' => 'Navarro Quispe',
                'birth_date' => '1982-12-19',
                'dni' => '64782159',
                'gender' => 'Masculino',
                'email' => 'luis.navarro@icloud.com',
                'phone' => '987300109',
                'address' => 'Calle Las Camelias 178',
                'district' => 'San Borja',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1010',
                'first_name' => 'Sofía',
                'last_name' => 'Chávez Montalvo',
                'birth_date' => '1997-06-05',
                'dni' => '75316428',
                'gender' => 'Femenino',
                'email' => 'sofia.chavez@gmail.com',
                'phone' => '987300110',
                'address' => 'Jr. Domeyer 145',
                'district' => 'Barranco',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1011',
                'first_name' => 'Fernanda',
                'last_name' => 'Gutiérrez Flores',
                'birth_date' => '1994-03-16',
                'dni' => '68943215',
                'gender' => 'Femenino',
                'email' => 'fernanda.gutierrez@gmail.com',
                'phone' => '987300111',
                'address' => 'Av. República de Panamá 410',
                'district' => 'Surquillo',
                'city' => 'Lima',
            ],
            [
                'client_number' => 'CLI-1012',
                'first_name' => 'Renato',
                'last_name' => 'Salazar Mena',
                'birth_date' => '1988-10-24',
                'dni' => '71368492',
                'gender' => 'Prefiero no decirlo',
                'email' => 'renato.salazar@yahoo.com',
                'phone' => '987300112',
                'address' => 'Av. El Derby 900',
                'district' => 'Santiago de Surco',
                'city' => 'Lima',
            ],
        ];

        foreach ($definitions as $definition) {
            Client::query()->updateOrCreate(
                ['client_number' => $definition['client_number']],
                [
                    'first_name' => $definition['first_name'],
                    'last_name' => $definition['last_name'],
                    'birth_date' => $definition['birth_date'],
                    'age' => Carbon::parse($definition['birth_date'])->age,
                    'dni' => $definition['dni'],
                    'gender' => $definition['gender'],
                    'email' => $definition['email'],
                    'phone' => $definition['phone'],
                    'address' => $definition['address'],
                    'district' => $definition['district'],
                    'city' => $definition['city'],
                ],
            );
        }
    }

    /**
     * @return array<string, ServiceCategory>
     */
    private function seedServiceCategories(): array
    {
        $definitions = [
            ['name' => 'Faciales', 'slug' => 'faciales'],
            ['name' => 'Masajes', 'slug' => 'masajes'],
            ['name' => 'Corporal', 'slug' => 'corporal'],
            ['name' => 'Cejas y pestañas', 'slug' => 'cejas-y-pestanas'],
            ['name' => 'Depilación láser', 'slug' => 'depilacion-laser'],
            ['name' => 'Nutrición', 'slug' => 'nutricion'],
        ];

        $categories = [];

        foreach ($definitions as $definition) {
            $category = ServiceCategory::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'is_active' => true,
                ],
            );

            $categories[$definition['slug']] = $category;
        }

        return $categories;
    }

    /**
     * @param  array<string, ServiceCategory>  $categories
     * @param  array<string, User>  $users
     * @return array<string, Service>
     */
    private function seedServices(array $categories, array $users): array
    {
        $definitions = [
            [
                'code' => 'limpieza-facial-profunda',
                'service_category_slug' => 'faciales',
                'name' => 'Limpieza facial profunda',
                'price' => 180.00,
                'duration_minutes' => 75,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Incluye diagnóstico, exfoliación, extracción, mascarilla calmante y sellado hidratante.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED,
                'deposit_amount' => 50.00,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => true,
                'professionals' => ['camila-rojas', 'valeria-nunez', 'sofia-ramos'],
                'schedules' => [
                    1 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '19:00'],
                    2 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '19:00'],
                    3 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '19:00'],
                    4 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '19:00'],
                    5 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '19:00'],
                    6 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '15:00'],
                    7 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
                ],
            ],
            [
                'code' => 'hidratacion-facial-express',
                'service_category_slug' => 'faciales',
                'name' => 'Hidratación facial express',
                'price' => 120.00,
                'duration_minutes' => 45,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Tratamiento rápido para recuperar luminosidad e hidratación en pieles urbanas.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED,
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => false,
                'professionals' => ['camila-rojas', 'sofia-ramos'],
                'schedules' => [],
            ],
            [
                'code' => 'masaje-descontracturante',
                'service_category_slug' => 'masajes',
                'name' => 'Masaje descontracturante',
                'price' => 160.00,
                'duration_minutes' => 60,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Enfocado en cuello, espalda y hombros con presión media o profunda según necesidad.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::REQUIRED,
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => true,
                'has_special_schedule' => true,
                'professionals' => ['kevin-torres', 'fernando-chavez'],
                'schedules' => [
                    1 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '21:00'],
                    2 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '21:00'],
                    3 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '21:00'],
                    4 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '21:00'],
                    5 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '21:00'],
                    6 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '18:00'],
                    7 => ['is_active' => true, 'starts_at' => '10:00', 'ends_at' => '14:00'],
                ],
            ],
            [
                'code' => 'drenaje-linfatico',
                'service_category_slug' => 'corporal',
                'name' => 'Drenaje linfático manual',
                'price' => 140.00,
                'duration_minutes' => 50,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Sesión corporal suave para retención de líquidos y bienestar general.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED,
                'deposit_amount' => null,
                'deposit_percentage' => 20,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => true,
                'professionals' => ['fernando-chavez'],
                'schedules' => [
                    1 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    2 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    3 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    4 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    5 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    6 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '14:00'],
                    7 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
                ],
            ],
            [
                'code' => 'cejas-laminado',
                'service_category_slug' => 'cejas-y-pestanas',
                'name' => 'Diseño y laminado de cejas',
                'price' => 110.00,
                'duration_minutes' => 40,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Diseño, perfilado y laminado para cejas definidas con acabado natural.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED,
                'deposit_amount' => 30.00,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => false,
                'professionals' => ['valeria-nunez', 'camila-rojas'],
                'schedules' => [],
            ],
            [
                'code' => 'lifting-pestanas',
                'service_category_slug' => 'cejas-y-pestanas',
                'name' => 'Lifting de pestañas',
                'price' => 130.00,
                'duration_minutes' => 50,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Realza la curvatura natural de las pestañas sin extensiones.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED,
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => false,
                'professionals' => ['valeria-nunez'],
                'schedules' => [],
            ],
            [
                'code' => 'depilacion-axilas',
                'service_category_slug' => 'depilacion-laser',
                'name' => 'Depilación láser de axilas',
                'price' => 150.00,
                'duration_minutes' => 30,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Sesión puntual de mantenimiento con tecnología láser para zonas pequeñas.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::NOT_ALLOWED,
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => true,
                'professionals' => ['camila-rojas', 'fernando-chavez'],
                'schedules' => [
                    1 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
                    2 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    3 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    4 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    5 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
                    6 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '14:00'],
                    7 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
                ],
            ],
            [
                'code' => 'depilacion-piernas',
                'service_category_slug' => 'depilacion-laser',
                'name' => 'Depilación láser de piernas completas',
                'price' => 320.00,
                'duration_minutes' => 90,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Cobertura completa de piernas con planificación por sesiones.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED,
                'deposit_amount' => 80.00,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => false,
                'professionals' => ['fernando-chavez'],
                'schedules' => [],
            ],
            [
                'code' => 'consulta-nutricional',
                'service_category_slug' => 'nutricion',
                'name' => 'Consulta nutricional integral',
                'price' => 200.00,
                'duration_minutes' => 60,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Evaluación inicial, revisión de hábitos y plan alimenticio personalizado.',
                'image_path' => null,
                'online_payment_type' => ServicePaymentTypeCatalog::REQUIRED,
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => true,
                'is_home_service' => false,
                'has_special_schedule' => true,
                'professionals' => ['sofia-ramos'],
                'schedules' => [
                    1 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '18:00'],
                    2 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '18:00'],
                    3 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '18:00'],
                    4 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '18:00'],
                    5 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '18:00'],
                    6 => ['is_active' => true, 'starts_at' => '08:00', 'ends_at' => '12:00'],
                    7 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
                ],
            ],
        ];

        $services = [];

        foreach ($definitions as $definition) {
            $code = $definition['code'];
            $scheduleDefinitions = $definition['schedules'];
            $professionalCodes = $definition['professionals'];
            $category = $categories[$definition['service_category_slug']] ?? null;

            if ($category === null) {
                throw new \RuntimeException("Falta la categoría {$definition['service_category_slug']} para sembrar servicios.");
            }

            unset(
                $definition['code'],
                $definition['service_category_slug'],
                $definition['professionals'],
                $definition['schedules'],
            );

            $service = Service::query()->updateOrCreate(
                [
                    'service_category_id' => $category->id,
                    'name' => $definition['name'],
                ],
                [
                    'service_category_id' => $category->id,
                    'name' => $definition['name'],
                    'price' => $definition['price'],
                    'duration_minutes' => $definition['duration_minutes'],
                    'is_active' => $definition['is_active'],
                    'is_bookable_online' => $definition['is_bookable_online'],
                    'description' => $definition['description'],
                    'image_path' => $definition['image_path'],
                    'online_payment_type' => $definition['online_payment_type'],
                    'deposit_amount' => $definition['deposit_amount'],
                    'deposit_percentage' => $definition['deposit_percentage'],
                    'is_video_conference' => $definition['is_video_conference'],
                    'is_home_service' => $definition['is_home_service'],
                    'has_special_schedule' => $definition['has_special_schedule'],
                ],
            );

            $services[$code] = $service;
            $this->seedServiceSchedules($service, $scheduleDefinitions);
            $this->syncServiceProfessionals($service, $professionalCodes, $users);
        }

        return $services;
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Location>  $locations
     */
    private function seedLocationAssignments(array $users, array $locations): void
    {
        $assignments = [
            'ana.torres@sasstrend.pe' => [],
            'ricardo.paredes@sasstrend.pe' => ['miraflores', 'san-isidro'],
            'carla.medina@sasstrend.pe' => ['miraflores', 'san-isidro', 'surco', 'la-molina'],
            'lucia.quispe@sasstrend.pe' => ['miraflores', 'la-molina'],
            'diego.salazar@sasstrend.pe' => ['san-isidro'],
            'camila.rojas@sasstrend.pe' => ['miraflores', 'san-isidro'],
            'kevin.torres@sasstrend.pe' => ['surco'],
            'valeria.nunez@sasstrend.pe' => ['la-molina'],
            'fernando.chavez@sasstrend.pe' => ['surco', 'la-molina'],
            'sofia.ramos@sasstrend.pe' => ['san-isidro', 'la-molina'],
        ];

        foreach ($assignments as $email => $locationCodes) {
            $user = $users[$email] ?? null;

            if ($user === null) {
                continue;
            }

            $locationIds = collect($locationCodes)
                ->map(fn (string $code): int => $locations[$code]->id)
                ->all();

            $user->locations()->sync($locationIds);
        }
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedCustomPermissions(array $users): void
    {
        $permissions = Permission::query()
            ->whereIn('slug', [
                'bookings.delete',
                'bookings.update',
                'clients.create',
            ])
            ->get()
            ->keyBy('slug');

        $lucia = $users['lucia.quispe@sasstrend.pe'] ?? null;

        if ($lucia !== null) {
            $payload = [];

            if ($permission = $permissions->get('bookings.delete')) {
                $payload[$permission->id] = ['allowed' => false];
            }

            $lucia->customPermissions()->sync($payload);
        }

        $diego = $users['diego.salazar@sasstrend.pe'] ?? null;

        if ($diego !== null) {
            $payload = [];

            if ($permission = $permissions->get('bookings.update')) {
                $payload[$permission->id] = ['allowed' => true];
            }

            if ($permission = $permissions->get('clients.create')) {
                $payload[$permission->id] = ['allowed' => true];
            }

            $diego->customPermissions()->sync($payload);
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Location>  $locations
     */
    /**
     * @param  list<string>  $locationCodes
     * @param  array<string, Location>  $locations
     */
    private function syncUserLocations(User $user, array $locationCodes, array $locations): void
    {
        $locationIds = collect($locationCodes)
            ->map(fn (string $code): int => $locations[$code]->id)
            ->all();

        $user->locations()->sync($locationIds);
    }

    /**
     * @param  array<int, array{is_open: bool, opens_at: ?string, closes_at: ?string}>  $scheduleDefinitions
     */
    private function seedLocationSchedules(Location $location, array $scheduleDefinitions): void
    {
        foreach (range(1, 7) as $dayOfWeek) {
            $schedule = $scheduleDefinitions[$dayOfWeek] ?? [
                'is_open' => false,
                'opens_at' => null,
                'closes_at' => null,
            ];

            LocationSchedule::query()->updateOrCreate(
                [
                    'location_id' => $location->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'is_open' => (bool) $schedule['is_open'],
                    'opens_at' => $schedule['opens_at'],
                    'closes_at' => $schedule['closes_at'],
                ],
            );
        }
    }

    /**
     * @param  array<int, array{is_active: bool, starts_at: ?string, ends_at: ?string}>  $scheduleDefinitions
     */
    private function seedServiceSchedules(Service $service, array $scheduleDefinitions): void
    {
        if ($scheduleDefinitions === []) {
            return;
        }

        foreach (range(1, 7) as $dayOfWeek) {
            $schedule = $scheduleDefinitions[$dayOfWeek] ?? [
                'is_active' => false,
                'starts_at' => null,
                'ends_at' => null,
            ];

            ServiceSchedule::query()->updateOrCreate(
                [
                    'service_id' => $service->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'is_active' => (bool) $schedule['is_active'],
                    'starts_at' => $schedule['starts_at'],
                    'ends_at' => $schedule['ends_at'],
                ],
            );
        }
    }

    /**
     * @param  list<string>  $professionalCodes
     * @param  array<string, User>  $users
     */
    private function syncServiceProfessionals(Service $service, array $professionalCodes, array $users): void
    {
        $professionalIds = collect($professionalCodes)
            ->map(fn (string $code): int => $users[$code]->id)
            ->all();

        $service->professionals()->sync($professionalIds);
    }
}
