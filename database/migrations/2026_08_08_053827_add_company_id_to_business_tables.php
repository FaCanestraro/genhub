<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables where user_id simply becomes company_id, 1:1, always resolvable (these only ever
     * get written from client-only routes, so every user_id here belongs to a real company).
     */
    private array $simpleTables = [
        'products', 'campaigns', 'actions', 'generations', 'assets', 'leads', 'tasks', 'roles',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companyIdByOwnerUserId = DB::table('companies')->pluck('id', 'user_id');

        foreach ($this->simpleTables as $table) {
            $this->convertColumn($table, 'user_id', $companyIdByOwnerUserId, nullable: false);
        }

        // settings: was unique(user_id) -> unique(company_id).
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        $this->backfill('settings', 'user_id', $companyIdByOwnerUserId);
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('company_id');
            $table->foreignId('company_id')->nullable(false)->change();
        });

        // ai_credentials: was unique([user_id, provider]) -> unique([company_id, provider]).
        Schema::table('ai_credentials', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        $this->backfill('ai_credentials', 'user_id', $companyIdByOwnerUserId);
        Schema::table('ai_credentials', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'provider']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique(['company_id', 'provider']);
            $table->foreignId('company_id')->nullable(false)->change();
        });

        // audit_logs: account_id -> company_id, but stays NULLABLE — platform-admin actions
        // (managing other admins, etc.) aren't scoped to any company.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        $this->backfill('audit_logs', 'account_id', $companyIdByOwnerUserId);
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'created_at']);
            $table->dropIndex(['account_id', 'area']);
            $table->dropConstrainedForeignId('account_id');
            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'area']);
        });

        // monthly_fee moves from users to companies — billing belongs to the company, and a
        // company can now have more than one owner.
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('monthly_fee', 10, 2)->nullable();
        });
        DB::table('users')->whereNotNull('monthly_fee')->select('id', 'monthly_fee')->orderBy('id')->each(function ($user) use ($companyIdByOwnerUserId) {
            $companyId = $companyIdByOwnerUserId[$user->id] ?? null;
            if ($companyId) {
                DB::table('companies')->where('id', $companyId)->update(['monthly_fee' => $user->monthly_fee]);
            }
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('monthly_fee');
        });
    }

    private function convertColumn(string $table, string $oldColumn, $companyIdByOwnerUserId, bool $nullable): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('company_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        $this->backfill($table, $oldColumn, $companyIdByOwnerUserId);

        Schema::table($table, function (Blueprint $blueprint) use ($oldColumn, $nullable) {
            $blueprint->dropConstrainedForeignId($oldColumn);
            if (!$nullable) {
                $blueprint->foreignId('company_id')->nullable(false)->change();
            }
        });
    }

    private function backfill(string $table, string $oldColumn, $companyIdByOwnerUserId): void
    {
        DB::table($table)->select('id', $oldColumn)->orderBy('id')->each(function ($row) use ($table, $oldColumn, $companyIdByOwnerUserId) {
            $companyId = $companyIdByOwnerUserId[$row->{$oldColumn}] ?? null;

            if ($companyId) {
                DB::table($table)->where('id', $row->id)->update(['company_id' => $companyId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new \RuntimeException('This migration reshapes account-scoping across many tables and is not reversible. Restore from a backup instead.');
    }
};
