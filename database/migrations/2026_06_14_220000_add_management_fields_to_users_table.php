<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('phone', 50)->nullable()->after('email');
            $table->foreignId('role_id')->nullable()->after('phone')->constrained('roles')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('role_id');
            $table->boolean('is_primary_admin')->default(false)->after('is_active');
            $table->timestamp('invited_at')->nullable()->after('is_primary_admin');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invited_at');

            $table->index('role_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropIndex(['is_active']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'is_active',
                'is_primary_admin',
                'invited_at',
                'invitation_accepted_at',
            ]);
        });
    }
};
