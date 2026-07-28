<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = ['name', 'email', 'password', 'phone', 'owner_id', 'role_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function generations()
    {
        return $this->hasMany(Generation::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'owner_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isOwner(): bool
    {
        return $this->owner_id === null;
    }

    public function accountId(): int
    {
        return $this->owner_id ?? $this->id;
    }
}
