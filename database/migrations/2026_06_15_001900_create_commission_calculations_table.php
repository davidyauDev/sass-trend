<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_commission_id')->constrained('professional_commissions')->cascadeOnDelete();
            $table->foreignId('commission_rule_id')->nullable()->constrained('commission_rules')->nullOnDelete();
            $table->json('rule_snapshot')->nullable();
            $table->json('formula_snapshot')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_calculations');
    }
};
