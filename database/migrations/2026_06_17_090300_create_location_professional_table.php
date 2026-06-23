<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_professional', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['location_id', 'professional_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_professional');
    }
};
