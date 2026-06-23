<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_formulas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('commission_rule_id')->constrained('commission_rules')->cascadeOnDelete();
            $table->string('label');
            $table->string('threshold_operator')->default('>');
            $table->decimal('threshold_value', 12, 2)->nullable();
            $table->decimal('bonus_amount', 12, 2)->nullable();
            $table->decimal('bonus_percentage', 8, 2)->nullable();
            $table->json('condition_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_formulas');
    }
};
