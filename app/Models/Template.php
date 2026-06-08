<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'user_id', 'title', 'prompt', 'type', 'preview_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
