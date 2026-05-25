<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'user_id', 'name', 'sku', 'description', 'category',
        'price', 'url', 'images', 'attributes', 'active',
    ];

    protected $casts = [
        'images' => 'array',
        'attributes' => 'array',
        'active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
