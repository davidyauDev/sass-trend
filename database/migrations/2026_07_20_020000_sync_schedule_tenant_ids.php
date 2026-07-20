<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE location_schedules
            SET tenant_id = (
                SELECT locations.tenant_id
                FROM locations
                WHERE locations.id = location_schedules.location_id
            )
            WHERE EXISTS (
                SELECT 1
                FROM locations
                WHERE locations.id = location_schedules.location_id
                    AND locations.tenant_id IS NOT NULL
            )
        SQL);

        DB::statement(<<<'SQL'
            UPDATE service_schedules
            SET tenant_id = (
                SELECT services.tenant_id
                FROM services
                WHERE services.id = service_schedules.service_id
            )
            WHERE EXISTS (
                SELECT 1
                FROM services
                WHERE services.id = service_schedules.service_id
                    AND services.tenant_id IS NOT NULL
            )
        SQL);
    }

    public function down(): void
    {
        // Tenant ownership cannot be safely reverted.
    }
};
