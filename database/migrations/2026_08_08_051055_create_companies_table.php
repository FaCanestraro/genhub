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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('cnpj', 18)->nullable();
            $table->timestamps();
        });

        // Backfill: move nome_empresa/cnpj out of the settings JSON blob into their own
        // row per account, and strip those keys from the settings data going forward.
        DB::table('settings')->orderBy('id')->each(function ($setting) {
            $data = json_decode($setting->data ?? '{}', true) ?? [];

            DB::table('companies')->insert([
                'user_id' => $setting->user_id,
                'name' => $data['nome_empresa'] ?? null,
                'cnpj' => $data['cnpj'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            unset($data['nome_empresa'], $data['cnpj']);
            DB::table('settings')->where('id', $setting->id)->update(['data' => json_encode($data)]);
        });

        // Client accounts that never touched /settings yet still need a company row so the
        // admin panel can list them by querying companies directly.
        $clientIds = DB::table('users')->where('is_client', true)->whereNull('owner_id')->pluck('id');
        $existing = DB::table('companies')->pluck('user_id');

        foreach ($clientIds->diff($existing) as $id) {
            DB::table('companies')->insert(['user_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore nome_empresa/cnpj into the settings blob before the companies table (and
        // its data) is dropped, so rolling back doesn't lose that data.
        DB::table('companies')->orderBy('id')->each(function ($company) {
            $setting = DB::table('settings')->where('user_id', $company->user_id)->first();
            if (!$setting) {
                return;
            }

            $data = json_decode($setting->data ?? '{}', true) ?? [];
            $data['nome_empresa'] = $company->name;
            $data['cnpj'] = $company->cnpj;

            DB::table('settings')->where('id', $setting->id)->update(['data' => json_encode($data)]);
        });

        Schema::dropIfExists('companies');
    }
};
