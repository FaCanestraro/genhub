<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'account_id', 'causer_id', 'area', 'action', 'status', 'description',
        'input', 'output', 'ai_model', 'duration_ms',
        'subject_type', 'subject_id', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
