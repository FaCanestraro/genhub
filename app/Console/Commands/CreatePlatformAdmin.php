<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdmin extends Command
{
    protected $signature = 'admin:create {name} {email} {password}';

    protected $description = 'Cria um usuário admin da plataforma (acesso ao painel /admin, fora do sistema de contas de cliente)';

    public function handle(): int
    {
        $validator = Validator::make($this->arguments(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $admin = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
        ]);
        // is_platform_admin/is_client are intentionally not mass-assignable (never settable from a
        // request) — set them directly here. is_client=false keeps this account fully out of the
        // client-facing system (no company), independent from the admin panel.
        $admin->forceFill(['is_platform_admin' => true, 'is_client' => false])->save();

        $this->info("Admin da plataforma criado: {$admin->email} (id {$admin->id})");

        return self::SUCCESS;
    }
}
