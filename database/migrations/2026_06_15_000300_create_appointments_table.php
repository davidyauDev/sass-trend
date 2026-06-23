<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference_code', 40)->unique();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('resource_id')->nullable()->constrained('resources')->nullOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('appointment_status_id')->constrained('appointment_statuses')->restrictOnDelete();
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('timezone', 100)->default('America/Lima');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('PEN');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('no_show_at')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'starts_at']);
            $table->index(['professional_id', 'starts_at']);
            $table->index(['resource_id', 'starts_at']);
            $table->index(['appointment_status_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
