<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->foreignId('primary_location_id')
                ->nullable()
                ->after('id')
                ->constrained('locations')
                ->nullOnDelete();
            $table->json('gallery_paths')->nullable()->after('hero_image_path');
            $table->json('amenities')->nullable()->after('description');
            $table->json('highlights')->nullable()->after('amenities');
            $table->text('directions')->nullable()->after('highlights');
            $table->boolean('instant_confirmation')->default(true)->after('booking_intro');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropForeign(['primary_location_id']);
            $table->dropColumn([
                'primary_location_id',
                'gallery_paths',
                'amenities',
                'highlights',
                'directions',
                'instant_confirmation',
            ]);
        });
    }
};
