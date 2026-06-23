<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_commission_id')->constrained('professional_commissions')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_label')->nullable();
            $table->string('source_reference')->nullable();
            $table->decimal('revenue_amount', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_commissions');
    }
};
