<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->string('currency_symbol', 10)->default('S/')->after('primary_color');
            $table->string('website_url')->nullable()->after('tiktok_url');
            $table->string('youtube_url')->nullable()->after('website_url');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'currency_symbol',
                'website_url',
                'youtube_url',
            ]);
        });
    }
};
