<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCredential extends Model
{
    protected $fillable = [
        'company_id', 'provider', 'label', 'api_key', 'capabilities', 'is_active',
    ];

    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key' => 'encrypted',
        'capabilities' => 'array',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
