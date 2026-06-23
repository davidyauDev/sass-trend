<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_commission_id')->constrained('professional_commissions')->cascadeOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 14, 2);
            $table->string('reference')->nullable();
            $table->timestamp('transaction_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_transactions');
    }
};
