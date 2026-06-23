<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();

            $table->index('is_internal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
    }
};
