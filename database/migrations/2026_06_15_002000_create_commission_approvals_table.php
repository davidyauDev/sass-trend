<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('professional_commission_id')->constrained('professional_commissions')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending_review');
            $table->text('notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_approvals');
    }
};
