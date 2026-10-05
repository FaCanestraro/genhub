<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Publication extends Model
{
    protected $fillable = [
        'company_id', 'social_account_id', 'generation_id', 'user_id', 'asset_ids',
        'caption', 'as_story', 'status', 'external_id', 'permalink', 'error_message', 'published_at',
    ];

    protected $casts = [
        'asset_ids' => 'array',
        'as_story' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }
}
