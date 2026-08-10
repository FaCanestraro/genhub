<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'company_id', 'lead_id', 'titulo', 'descricao',
        'responsavel', 'prazo', 'concluida',
    ];

    protected $casts = [
        'prazo'     => 'datetime',
        'concluida' => 'boolean',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function lead() { return $this->belongsTo(Lead::class); }
}
