<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->string('site_name')->nullable()->after('name');
            $table->string('tagline')->nullable()->after('site_name');
            $table->string('logo_path')->nullable()->after('image_path');
            $table->string('hero_image_path')->nullable()->after('logo_path');
            $table->string('primary_color', 20)->default('#4b3626')->after('hero_image_path');
            $table->string('contact_phone', 50)->nullable()->after('primary_color');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('whatsapp_phone', 50)->nullable()->after('contact_email');
            $table->string('instagram_url')->nullable()->after('whatsapp_phone');
            $table->string('facebook_url')->nullable()->after('instagram_url');
            $table->string('tiktok_url')->nullable()->after('facebook_url');
            $table->string('booking_button_label')->default('Reservar ahora')->after('tiktok_url');
            $table->text('booking_intro')->nullable()->after('booking_button_label');
        });
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table): void {
            $table->dropColumn([
                'site_name',
                'tagline',
                'logo_path',
                'hero_image_path',
                'primary_color',
                'contact_phone',
                'contact_email',
                'whatsapp_phone',
                'instagram_url',
                'facebook_url',
                'tiktok_url',
                'booking_button_label',
                'booking_intro',
            ]);
        });
    }
};
