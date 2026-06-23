<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_group_members', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('professional_group_id')->constrained('professional_groups')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('professionals')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['professional_group_id', 'professional_id'], 'prof_group_members_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_group_members');
    }
};
