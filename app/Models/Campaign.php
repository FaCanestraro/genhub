<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'company_id', 'name', 'description', 'objective', 'status',
        'start_date', 'end_date', 'budget',
        'utm_source', 'utm_medium', 'utm_campaign',
        'goal_leads', 'goal_sales', 'leads_count', 'actions_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function actions()
    {
        return $this->hasMany(Action::class);
    }
}
