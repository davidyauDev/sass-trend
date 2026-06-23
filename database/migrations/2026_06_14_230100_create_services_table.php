<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_category_id')
                ->constrained('service_categories')
                ->restrictOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_bookable_online')->default(true);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('online_payment_type')->nullable();
            $table->decimal('deposit_amount', 10, 2)->nullable();
            $table->unsignedTinyInteger('deposit_percentage')->nullable();
            $table->boolean('is_video_conference')->default(false);
            $table->boolean('is_home_service')->default(false);
            $table->boolean('has_special_schedule')->default(false);
            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
