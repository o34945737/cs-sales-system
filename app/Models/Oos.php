<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oos extends Model
{
    use HasFactory;

    protected $table = 'oos';
    protected $fillable = [
        'tanggal_input', 'brand', 'platform', 'cs_name', 'order_id',
        'product_name', 'sku', 'reason', 'solusi', 'note_detail_varian',
        'update_cs', 'tanggal_blast', 'feedback_customers', 'month',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Month
            if ($model->tanggal_input) {
                // Diisikan format: Month Year (contoh: January 2026)
                $model->month = Carbon::parse($model->tanggal_input)->format('F Y');
            }
        });
    }
}
