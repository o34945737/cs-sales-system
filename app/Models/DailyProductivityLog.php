<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductivityLog extends Model
{
    protected $fillable = [
        'agent_name',
        'date',
        'total_complaint',
        'total_bad_review',
        'total_order_tracking',
        'total_oos',
        'notes',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
