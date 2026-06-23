<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('service_category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->foreignId('commission_type_id')->constrained('commission_types')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedTinyInteger('priority')->default(50);
            $table->string('source_type')->nullable();
            $table->string('calculation_mode')->default('percentage');
            $table->decimal('percentage', 8, 2)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('min_revenue', 12, 2)->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->json('condition_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'priority']);
            $table->index(['source_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
