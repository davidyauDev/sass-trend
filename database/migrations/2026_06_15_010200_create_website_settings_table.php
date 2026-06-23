<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('primary_color', 20)->default('#4b3626');
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('whatsapp_phone', 50)->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('booking_button_label')->default('Reservar ahora');
            $table->text('booking_intro')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
