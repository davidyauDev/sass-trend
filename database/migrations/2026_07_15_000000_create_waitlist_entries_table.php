<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->date('desired_date');
            $table->time('available_from');
            $table->time('available_until');
            $table->string('status', 20)->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'desired_date']);
            $table->index(['branch_id', 'desired_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
