<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_schedules', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_working')->default(false);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['professional_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_schedules');
    }
};
