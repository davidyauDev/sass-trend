<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->string('method')->index();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reference')->nullable();
            $table->dateTime('paid_at')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
    }
};
