<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ownership and role assignment now live in company_user — a user can belong to more
        // than one company, so a single owner_id/role_id column on users no longer makes sense.
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('owner_id');
        });

        // Company ownership now lives in company_user (is_owner=true), not a single user_id.
        Schema::table('companies', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException('This migration is not reversible. Restore from a backup instead.');
    }
};
