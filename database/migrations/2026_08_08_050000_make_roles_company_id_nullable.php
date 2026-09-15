<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'company_id')) {
            DB::statement('ALTER TABLE roles ALTER COLUMN company_id DROP NOT NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('roles', 'company_id')) {
            DB::statement('ALTER TABLE roles ALTER COLUMN company_id SET NOT NULL;');
        }
    }
};
