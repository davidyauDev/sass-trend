<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Location;
use App\Models\Professional;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductBranchStock;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use App\Models\ProductSale;
use App\Models\ProductSaleItem;
use App\Models\ProductStockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Support\Carbon;
use App\Services\Users\UserRoleCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class TenantDemoSeeder extends Seeder
{
    private const TENANT_SLUG = 'sass-trend-demo';

    private const TENANT_NAME = 'SASS Trend Demo';

    private const TENANT_OWNER_EMAIL = 'ana.torres@sasstrend.pe';

    private const TENANT_OWNER_PASSWORD = 'AnaDemo2026!';

    private const CENTRAL_ADMIN_EMAIL = 'central.admin@sasstrend-demo.pe';

    private const CENTRAL_ADMIN_PASSWORD = 'DemoTenant2026!';

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);

        $tenant = Tenant::query()->firstOrNew(['slug' => self::TENANT_SLUG]);

        if (! $tenant->exists) {
            $tenant->id = (string) Str::uuid();
        }

        $tenant->fill([
            'name' => self::TENANT_NAME,
            'owner_name' => 'Ana Lucía Torres Mendoza',
            'owner_email' => self::TENANT_OWNER_EMAIL,
            'plan' => Tenant::PLAN_BASIC,
            'status' => Tenant::STATUS_ACTIVE,
            'provisioning_error' => null,
            'provisioned_at' => now(),
            'suspended_at' => null,
        ]);

        $tenant->save();

        DB::transaction(function () use ($tenant): void {
            $this->assignTenantToNullRecords($tenant->id);
            $this->seedTenantOwner($tenant->id);
            $this->seedCentralAdmin();
            $this->seedTenantWebsiteSetting($tenant->id);
        });

        tenancy()->initialize($tenant);

        try {
            DB::transaction(function () use ($tenant): void {
                $this->call([
                    DemoDataSeeder::class,
                ]);

                $this->assignTenantToNullRecords($tenant->id);

                $this->seedTenantBranches();

                $this->assignTenantToNullRecords($tenant->id);

                $this->call([
                    CommissionDemoSeeder::class,
                ]);

                $this->assignTenantToNullRecords($tenant->id);

                $this->seedProfessionalProfiles();
                $this->seedProductCatalog();
                $this->seedProductInventory();
                $this->seedProductSales();
                $this->seedGeneralSales();
                $this->seedTenantOwner(tenant('id'));
                $this->assignTenantToNullRecords($tenant->id);
            });
        } finally {
            tenancy()->end();
        }
    }

    private function seedCentralAdmin(): void
    {
        $role = Role::query()
            ->where('slug', UserRoleCatalog::GENERAL_ADMIN)
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => self::CENTRAL_ADMIN_EMAIL],
            [
                'tenant_id' => null,
                'name' => 'Central Admin',
                'first_name' => 'Central',
                'last_name' => 'Admin',
                'phone' => null,
                'role_id' => $role->id,
                'email_verified_at' => now(),
                'password' => Hash::make(self::CENTRAL_ADMIN_PASSWORD),
                'is_active' => true,
                'is_primary_admin' => false,
                'invited_at' => now(),
                'invitation_accepted_at' => now(),
            ],
        );
    }

    private function seedTenantOwner(string $tenantId): void
    {
        $role = Role::query()
            ->where('slug', UserRoleCatalog::GENERAL_ADMIN)
            ->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => self::TENANT_OWNER_EMAIL],
            [
                'tenant_id' => $tenantId,
                'name' => 'Ana Lucía Torres Mendoza',
                'first_name' => 'Ana Lucía',
                'last_name' => 'Torres Mendoza',
                'phone' => '987100201',
                'role_id' => $role->id,
                'email_verified_at' => now(),
                'password' => Hash::make(self::TENANT_OWNER_PASSWORD),
                'is_active' => true,
                'is_primary_admin' => true,
                'invited_at' => now(),
                'invitation_accepted_at' => now(),
            ],
        );
    }

    private function seedTenantWebsiteSetting(string $tenantId): void
    {
        $updated = DB::table('website_settings')
            ->whereNull('tenant_id')
            ->update([
                'tenant_id' => $tenantId,
                'site_name' => self::TENANT_NAME,
                'tagline' => 'Reserva tratamientos premium y controla tu operación en un solo lugar.',
                'description' => 'Demo realista para gestionar agenda, clientes, ventas, inventario y comisiones.',
                'primary_color' => '#7a5c42',
                'currency_symbol' => 'S/',
                'contact_phone' => '987654321',
                'contact_email' => 'hola@sasstrend-demo.pe',
                'whatsapp_phone' => '51987654321',
                'instagram_url' => 'https://instagram.com/sasstrenddemo',
                'website_url' => 'https://sasstrend-demo.pe',
                'youtube_url' => 'https://youtube.com/@sasstrenddemo',
                'booking_button_label' => 'Reservar cita',
                'booking_intro' => 'Elige sede, servicio y profesional para confirmar tu reserva en minutos.',
                'is_active' => true,
            ]);

        if ($updated > 0) {
            return;
        }

        DB::table('website_settings')->insert([
            'tenant_id' => $tenantId,
            'site_name' => self::TENANT_NAME,
            'tagline' => 'Reserva tratamientos premium y controla tu operación en un solo lugar.',
            'description' => 'Demo realista para gestionar agenda, clientes, ventas, inventario y comisiones.',
            'logo_path' => null,
            'hero_image_path' => null,
            'primary_color' => '#7a5c42',
            'currency_symbol' => 'S/',
            'contact_phone' => '987654321',
            'contact_email' => 'hola@sasstrend-demo.pe',
            'whatsapp_phone' => '51987654321',
            'instagram_url' => 'https://instagram.com/sasstrenddemo',
            'facebook_url' => null,
            'tiktok_url' => null,
            'website_url' => 'https://sasstrend-demo.pe',
            'youtube_url' => 'https://youtube.com/@sasstrenddemo',
            'booking_button_label' => 'Reservar cita',
            'booking_intro' => 'Elige sede, servicio y profesional para confirmar tu reserva en minutos.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedTenantBranches(): void
    {
        $definitions = [
            ['name' => 'Miraflores', 'slug' => 'miraflores', 'address' => 'Av. Larco 1234, Miraflores, Lima', 'phone' => '987654321', 'email' => 'miraflores@agenda.com', 'timezone' => 'America/Lima', 'color' => 'sky', 'is_active' => true],
            ['name' => 'San Isidro', 'slug' => 'san-isidro', 'address' => 'Av. Jorge Basadre 428, San Isidro, Lima', 'phone' => '987654322', 'email' => 'sanisidro@agenda.com', 'timezone' => 'America/Lima', 'color' => 'emerald', 'is_active' => true],
            ['name' => 'Surco', 'slug' => 'surco', 'address' => 'Av. Caminos del Inca 345, Santiago de Surco, Lima', 'phone' => '987654323', 'email' => 'surco@agenda.com', 'timezone' => 'America/Lima', 'color' => 'violet', 'is_active' => true],
            ['name' => 'La Molina', 'slug' => 'la-molina', 'address' => 'Av. Raúl Ferrero 1025, La Molina, Lima', 'phone' => '987654324', 'email' => 'lamolina@agenda.com', 'timezone' => 'America/Lima', 'color' => 'amber', 'is_active' => true],
        ];

        foreach ($definitions as $definition) {
            Branch::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition,
            );
        }
    }

    private function seedProfessionalProfiles(): void
    {
        $definitions = [
            [
                'email' => 'camila.rojas@sasstrend.pe',
                'public_name' => 'Camila Rojas Silva',
                'bio' => 'Especialista en faciales y protocolos de hidratación.',
                'locations' => ['SASS Trend Miraflores', 'SASS Trend San Isidro'],
                'services' => ['Limpieza facial profunda', 'Hidratación facial express'],
            ],
            [
                'email' => 'valeria.nunez@sasstrend.pe',
                'public_name' => 'Valeria Núñez Castro',
                'bio' => 'Diseño de cejas, lifting y atención estética detallista.',
                'locations' => ['SASS Trend La Molina'],
                'services' => ['Diseño y laminado de cejas', 'Lifting de pestañas'],
            ],
            [
                'email' => 'fernando.chavez@sasstrend.pe',
                'public_name' => 'Fernando Chávez Montalvo',
                'bio' => 'Enfoque corporal, masajes y bienestar integral.',
                'locations' => ['SASS Trend Surco'],
                'services' => ['Masaje descontracturante', 'Depilación láser de axilas'],
            ],
            [
                'email' => 'sofia.ramos@sasstrend.pe',
                'public_name' => 'Sofía Ramos Castillo',
                'bio' => 'Nutrición clínica y seguimiento personalizado.',
                'locations' => ['SASS Trend San Isidro', 'SASS Trend La Molina'],
                'services' => ['Consulta nutricional integral', 'Limpieza facial profunda'],
            ],
        ];

        foreach ($definitions as $definition) {
            $user = User::query()->where('email', $definition['email'])->first();

            if (! $user instanceof User) {
                continue;
            }

            $professional = Professional::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'public_name' => $definition['public_name'],
                    'email' => $definition['email'],
                    'accepts_online_bookings' => true,
                    'has_system_access' => true,
                    'bio' => $definition['bio'],
                    'photo_path' => null,
                    'is_active' => true,
                ],
            );

            $locationIds = Location::query()
                ->whereIn('name', $definition['locations'])
                ->pluck('id')
                ->all();

            $professional->locations()->sync($locationIds);

            $serviceIds = Service::query()
                ->whereIn('name', $definition['services'])
                ->pluck('id')
                ->all();

            $professional->services()->sync($serviceIds);
        }

        DB::table('location_professional')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => tenant('id')]);

        DB::table('professional_service_assignments')
            ->whereNull('tenant_id')
            ->update(['tenant_id' => tenant('id')]);
    }

    private function seedProductCatalog(): void
    {
        $brands = [
            'Cosmética Andina',
            'Dermaluz',
        ];

        foreach ($brands as $name) {
            ProductBrand::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        $categories = [
            'Cuidado facial',
            'Bienestar corporal',
        ];

        foreach ($categories as $name) {
            ProductCategory::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        $presentations = [
            'Frasco',
            'Unidad',
        ];

        foreach ($presentations as $name) {
            ProductPresentation::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }

        $definitions = [
            ['barcode' => '7700000000011', 'name' => 'Serum hidratante 30 ml', 'brand' => 'Cosmética Andina', 'category' => 'Cuidado facial', 'presentation' => 'Frasco', 'public_sale_price' => 85, 'current_stock' => 18, 'purchase_cost' => 42, 'internal_sale_price' => 65, 'sale_commission' => 5, 'commission_type' => 'percent', 'includes_tax' => true, 'description' => 'Serum ligero para hidratación diaria y acabado luminoso.', 'stock_alarm_enabled' => true, 'stock_alarm_limit' => 6, 'stock_alarm_emails' => 'inventario@sasstrend-demo.pe'],
            ['barcode' => '7700000000028', 'name' => 'Protector solar facial SPF 50', 'brand' => 'Cosmética Andina', 'category' => 'Cuidado facial', 'presentation' => 'Frasco', 'public_sale_price' => 120, 'current_stock' => 12, 'purchase_cost' => 64, 'internal_sale_price' => 95, 'sale_commission' => 10, 'commission_type' => 'amount', 'includes_tax' => true, 'description' => 'Protección diaria de amplio espectro para piel sensible.', 'stock_alarm_enabled' => true, 'stock_alarm_limit' => 5, 'stock_alarm_emails' => 'inventario@sasstrend-demo.pe'],
            ['barcode' => '7700000000035', 'name' => 'Aceite de masaje relajante 250 ml', 'brand' => 'Dermaluz', 'category' => 'Bienestar corporal', 'presentation' => 'Frasco', 'public_sale_price' => 78, 'current_stock' => 9, 'purchase_cost' => 35, 'internal_sale_price' => 60, 'sale_commission' => 4, 'commission_type' => 'percent', 'includes_tax' => true, 'description' => 'Aceite corporal con aroma suave para tratamientos y venta retail.', 'stock_alarm_enabled' => true, 'stock_alarm_limit' => 4, 'stock_alarm_emails' => 'inventario@sasstrend-demo.pe'],
            ['barcode' => '7700000000042', 'name' => 'Mascarilla reparadora capilar', 'brand' => 'Dermaluz', 'category' => 'Bienestar corporal', 'presentation' => 'Unidad', 'public_sale_price' => 32, 'current_stock' => 20, 'purchase_cost' => 12, 'internal_sale_price' => 25, 'sale_commission' => 3, 'commission_type' => 'amount', 'includes_tax' => true, 'description' => 'Tratamiento intensivo para recuperación y brillo capilar.', 'stock_alarm_enabled' => false, 'stock_alarm_limit' => null, 'stock_alarm_emails' => null],
        ];

        foreach ($definitions as $definition) {
            Product::query()->updateOrCreate(
                ['barcode' => $definition['barcode']],
                [
                    'name' => $definition['name'],
                    'barcode' => $definition['barcode'],
                    'brand_id' => ProductBrand::query()->where('name', $definition['brand'])->value('id'),
                    'category_id' => ProductCategory::query()->where('name', $definition['category'])->value('id'),
                    'presentation_id' => ProductPresentation::query()->where('name', $definition['presentation'])->value('id'),
                    'public_sale_price' => $definition['public_sale_price'],
                    'current_stock' => $definition['current_stock'],
                    'purchase_cost' => $definition['purchase_cost'],
                    'internal_sale_price' => $definition['internal_sale_price'],
                    'sale_commission' => $definition['sale_commission'],
                    'commission_type' => $definition['commission_type'],
                    'includes_tax' => $definition['includes_tax'],
                    'description' => $definition['description'],
                    'stock_alarm_enabled' => $definition['stock_alarm_enabled'],
                    'stock_alarm_limit' => $definition['stock_alarm_limit'],
                    'stock_alarm_emails' => $definition['stock_alarm_emails'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedProductInventory(): void
    {
        $definitions = [
            ['barcode' => '7700000000011', 'branch' => 'miraflores', 'stock' => 18],
            ['barcode' => '7700000000028', 'branch' => 'san-isidro', 'stock' => 12],
            ['barcode' => '7700000000035', 'branch' => 'surco', 'stock' => 9],
            ['barcode' => '7700000000042', 'branch' => 'la-molina', 'stock' => 20],
        ];

        foreach ($definitions as $definition) {
            $productId = DB::table('products')->where('barcode', $definition['barcode'])->value('id');
            $branchId = DB::table('branches')->where('slug', $definition['branch'])->value('id');

            if (! is_numeric($productId) || ! is_numeric($branchId)) {
                continue;
            }

            DB::table('product_branch_stocks')->updateOrInsert(
                [
                    'product_id' => (int) $productId,
                    'branch_id' => (int) $branchId,
                ],
                [
                    'tenant_id' => tenant('id'),
                    'current_stock' => $definition['stock'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedProductSales(): void
    {
        $definitions = [
            ['sold_at' => Carbon::create(2026, 6, 16, 10, 0, 0), 'branch' => 'miraflores', 'user' => 'ricardo.paredes@sasstrend.pe', 'barcode' => '7700000000011', 'quantity' => 1, 'unit_price' => 85, 'notes' => 'Venta demo de dermocosmética.'],
            ['sold_at' => Carbon::create(2026, 6, 17, 3, 0, 0), 'branch' => 'san-isidro', 'user' => 'carla.medina@sasstrend.pe', 'barcode' => '7700000000028', 'quantity' => 1, 'unit_price' => 120, 'notes' => 'Compra para cabina y reventa.'],
        ];

        foreach ($definitions as $definition) {
            $product = Product::query()->withoutGlobalScopes()->where('barcode', $definition['barcode'])->first();
            $branch = Branch::query()->withoutGlobalScopes()->where('slug', $definition['branch'])->first();
            $user = User::query()->withoutGlobalScopes()->where('email', $definition['user'])->first();

            if (! $product instanceof Product || ! $branch instanceof Branch || ! $user instanceof User) {
                continue;
            }

            $startingStock = match ($definition['barcode']) {
                '7700000000011' => 18.0,
                '7700000000028' => 12.0,
                '7700000000035' => 9.0,
                '7700000000042' => 20.0,
                default => 0.0,
            };

            DB::table('product_branch_stocks')
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->update(['current_stock' => $startingStock]);

            $sale = ProductSale::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'sold_at' => $definition['sold_at'],
                ],
                [
                    'total' => $definition['quantity'] * $definition['unit_price'],
                    'notes' => $definition['notes'],
                ],
            );

            $item = ProductSaleItem::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'product_sale_id' => $sale->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $definition['quantity'],
                    'unit_price' => $definition['unit_price'],
                    'subtotal' => $definition['quantity'] * $definition['unit_price'],
                ],
            );

            $stock = DB::table('product_branch_stocks')
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->first();

            if ($stock === null) {
                continue;
            }

            $previousStock = (float) $stock->current_stock;
            $newStock = max(0, $startingStock - (float) $definition['quantity']);

            DB::table('product_branch_stocks')
                ->where('id', $stock->id)
                ->update([
                    'current_stock' => $newStock,
                    'updated_at' => now(),
                ]);

            DB::table('product_stock_movements')->updateOrInsert(
                [
                    'product_sale_id' => $sale->id,
                    'product_sale_item_id' => $item->id,
                ],
                [
                    'tenant_id' => tenant('id'),
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'user_id' => $user->id,
                    'movement_type' => 'sale',
                    'previous_stock' => $previousStock,
                    'quantity_delta' => -1 * (float) $definition['quantity'],
                    'new_stock' => $newStock,
                    'reason' => 'Venta de producto demo.',
                    'comment' => null,
                    'occurred_at' => $definition['sold_at'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    private function seedGeneralSales(): void
    {
        $definitions = [
            [
                'sale_number' => 'VTA-2001',
                'ticket_number' => '48250001',
                'branch' => 'miraflores',
                'client' => 'CLI-1001',
                'user' => 'ricardo.paredes@sasstrend.pe',
                'sold_at' => Carbon::create(2026, 6, 16, 14, 0, 0),
                'notes' => 'Cobro mixto por tratamiento y producto.',
                'items' => [
                    ['item_type' => 'service', 'service' => 'Limpieza facial profunda', 'product' => null, 'item_name' => 'Limpieza facial profunda', 'item_detail' => 'Sesión premium', 'quantity' => 1, 'unit_price' => 180],
                    ['item_type' => 'product', 'service' => null, 'product' => 'Serum hidratante 30 ml', 'item_name' => 'Serum hidratante 30 ml', 'item_detail' => 'Venta retail', 'quantity' => 1, 'unit_price' => 85],
                ],
            ],
            [
                'sale_number' => 'VTA-2002',
                'ticket_number' => '48250002',
                'branch' => 'san-isidro',
                'client' => 'CLI-1003',
                'user' => 'carla.medina@sasstrend.pe',
                'sold_at' => Carbon::create(2026, 6, 17, 9, 15, 0),
                'notes' => 'Venta de control y producto complementario.',
                'items' => [
                    ['item_type' => 'service', 'service' => 'Consulta nutricional integral', 'product' => null, 'item_name' => 'Consulta nutricional integral', 'item_detail' => 'Primera evaluación', 'quantity' => 1, 'unit_price' => 200],
                    ['item_type' => 'product', 'service' => null, 'product' => 'Protector solar facial SPF 50', 'item_name' => 'Protector solar facial SPF 50', 'item_detail' => 'Recomendado por profesional', 'quantity' => 1, 'unit_price' => 120],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $branch = Branch::query()->where('slug', $definition['branch'])->first();
            $client = Client::query()->where('client_number', $definition['client'])->first();
            $user = User::query()->where('email', $definition['user'])->first();

            if (! $branch instanceof Branch || ! $client instanceof Client || ! $user instanceof User) {
                continue;
            }

            $subtotal = collect($definition['items'])->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']);

            $sale = Sale::query()->updateOrCreate(
                ['sale_number' => $definition['sale_number']],
                [
                    'branch_id' => $branch->id,
                    'client_id' => $client->id,
                    'user_id' => $user->id,
                    'ticket_number' => $definition['ticket_number'],
                    'sold_at' => $definition['sold_at'],
                    'status' => 'paid',
                    'subtotal' => $subtotal,
                    'discount_total' => 0,
                    'total' => $subtotal,
                    'paid_total' => $subtotal,
                    'change_total' => 0,
                    'notes' => $definition['notes'],
                ],
            );

            SaleItem::query()->where('sale_id', $sale->id)->delete();
            SalePayment::query()->where('sale_id', $sale->id)->delete();

            foreach ($definition['items'] as $itemDefinition) {
                $serviceId = $itemDefinition['service'] !== null
                    ? Service::query()->where('name', $itemDefinition['service'])->value('id')
                    : null;

                $productId = $itemDefinition['product'] !== null
                    ? Product::query()->where('name', $itemDefinition['product'])->value('id')
                    : null;

                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'item_type' => $itemDefinition['item_type'],
                    'service_id' => is_int($serviceId) ? $serviceId : null,
                    'product_id' => is_int($productId) ? $productId : null,
                    'item_name' => $itemDefinition['item_name'],
                    'item_detail' => $itemDefinition['item_detail'],
                    'quantity' => $itemDefinition['quantity'],
                    'unit_price' => $itemDefinition['unit_price'],
                    'subtotal' => $itemDefinition['quantity'] * $itemDefinition['unit_price'],
                    'meta' => ['source' => 'tenant-demo-seeder'],
                ]);
            }

            SalePayment::query()->updateOrCreate(
                ['sale_id' => $sale->id, 'reference' => 'PAY-'.$definition['sale_number']],
                [
                    'method' => 'card',
                    'amount' => $subtotal,
                    'paid_at' => $definition['sold_at'],
                    'notes' => 'Pago demo registrado automáticamente.',
                ],
            );
        }
    }

    private function assignTenantToNullRecords(string $tenantId): void
    {
        $tables = [
            'users',
            'clients',
            'locations',
            'location_schedules',
            'service_categories',
            'services',
            'service_schedules',
            'branches',
            'appointment_statuses',
            'resources',
            'appointments',
            'appointment_notes',
            'appointment_payments',
            'appointment_histories',
            'commission_types',
            'commission_rules',
            'commission_formulas',
            'commission_settlements',
            'professional_commissions',
            'service_commissions',
            'product_commissions',
            'membership_commissions',
            'commission_transactions',
            'commission_calculations',
            'commission_approvals',
            'commission_payments',
            'commission_audit_logs',
            'website_settings',
            'user_permissions',
            'products',
            'product_branch_stocks',
            'product_sales',
            'product_sale_items',
            'product_stock_movements',
            'professionals',
            'professional_schedules',
            'professional_schedule_breaks',
            'professional_groups',
            'professional_group_members',
            'sales',
            'sale_items',
            'sale_payments',
            'product_brands',
            'product_categories',
            'product_presentations',
            'location_professional',
            'professional_service_assignments',
        ];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);
        }

        DB::table('users')
            ->whereNull('tenant_id')
            ->where('email', '!=', self::CENTRAL_ADMIN_EMAIL)
            ->update(['tenant_id' => $tenantId]);

        DB::table('users')
            ->where('email', self::TENANT_OWNER_EMAIL)
            ->update([
                'tenant_id' => $tenantId,
                'is_primary_admin' => true,
            ]);

        DB::table('users')
            ->where('email', self::CENTRAL_ADMIN_EMAIL)
            ->update([
                'tenant_id' => null,
                'is_primary_admin' => false,
            ]);
    }
}
