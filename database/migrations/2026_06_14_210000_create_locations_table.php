<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->boolean('accepts_online_bookings')->default(false);
            $table->string('secondary_phone', 50)->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index('email');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
