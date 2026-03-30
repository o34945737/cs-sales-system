<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oos extends Model
{
    use HasFactory;

    protected $table = 'oos';
    protected $guarded = ['id'];

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
