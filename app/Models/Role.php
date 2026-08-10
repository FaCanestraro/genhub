<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['company_id', 'name', 'description', 'permissions', 'is_default'];

    protected $casts = [
        'permissions' => 'array',
        'is_default'  => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
