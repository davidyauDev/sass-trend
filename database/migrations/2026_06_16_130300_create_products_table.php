<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('barcode')->nullable();
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
            $table->decimal('public_sale_price', 10, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->decimal('purchase_cost', 10, 2)->default(0);
            $table->decimal('internal_sale_price', 10, 2)->default(0);
            $table->decimal('sale_commission', 10, 2)->default(0);
            $table->enum('commission_type', ['percent', 'amount'])->default('percent');
            $table->boolean('includes_tax')->default(false);
            $table->text('description')->nullable();
            $table->boolean('stock_alarm_enabled')->default(false);
            $table->decimal('stock_alarm_limit', 10, 2)->nullable();
            $table->text('stock_alarm_emails')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
