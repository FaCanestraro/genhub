<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCredential extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'label', 'api_key', 'capabilities', 'is_active',
    ];

    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key' => 'encrypted',
        'capabilities' => 'array',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
