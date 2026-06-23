<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_active')->default(false);
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['service_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_schedules');
    }
};
