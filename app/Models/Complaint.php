<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // 0. SKU master -> autofill product, brand, dan default value bila tersedia.
            $skuCode = $model->sku
                ? SkuCode::query()
                    ->where('sku', $model->sku)
                    ->where('is_active', true)
                    ->first(['product_name', 'brand', 'default_value_of_product'])
                : null;

            if ($skuCode) {
                if (blank($model->product_name)) {
                    $model->product_name = $skuCode->product_name;
                }

                if (blank($model->brand) && filled($skuCode->brand)) {
                    $model->brand = $skuCode->brand;
                }

                if (($model->value_of_product === null || $model->value_of_product === '') && $skuCode->default_value_of_product !== null) {
                    $model->value_of_product = $skuCode->default_value_of_product;
                }
            }

            // 1. Cycle - Auto fill
            if ($model->jam_customer_complaint) {
                // Asumsi format 'H:i' atau 'H:i:s'
                $time = Carbon::parse($model->jam_customer_complaint)->format('H:i:s');
                if ($time >= '21:00:00' || $time <= '15:00:00') {
                    $model->cycle = 'Cycle 1 (21.00 - 15.00)';
                } else {
                    $model->cycle = 'Cycle 2 (15.01 - 20.59)';
                }
            }

            // 3. Sub Cases -> Cause By Logic berbasis master data.
            $defaultCauseBy = $model->sub_case
                ? SubCase::query()
                    ->where('name', $model->sub_case)
                    ->value('default_cause_by')
                : null;

            if ($defaultCauseBy) {
                $model->cause_by = $defaultCauseBy;
            }

            // 4-5. Status dan priority mengacu ke master Last Step aktif.
            $lastStep = $model->last_step
                ? LastStep::query()
                    ->where('name', $model->last_step)
                    ->first(['status_result', 'priority_level'])
                : null;

            if ($lastStep) {
                $model->status = $lastStep->status_result ?: 'Pending';
                $model->priority = $lastStep->priority_level;
            }

            if ($model->last_step !== 'Claim Reject') {
                $model->reason_whitelist = null;
                $model->reason_late_respons = null;
            }

            if ($model->reason_whitelist !== 'Late Respons') {
                $model->reason_late_respons = null;
            }

            if ($model->step_cs_selesai !== 'YES') {
                $model->tanggal_step_cs_selesai = null;
            }

            // 7. Category Customer (Penghitungan riwayat username)
            if (empty($model->category_customer) && $model->username) {
                // Hanya jalankan logic ini bila record baru dibuat (bukan update data lama)
                if (!$model->exists) {
                    $count = self::where('username', $model->username)->count();
                    if ($count == 1) {
                        $model->category_customer = "Customer ini complaint ke 2";
                    } elseif ($count >= 2) {
                        $c = $count + 1;
                        $model->category_customer = "Customer ini complaint ke {$c}x";
                    } else {
                        $model->category_customer = null; // complaint pertama kali
                    }
                }
            }

            // 8. Riwayat OOS
            if ($model->order_id) {
                // Hubungkan ke model OOS (kalau ada match di tabel oos)
                $existsInOos = \App\Models\Oos::where('order_id', $model->order_id)->exists();
                if ($existsInOos) {
                    $model->oos = 'Ada Riwayat OOS';
                    $model->riwayat_oos = 'Ada Riwayat OOS';
                } else {
                    $model->oos = 'Tidak Ada Riwayat OOS';
                    $model->riwayat_oos = null;
                }
            }
        });
    }

    // 6. SLA - Dinamis menggunakan Accessor
    public function getSlaAttribute()
    {
        if (!$this->tanggal_complaint) return null;

        $startDate = Carbon::parse($this->tanggal_complaint);
        
        // Berhenti di tanggal update jika statusnya solved
        if ($this->status === 'Solved' && $this->tanggal_update) {
            $endDate = Carbon::parse($this->tanggal_update);
        } else {
            $endDate = Carbon::now(); // Tanggal berjalan (saat ini)
        }

        // Return dalam bentuk hari atau selisih
        return floor($startDate->diffInDays($endDate));
    }
}
