<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_schedule_breaks', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('professional_schedule_id')->constrained('professional_schedules')->cascadeOnDelete();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_schedule_breaks');
    }
};
