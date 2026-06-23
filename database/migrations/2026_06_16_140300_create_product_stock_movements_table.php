<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('product_sale_id')->nullable()->constrained('product_sales')->nullOnDelete();
            $table->foreignId('product_sale_item_id')->nullable()->constrained('product_sale_items')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('movement_type', ['sale', 'adjustment', 'manual', 'initial'])->default('manual');
            $table->decimal('previous_stock', 10, 2);
            $table->decimal('quantity_delta', 10, 2);
            $table->decimal('new_stock', 10, 2);
            $table->string('reason')->nullable();
            $table->text('comment')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
            $table->index(['branch_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stock_movements');
    }
};
