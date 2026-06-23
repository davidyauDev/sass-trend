<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('public_name');
            $table->string('email')->nullable();
            $table->boolean('accepts_online_bookings')->default(true);
            $table->boolean('has_system_access')->default(false);
            $table->text('bio')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'accepts_online_bookings']);
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('professional_group_members');
        Schema::dropIfExists('professional_service_assignments');
        Schema::dropIfExists('location_professional');
        Schema::dropIfExists('professional_schedule_breaks');
        Schema::dropIfExists('professional_schedules');
        Schema::dropIfExists('professionals');
        Schema::enableForeignKeyConstraints();
    }
};
