<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = [
        'campaign_id', 'user_id', 'type', 'platform', 'status',
        'title', 'brief', 'caption', 'hashtags', 'product_ids',
        'resolution', 'quantity', 'scheduled_at', 'published_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'product_ids' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function generations()
    {
        return $this->hasMany(Generation::class);
    }

    public function latestGeneration()
    {
        return $this->hasOne(Generation::class)->latestOfMany();
    }
}
