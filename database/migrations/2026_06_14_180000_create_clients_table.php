<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('dni', 50)->nullable()->unique();
            $table->string('gender', 50)->nullable();
            $table->string('client_number', 50)->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();

            $table->index('first_name');
            $table->index('last_name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
