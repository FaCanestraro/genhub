<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'name', 'sku', 'description', 'category',
        'price', 'price_discount', 'url', 'images', 'attributes', 'active',
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'active' => 'boolean',
        'price' => 'decimal:2',
        'price_discount' => 'decimal:2',
    ];

    public function getImagesAttribute($value): array
    {
        $paths = json_decode($value, true) ?? [];
        return array_map(fn($path) => str_starts_with($path, 'http')
            ? $path
            : Storage::disk('r2')->url($path), $paths);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
