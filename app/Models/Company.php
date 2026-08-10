<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'cnpj', 'monthly_fee'];

    protected $casts = ['monthly_fee' => 'decimal:2'];

    public function memberships()
    {
        return $this->hasMany(CompanyUser::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_user')
            ->withPivot('role_id', 'is_owner')
            ->withTimestamps();
    }

    public function owners()
    {
        return $this->users()->wherePivot('is_owner', true);
    }
}
