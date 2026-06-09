<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Template extends Model
{
    protected $fillable = [
        'user_id', 'title', 'prompt', 'type', 'preview_path',
    ];

    protected $appends = ['preview_url'];

    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->preview_path) return null;
        return str_starts_with($this->preview_path, 'http')
            ? $this->preview_path
            : Storage::disk('r2')->url($this->preview_path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
