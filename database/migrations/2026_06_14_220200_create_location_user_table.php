<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['location_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_user');
    }
};
