<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // 15. Month - Auto extract nama bulan berserta tahunnya (Misal "January 2026")
            if ($model->tanggal_review) {
                // Di Laravel setting default, translatedFormat memanggil locale en / id
                $model->month = Carbon::parse($model->tanggal_review)->format('F Y');
            }

            // 16. Status Automation
            if ($model->progress === 'Follow Up Customer') {
                $model->status = 'Pending';
            } elseif ($model->progress === 'Auto Reply') {
                $model->status = 'Solved';
            }

            // By (Cause_By) automation mengikuti default cause by dari master sub case.
            $defaultCauseBy = $model->category_review
                ? SubCase::query()
                    ->where('name', $model->category_review)
                    ->value('default_cause_by')
                : null;

            if ($defaultCauseBy) {
                $model->cause_by = $defaultCauseBy;
            }
        });
    }
}
