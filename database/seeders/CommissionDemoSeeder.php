<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\CommissionType;
use App\Models\ProfessionalCommission;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\Commissions\CommissionSourceCatalog;
use App\Services\Commissions\CommissionStatusCatalog;
use App\Services\Commissions\CommissionTypeCatalog;
use Illuminate\Database\Seeder;

class CommissionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $type = CommissionType::query()->withoutGlobalScopes()->updateOrCreate(
            ['slug' => CommissionTypeCatalog::PERCENTAGE],
            ['name' => 'Percentage Commission', 'calculation_basis' => 'percentage', 'is_active' => true],
        );

        $branch = Branch::query()->withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'miraflores'],
            [
                'name' => 'Miraflores',
                'address' => 'Av. Larco 1234',
                'timezone' => 'America/Lima',
                'color' => 'sky',
                'is_active' => true,
            ],
        );

        $category = ServiceCategory::query()->withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'faciales'],
            ['name' => 'Faciales', 'is_active' => true],
        );

        $service = Service::query()->withoutGlobalScopes()->firstOrCreate(
            ['name' => 'Limpieza premium'],
            [
                'service_category_id' => $category->id,
                'price' => 180,
                'duration_minutes' => 60,
                'is_active' => true,
                'is_bookable_online' => true,
                'description' => 'Servicio demo.',
                'image_path' => null,
                'online_payment_type' => 'allowed',
                'deposit_amount' => null,
                'deposit_percentage' => null,
                'is_video_conference' => false,
                'is_home_service' => false,
                'has_special_schedule' => false,
            ],
        );

        $rule = CommissionRule::query()->withoutGlobalScopes()->updateOrCreate(
            ['slug' => 'appointment-default'],
            [
                'branch_id' => $branch->id,
                'service_id' => $service->id,
                'service_category_id' => $category->id,
                'commission_type_id' => $type->id,
                'name' => 'Default appointment commission',
                'priority' => 20,
                'source_type' => CommissionSourceCatalog::APPOINTMENT,
                'calculation_mode' => 'percentage',
                'percentage' => 10,
                'fixed_amount' => null,
                'min_revenue' => null,
                'min_quantity' => null,
                'condition_json' => null,
                'is_active' => true,
            ],
        );

        $professional = User::query()->withoutGlobalScopes()->where('email', 'amparo.berna@sasstrend.pe')->first();

        if ($professional instanceof User) {
            ProfessionalCommission::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'source_type' => CommissionSourceCatalog::APPOINTMENT,
                    'source_reference' => 'DEMO-APT-001',
                    'user_id' => $professional->id,
                ],
                [
                    'branch_id' => $branch->id,
                    'commission_rule_id' => $rule->id,
                    'commission_type_id' => $type->id,
                    'status' => CommissionStatusCatalog::APPROVED,
                    'revenue_amount' => 180,
                    'cost_amount' => 90,
                    'profit_amount' => 90,
                    'commission_amount' => 18,
                    'quantity' => 1,
                    'currency' => 'PEN',
                    'approved_by' => $professional->id,
                    'approved_at' => now(),
                    'generated_at' => now(),
                    'metadata' => ['source' => 'demo'],
                ],
            );
        }
    }
}
