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
        Schema::table('users', function (Blueprint $table) {
            // True for anyone who owns/belongs to a client company (the pre-existing meaning of
            // owner_id === null). Platform-admin-only users created purely for /admin access
            // (no company) get this set to false so they're fully blocked from client routes,
            // regardless of the is_platform_admin flag.
            $table->boolean('is_client')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_client');
        });
    }
};
