<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyProductivity extends Model
{
    protected $fillable = [
        'cs_name',
        'tanggal',
        'complaint_handled',
        'complaint_solved',
        'bad_review_handled',
        'order_tracking_handled',
        'oos_handled',
        'total_ticket',
        'notes',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
