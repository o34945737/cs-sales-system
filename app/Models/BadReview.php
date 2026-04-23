<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BadReview extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    private const PROGRESS_PENDING = 'Follow Up Customer';
    private const PROGRESS_SOLVED = 'Auto Reply';

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // 15. Month - Auto extract nama bulan berserta tahunnya (Misal "January 2026")
            if ($model->tanggal_review) {
                // Di Laravel setting default, translatedFormat memanggil locale en / id
                $model->month = Carbon::parse($model->tanggal_review)->format('F Y');
            }

            $skuCode = $model->sku
                ? SkuCode::query()
                    ->where('sku', $model->sku)
                    ->first(['product_name'])
                : null;

            if ($skuCode) {
                if (blank($model->product_name) && filled($skuCode->product_name)) {
                    $model->product_name = $skuCode->product_name;
                }
            }

            // 16. Status Automation
            if ($model->progress === self::PROGRESS_PENDING) {
                $model->status = 'Pending';
            } elseif ($model->progress === self::PROGRESS_SOLVED) {
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
