<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_service_assignments', function (Blueprint $table): void {
            $table->decimal('sale_commission', 10, 2)->default(0)->after('service_id');
            $table->enum('commission_type', ['percent', 'amount'])->default('percent')->after('sale_commission');
        });
    }

    public function down(): void
    {
        Schema::table('professional_service_assignments', function (Blueprint $table): void {
            $table->dropColumn(['sale_commission', 'commission_type']);
        });
    }
};
