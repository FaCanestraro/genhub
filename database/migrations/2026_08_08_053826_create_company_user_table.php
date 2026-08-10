<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_owner')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'user_id']);
        });

        // Backfill: every current account owner (users.owner_id IS NULL) becomes is_owner=true
        // on their own company; every current team member becomes a non-owner member of their
        // owner's company, carrying over whatever role_id they already had.
        $users = DB::table('users')->select('id', 'owner_id', 'role_id')->get();
        $companyIdByOwnerUserId = DB::table('companies')->pluck('id', 'user_id');

        foreach ($users as $user) {
            $ownerUserId = $user->owner_id ?? $user->id;
            $companyId = $companyIdByOwnerUserId[$ownerUserId] ?? null;

            if (!$companyId) {
                // Platform-admin-only accounts (is_client=false) never got a company row — skip.
                continue;
            }

            DB::table('company_user')->insert([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'is_owner' => $user->owner_id === null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
