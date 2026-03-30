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

            // By (Cause_By) Automation
            if (in_array($model->category_review, ['Bad Quality Product', 'Expired'])) {
                $model->cause_by = 'BRAND';
            } elseif (in_array($model->category_review, ['Misunderstanding of the product', 'Change Mind'])) {
                $model->cause_by = 'CUSTOMER';
            } elseif ($model->category_review === 'OOS') {
                $model->cause_by = 'KAE';
            } elseif ($model->category_review === 'Promotion') {
                $model->cause_by = 'PROMO';
            }
        });
    }
}
