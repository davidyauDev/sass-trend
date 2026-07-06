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
            $this->pruneLocations();
            $users = $this->seedUsers($locations);
            $this->seedClients();
            $categories = $this->seedServiceCategories();

            $this->seedServices($categories, $users);
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
                'description' => 'Sede premium para coloracion, maquillaje y experiencias de salon.',
                'image_path' => null,
                'is_active' => true,
                'schedules' => $this->defaultLocationSchedule('09:00', '20:00', '18:00'),
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
            ])
            ->get()
            ->keyBy('slug');

        $definitions = [
            [
                'code' => 'ana-lucia-torres',
                'first_name' => 'Ana Lucia',
                'last_name' => 'Torres Mendoza',
                'email' => 'ana.torres@sasstrend.pe',
                'phone' => '987100201',
                'role_slug' => UserRoleCatalog::GENERAL_ADMIN,
                'locations' => [],
                'is_primary_admin' => true,
            ],
            [
                'code' => 'ricardo-paredes',
                'first_name' => 'Ricardo',
                'last_name' => 'Paredes Leon',
                'email' => 'ricardo.paredes@sasstrend.pe',
                'phone' => '987100202',
                'role_slug' => UserRoleCatalog::GENERAL_ADMIN,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'carla-medina',
                'first_name' => 'Carla',
                'last_name' => 'Medina Rojas',
                'email' => 'carla.medina@sasstrend.pe',
                'phone' => '987100203',
                'role_slug' => UserRoleCatalog::LOCATION_ADMIN,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'lucia-quispe',
                'first_name' => 'Lucia',
                'last_name' => 'Quispe Herrera',
                'email' => 'lucia.quispe@sasstrend.pe',
                'phone' => '987100204',
                'role_slug' => UserRoleCatalog::RECEPTIONIST_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'diego-salazar',
                'first_name' => 'Diego',
                'last_name' => 'Salazar Pino',
                'email' => 'diego.salazar@sasstrend.pe',
                'phone' => '987100205',
                'role_slug' => UserRoleCatalog::RECEPTIONIST_VIEWER,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'amparo-berna',
                'first_name' => 'Amparo',
                'last_name' => 'Berna',
                'email' => 'amparo.berna@sasstrend.pe',
                'phone' => '987100206',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'dorita-lopez',
                'first_name' => 'Dorita',
                'last_name' => 'Lopez',
                'email' => 'dorita.lopez@sasstrend.pe',
                'phone' => '987100207',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'marizol-leandro',
                'first_name' => 'Marizol',
                'last_name' => 'Leandro',
                'email' => 'marizol.leandro@sasstrend.pe',
                'phone' => '987100208',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'lilian-aguado',
                'first_name' => 'Lilian',
                'last_name' => 'Aguado',
                'email' => 'lilian.aguado@sasstrend.pe',
                'phone' => '987100209',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'enith-chero',
                'first_name' => 'Enith',
                'last_name' => 'Chero',
                'email' => 'enith.chero@sasstrend.pe',
                'phone' => '987100210',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'fabiola-valiente',
                'first_name' => 'Fabiola',
                'last_name' => 'Valiente',
                'email' => 'fabiola.valiente@sasstrend.pe',
                'phone' => '987100211',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'tatiana-bernal',
                'first_name' => 'Tatiana',
                'last_name' => 'Bernal',
                'email' => 'tatiana.bernal@sasstrend.pe',
                'phone' => '987100212',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
            [
                'code' => 'brigitte-ramos',
                'first_name' => 'Brigitte',
                'last_name' => 'Ramos',
                'email' => 'brigitte.ramos@sasstrend.pe',
                'phone' => '987100213',
                'role_slug' => UserRoleCatalog::STAFF_EDITOR,
                'locations' => ['miraflores'],
                'is_primary_admin' => false,
            ],
        ];

        $users = [];

        foreach ($definitions as $index => $definition) {
            $role = $roles->get($definition['role_slug']);

            if ($role === null) {
                throw new \RuntimeException("Falta el rol {$definition['role_slug']} para sembrar usuarios.");
            }

            $code = $definition['code'];
            $locationsToAssign = $definition['locations'];
            $acceptedAt = now()->subDays(45 - $index);

            $user = User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => trim($definition['first_name'].' '.$definition['last_name']),
                    'first_name' => $definition['first_name'],
                    'last_name' => $definition['last_name'],
                    'phone' => $definition['phone'],
                    'role_id' => $role->id,
                    'password' => 'password',
                    'is_active' => true,
                    'is_primary_admin' => $definition['is_primary_admin'],
                    'invited_at' => $acceptedAt->copy()->subDay(),
                    'invitation_accepted_at' => $acceptedAt,
                ],
            );

            $user->forceFill([
                'email_verified_at' => $acceptedAt,
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
            ['client_number' => 'CLI-1001', 'first_name' => 'María Fernanda', 'last_name' => 'López Huamán', 'birth_date' => '1991-05-18', 'dni' => '74251689', 'gender' => 'Femenino', 'email' => 'maria.lopez@gmail.com', 'phone' => '987300101', 'address' => 'Av. Pardo 455', 'district' => 'Miraflores', 'city' => 'Lima'],
            ['client_number' => 'CLI-1002', 'first_name' => 'Jose Antonio', 'last_name' => 'Ramirez Salazar', 'birth_date' => '1986-11-03', 'dni' => '68852147', 'gender' => 'Masculino', 'email' => 'jose.ramirez@hotmail.com', 'phone' => '987300102', 'address' => 'Av. Arequipa 1830', 'district' => 'Lince', 'city' => 'Lima'],
            ['client_number' => 'CLI-1003', 'first_name' => 'Valeria', 'last_name' => 'Paredes Rivas', 'birth_date' => '1998-02-14', 'dni' => '72134856', 'gender' => 'Femenino', 'email' => 'valeria.paredes@outlook.com', 'phone' => '987300103', 'address' => 'Calle Los Eucaliptos 210', 'district' => 'San Isidro', 'city' => 'Lima'],
            ['client_number' => 'CLI-1004', 'first_name' => 'Carlos Eduardo', 'last_name' => 'Vega Torres', 'birth_date' => '1984-07-22', 'dni' => '73491528', 'gender' => 'Masculino', 'email' => 'carlos.vega@gmail.com', 'phone' => '987300104', 'address' => 'Jr. Bolívar 314', 'district' => 'Pueblo Libre', 'city' => 'Lima'],
            ['client_number' => 'CLI-1005', 'first_name' => 'Andrea Lucia', 'last_name' => 'Castillo Peña', 'birth_date' => '1993-09-30', 'dni' => '70563149', 'gender' => 'Femenino', 'email' => 'andrea.castillo@gmail.com', 'phone' => '987300105', 'address' => 'Av. Benavides 512', 'district' => 'Santiago de Surco', 'city' => 'Lima'],
            ['client_number' => 'CLI-1006', 'first_name' => 'Camila', 'last_name' => 'Nuñez Ortega', 'birth_date' => '1996-04-27', 'dni' => '75921436', 'gender' => 'Femenino', 'email' => 'camila.nunez@gmail.com', 'phone' => '987300106', 'address' => 'Av. La Molina 890', 'district' => 'La Molina', 'city' => 'Lima'],
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
            ['name' => 'Tratamientos capilares', 'slug' => 'tratamientos-capilares'],
            ['name' => 'Cortes y peinados', 'slug' => 'cortes-y-peinados'],
            ['name' => 'Manicure y pedicure', 'slug' => 'manicure-y-pedicure'],
            ['name' => 'Coloracion y mechas', 'slug' => 'coloracion-y-mechas'],
            ['name' => 'Pestanas y maquillaje', 'slug' => 'pestanas-y-maquillaje'],
            ['name' => 'Depilacion y cejas', 'slug' => 'depilacion-y-cejas'],
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
     */
    private function seedServices(array $categories, array $users): void
    {
        $definitions = [
            ['code' => 'keratinas', 'service_category_slug' => 'tratamientos-capilares', 'name' => 'Keratinas', 'price' => 280.00, 'duration_minutes' => 180, 'description' => 'Alisado y nutricion capilar con acabado brillante y control de frizz.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 80.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => true, 'professionals' => ['amparo-berna', 'dorita-lopez', 'marizol-leandro', 'tatiana-bernal']],
            ['code' => 'tratamientos-cabello', 'service_category_slug' => 'tratamientos-capilares', 'name' => 'Tratamientos de cabello', 'price' => 160.00, 'duration_minutes' => 90, 'description' => 'Hidratacion, reparacion y nutricion profunda segun diagnostico capilar.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['amparo-berna', 'marizol-leandro', 'lilian-aguado', 'enith-chero']],
            ['code' => 'botox-capilares', 'service_category_slug' => 'tratamientos-capilares', 'name' => 'Bóttox capilares', 'price' => 240.00, 'duration_minutes' => 150, 'description' => 'Recuperacion intensiva para cabellos maltratados con sellado termico.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 70.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => true, 'professionals' => ['dorita-lopez', 'marizol-leandro', 'tatiana-bernal']],
            ['code' => 'cortes-dama-caballeros', 'service_category_slug' => 'cortes-y-peinados', 'name' => 'Cortes de dama y caballeros', 'price' => 65.00, 'duration_minutes' => 45, 'description' => 'Corte personalizado con acabado y asesoria segun estilo.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['amparo-berna', 'dorita-lopez', 'marizol-leandro', 'lilian-aguado', 'enith-chero']],
            ['code' => 'peinados', 'service_category_slug' => 'cortes-y-peinados', 'name' => 'Peinados', 'price' => 95.00, 'duration_minutes' => 60, 'description' => 'Peinado social, brushing o styling final segun ocasion.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['amparo-berna', 'tatiana-bernal', 'brigitte-ramos']],
            ['code' => 'manicure-clasicas', 'service_category_slug' => 'manicure-y-pedicure', 'name' => 'Manicure clásicas', 'price' => 35.00, 'duration_minutes' => 45, 'description' => 'Limpieza, limado, cuticulas y esmaltado clasico.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['lilian-aguado', 'enith-chero', 'fabiola-valiente', 'brigitte-ramos']],
            ['code' => 'manicure-infinity-shade', 'service_category_slug' => 'manicure-y-pedicure', 'name' => 'Manicure Infinity Shade', 'price' => 55.00, 'duration_minutes' => 50, 'description' => 'Manicure con acabado duradero y tonos de alta cobertura.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['lilian-aguado', 'fabiola-valiente']],
            ['code' => 'color-gel', 'service_category_slug' => 'manicure-y-pedicure', 'name' => 'Color gel', 'price' => 45.00, 'duration_minutes' => 30, 'description' => 'Aplicacion de gel color con curado y brillo prolongado.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'fabiola-valiente', 'brigitte-ramos']],
            ['code' => 'linea-opi', 'service_category_slug' => 'manicure-y-pedicure', 'name' => 'En la línea de OPI', 'price' => 60.00, 'duration_minutes' => 55, 'description' => 'Servicio premium con esmaltes y acabados de la linea OPI.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['lilian-aguado', 'fabiola-valiente']],
            ['code' => 'pedicure', 'service_category_slug' => 'manicure-y-pedicure', 'name' => 'Pedicure', 'price' => 50.00, 'duration_minutes' => 60, 'description' => 'Pedicure completa con limpieza, exfoliacion y esmaltado.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'brigitte-ramos', 'fabiola-valiente']],
            ['code' => 'mechas', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Mechas', 'price' => 210.00, 'duration_minutes' => 150, 'description' => 'Iluminacion parcial o total con tecnica personalizada.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 60.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => true, 'professionals' => ['amparo-berna', 'dorita-lopez', 'marizol-leandro']],
            ['code' => 'tinte-color-entero', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Tinte color entero', 'price' => 170.00, 'duration_minutes' => 120, 'description' => 'Aplicacion global de color con cobertura uniforme y brillo.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 50.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['amparo-berna', 'tatiana-bernal', 'marizol-leandro']],
            ['code' => 'tinte-raices', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Tinte raíces', 'price' => 120.00, 'duration_minutes' => 90, 'description' => 'Retoque de crecimiento para mantener uniformidad del color.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'lilian-aguado', 'tatiana-bernal']],
            ['code' => 'rayitos', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Rayitos', 'price' => 190.00, 'duration_minutes' => 140, 'description' => 'Reflejos finos para iluminar y dar dimension al cabello.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 55.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['amparo-berna', 'marizol-leandro']],
            ['code' => 'balayage', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Balayage', 'price' => 320.00, 'duration_minutes' => 180, 'description' => 'Tecnica de iluminacion degradada con acabado natural y sofisticado.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 90.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => true, 'professionals' => ['amparo-berna', 'dorita-lopez', 'tatiana-bernal']],
            ['code' => 'mechas-babylights', 'service_category_slug' => 'coloracion-y-mechas', 'name' => 'Mechas babylights', 'price' => 280.00, 'duration_minutes' => 180, 'description' => 'Iluminacion fina y sutil para un efecto natural tipo babylights.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 80.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => true, 'professionals' => ['dorita-lopez', 'marizol-leandro', 'lilian-aguado']],
            ['code' => 'pestanas-1x1', 'service_category_slug' => 'pestanas-y-maquillaje', 'name' => 'Pestañas 1x1', 'price' => 180.00, 'duration_minutes' => 120, 'description' => 'Aplicacion clasica una a una para un look natural y definido.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 50.00, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['tatiana-bernal', 'brigitte-ramos']],
            ['code' => 'pestanas-tira', 'service_category_slug' => 'pestanas-y-maquillaje', 'name' => 'Pestañas de tira', 'price' => 40.00, 'duration_minutes' => 20, 'description' => 'Colocacion rapida de pestanas de tira para eventos y maquillaje social.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['tatiana-bernal', 'fabiola-valiente', 'brigitte-ramos']],
            ['code' => 'maquillaje', 'service_category_slug' => 'pestanas-y-maquillaje', 'name' => 'Maquillaje', 'price' => 130.00, 'duration_minutes' => 75, 'description' => 'Maquillaje social o de ocasion con preparacion y sellado de piel.', 'online_payment_type' => ServicePaymentTypeCatalog::DEPOSIT_REQUIRED, 'deposit_amount' => 40.00, 'deposit_percentage' => null, 'is_home_service' => true, 'has_special_schedule' => false, 'professionals' => ['tatiana-bernal', 'fabiola-valiente']],
            ['code' => 'depilacion-hilo-cejas', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación con hilo - Cejas', 'price' => 25.00, 'duration_minutes' => 20, 'description' => 'Perfilado preciso de cejas con tecnica de hilo.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'enith-chero', 'brigitte-ramos']],
            ['code' => 'depilacion-hilo-boso', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación con hilo - Boso', 'price' => 18.00, 'duration_minutes' => 15, 'description' => 'Retiro delicado del vello del boso con tecnica de hilo.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'brigitte-ramos']],
            ['code' => 'depilacion-hilo-rostro', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación con hilo - Rostro', 'price' => 45.00, 'duration_minutes' => 35, 'description' => 'Depilacion facial completa con acabado suave y preciso.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'brigitte-ramos']],
            ['code' => 'cejas', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Cejas', 'price' => 22.00, 'duration_minutes' => 20, 'description' => 'Diseño basico y definicion de cejas.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'brigitte-ramos']],
            ['code' => 'depilacion-cera-rostro', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Rostro', 'price' => 40.00, 'duration_minutes' => 30, 'description' => 'Depilacion facial completa con cera de baja irritacion.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'fabiola-valiente']],
            ['code' => 'depilacion-cera-cejas', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Cejas', 'price' => 20.00, 'duration_minutes' => 15, 'description' => 'Perfilado rapido de cejas con cera.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'enith-chero']],
            ['code' => 'depilacion-cera-boso', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Boso', 'price' => 16.00, 'duration_minutes' => 10, 'description' => 'Depilacion de boso con cera suave de rapida aplicacion.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['dorita-lopez', 'brigitte-ramos']],
            ['code' => 'depilacion-cera-piernas-enteras', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Piernas enteras', 'price' => 75.00, 'duration_minutes' => 50, 'description' => 'Depilacion completa de piernas con cera profesional.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'fabiola-valiente']],
            ['code' => 'depilacion-cera-media-pierna', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - 1/2 piernas', 'price' => 45.00, 'duration_minutes' => 30, 'description' => 'Depilacion de media pierna con cera tibia.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'fabiola-valiente']],
            ['code' => 'depilacion-cera-bikini', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Área de bikini', 'price' => 40.00, 'duration_minutes' => 25, 'description' => 'Perfilado de bikini con protocolo higienico y cera especializada.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['fabiola-valiente', 'brigitte-ramos']],
            ['code' => 'depilacion-cera-brasilera', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Brasilera', 'price' => 55.00, 'duration_minutes' => 35, 'description' => 'Depilacion brasilera con tecnica de confort y acabado prolijo.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['fabiola-valiente', 'brigitte-ramos']],
            ['code' => 'depilacion-cera-ingle', 'service_category_slug' => 'depilacion-y-cejas', 'name' => 'Depilación de cera - Ingle', 'price' => 30.00, 'duration_minutes' => 20, 'description' => 'Depilacion de ingle con cera de uso profesional.', 'online_payment_type' => ServicePaymentTypeCatalog::ALLOWED, 'deposit_amount' => null, 'deposit_percentage' => null, 'is_home_service' => false, 'has_special_schedule' => false, 'professionals' => ['enith-chero', 'brigitte-ramos']],
        ];

        foreach ($definitions as $definition) {
            $category = $categories[$definition['service_category_slug']] ?? null;

            if ($category === null) {
                throw new \RuntimeException("Falta la categoria {$definition['service_category_slug']} para sembrar servicios.");
            }

            $professionals = $definition['professionals'];
            unset($definition['code'], $definition['service_category_slug'], $definition['professionals']);

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
                    'is_active' => true,
                    'is_bookable_online' => true,
                    'description' => $definition['description'],
                    'image_path' => null,
                    'online_payment_type' => $definition['online_payment_type'],
                    'deposit_amount' => $definition['deposit_amount'],
                    'deposit_percentage' => $definition['deposit_percentage'],
                    'is_video_conference' => false,
                    'is_home_service' => $definition['is_home_service'],
                    'has_special_schedule' => $definition['has_special_schedule'],
                ],
            );

            $this->seedServiceSchedules($service, $definition['has_special_schedule']
                ? $this->defaultServiceSchedule()
                : []);
            $this->syncServiceProfessionals($service, $professionals, $users);
        }
    }

    /**
     * @param  array<string, User>  $users
     * @param  array<string, Location>  $locations
     */
    private function seedLocationAssignments(array $users, array $locations): void
    {
        $assignments = [
            'ana.torres@sasstrend.pe' => [],
            'ricardo.paredes@sasstrend.pe' => ['miraflores'],
            'carla.medina@sasstrend.pe' => ['miraflores'],
            'lucia.quispe@sasstrend.pe' => ['miraflores'],
            'diego.salazar@sasstrend.pe' => ['miraflores'],
            'amparo.berna@sasstrend.pe' => ['miraflores'],
            'dorita.lopez@sasstrend.pe' => ['miraflores'],
            'marizol.leandro@sasstrend.pe' => ['miraflores'],
            'lilian.aguado@sasstrend.pe' => ['miraflores'],
            'enith.chero@sasstrend.pe' => ['miraflores'],
            'fabiola.valiente@sasstrend.pe' => ['miraflores'],
            'tatiana.bernal@sasstrend.pe' => ['miraflores'],
            'brigitte.ramos@sasstrend.pe' => ['miraflores'],
        ];

        foreach ($assignments as $email => $locationCodes) {
            $user = $users[$email] ?? null;

            if ($user === null) {
                continue;
            }

            $this->syncUserLocations($user, $locationCodes, $locations);
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
     * @param  list<string>  $locationCodes
     * @param  array<string, Location>  $locations
     */
    private function syncUserLocations(User $user, array $locationCodes, array $locations): void
    {
        $locationIds = collect($locationCodes)
            ->map(fn (string $code): ?int => $locations[$code]->id ?? null)
            ->filter(fn (?int $id): bool => $id !== null)
            ->all();

        $user->locations()->sync($locationIds);
    }

    private function pruneLocations(): void
    {
        $miraflores = Location::query()->where('name', 'SASS Trend Miraflores')->first();

        if (! $miraflores instanceof Location) {
            return;
        }

        Location::query()
            ->whereKeyNot($miraflores->id)
            ->delete();
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
            ->map(fn (string $code): ?int => isset($users[$code]) ? $users[$code]->id : null)
            ->filter()
            ->values()
            ->all();

        $service->professionals()->sync($professionalIds);
    }

    /**
     * @return array<int, array{is_open: bool, opens_at: ?string, closes_at: ?string}>
     */
    private function defaultLocationSchedule(string $weekdayStart, string $weekdayEnd, string $saturdayEnd): array
    {
        return [
            1 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $weekdayEnd],
            2 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $weekdayEnd],
            3 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $weekdayEnd],
            4 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $weekdayEnd],
            5 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $weekdayEnd],
            6 => ['is_open' => true, 'opens_at' => $weekdayStart, 'closes_at' => $saturdayEnd],
            7 => ['is_open' => false, 'opens_at' => null, 'closes_at' => null],
        ];
    }

    /**
     * @return array<int, array{is_active: bool, starts_at: ?string, ends_at: ?string}>
     */
    private function defaultServiceSchedule(): array
    {
        return [
            1 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
            2 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
            3 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
            4 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
            5 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '18:00'],
            6 => ['is_active' => true, 'starts_at' => '09:00', 'ends_at' => '14:00'],
            7 => ['is_active' => false, 'starts_at' => null, 'ends_at' => null],
        ];
    }
}
