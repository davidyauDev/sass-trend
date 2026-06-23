<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_service', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_service');
    }
};
