<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['company_id', 'data'];

    protected $casts = ['data' => 'array'];

    public function company() { return $this->belongsTo(Company::class); }
}
