<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('item_type')->index();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('item_name');
            $table->string('item_detail')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'item_type'], 'sale_items_sale_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
