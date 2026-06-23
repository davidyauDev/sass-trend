<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('owner_name');
            $table->string('owner_email')->index();
            $table->string('plan')->default('basic')->index();
            $table->string('status')->default('pending')->index();
            $table->text('provisioning_error')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->timestamp('suspended_at')->nullable();

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
