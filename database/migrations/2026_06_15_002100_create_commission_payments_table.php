<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_settlement_id')->constrained('commission_settlements')->cascadeOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->string('method')->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_payments');
    }
};
