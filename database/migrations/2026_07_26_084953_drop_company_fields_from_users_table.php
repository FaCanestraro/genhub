<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        User::query()
            ->where(function ($q) {
                $q->whereNotNull('company_name')->orWhereNotNull('cnpj');
            })
            ->each(function (User $user) {
                $setting = Setting::firstOrCreate(['user_id' => $user->id]);
                $data = $setting->data ?? [];
                if (empty($data['nome_empresa']) && !empty($user->company_name)) {
                    $data['nome_empresa'] = $user->company_name;
                }
                if (empty($data['cnpj']) && !empty($user->cnpj)) {
                    $data['cnpj'] = $user->cnpj;
                }
                $setting->update(['data' => $data]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'cnpj']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company_name')->nullable();
            $table->string('cnpj', 18)->nullable();
        });
    }
};
