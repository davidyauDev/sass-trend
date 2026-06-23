<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return list<string>
     */
    private function tenantTables(): array
    {
        return [
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
            'schedule_blocks',
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
        ];
    }

    public function up(): void
    {
        foreach ($this->tenantTables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('tenant_id')->nullable()->after('id')->index();
            });
        }

        $defaultTenantId = DB::table('tenants')
            ->orderBy('created_at')
            ->value('id');

        if (! is_string($defaultTenantId) || $defaultTenantId === '') {
            return;
        }

        foreach ($this->tenantTables() as $table) {
            DB::table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $defaultTenantId]);
        }
    }

    public function down(): void
    {
        foreach ($this->tenantTables() as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropIndex(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
