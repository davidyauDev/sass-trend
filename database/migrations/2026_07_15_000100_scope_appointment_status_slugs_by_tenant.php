<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_statuses', function (Blueprint $table): void {
            $table->dropUnique('appointment_statuses_slug_unique');
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('appointment_statuses', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'slug']);
            $table->unique('slug');
        });
    }
};
