<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->after('owner_id')->constrained('roles')->nullOnDelete();
        });

        $fullPermissions = collect(config('menus'))
            ->mapWithKeys(fn ($label, $slug) => [$slug => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true]])
            ->toArray();

        User::whereNull('owner_id')->whereNull('role_id')->each(function (User $user) use ($fullPermissions) {
            $role = Role::firstOrCreate(
                ['user_id' => $user->id, 'is_default' => true],
                ['name' => 'Administrador', 'description' => 'Acesso completo a todas as áreas do sistema.', 'permissions' => $fullPermissions]
            );
            $user->update(['role_id' => $role->id]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
