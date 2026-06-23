<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules')->nullOnDelete();
            $table->foreignId('commission_type_id')->nullable()->constrained('commission_types')->nullOnDelete();
            $table->foreignId('commission_settlement_id')->nullable()->constrained('commission_settlements')->nullOnDelete();
            $table->string('source_type');
            $table->string('source_reference');
            $table->string('status')->default('draft');
            $table->decimal('revenue_amount', 14, 2)->default(0);
            $table->decimal('cost_amount', 14, 2)->nullable();
            $table->decimal('profit_amount', 14, 2)->nullable();
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('currency', 3)->default('PEN');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'user_id']);
            $table->index(['source_type', 'source_reference']);
            $table->index(['status', 'commission_settlement_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_commissions');
    }
};
