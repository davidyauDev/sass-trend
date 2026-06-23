<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 40);
            $table->string('status', 40);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payments');
    }
};
