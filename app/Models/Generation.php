<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generation extends Model
{
    protected $fillable = [
        'action_id', 'session_id', 'session_title', 'company_id', 'type', 'status', 'prompt',
        'result_text', 'model_used', 'metadata', 'error_message',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function action()
    {
        return $this->belongsTo(Action::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
