<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'nome', 'email', 'telefone',
        'status', 'fonte', 'local', 'responsavel', 'notas',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function campaign()   { return $this->belongsTo(Campaign::class); }
    public function tasks()      { return $this->hasMany(Task::class); }
    public function activities() { return $this->hasMany(LeadActivity::class); }
}
