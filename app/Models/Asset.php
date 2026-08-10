<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    protected $fillable = [
        'generation_id', 'company_id', 'type', 'disk', 'path',
        'mime_type', 'size', 'width', 'height', 'duration', 'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = ['url'];

    public function generation()
    {
        return $this->belongsTo(Generation::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
