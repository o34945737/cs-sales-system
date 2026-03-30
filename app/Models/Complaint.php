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

            // 3. Sub Cases -> Cause By Logic
            // (Untuk SKU auto-fill product dan qty, ini bergantung pada master data SKU. 
            // Kita bisa melakukannya di logic Controller atau service). 
            if (in_array($model->sub_case, ['Bad Quality Product', 'Expired'])) {
                $model->cause_by = 'BRAND';
            } elseif (in_array($model->sub_case, ['Misunderstanding of the product', 'Change Mind'])) {
                $model->cause_by = 'CUSTOMER';
            } elseif ($model->sub_case === 'OOS') {
                $model->cause_by = 'KAE';
            } elseif ($model->sub_case === 'Promotion') {
                $model->cause_by = 'PROMO';
            }
            // Di luar kategori di atas, diisi manual sehingga tidak kita overwite.

            // 4. Status Auto Fill
            $solvedSteps = [
                'Claim Receive (10x shipping fee)', 'Claim Receive (Full)', 
                'Complaint Canceled by buyer/No Respons', 'Product has been delivered (Late Delivery)', 
                'Refund has been transferred by finance (SPF)', 'Return Refund (Full)', 
                'Return Refund (Partial)', 'Seller Win', 
                'The replacement product has been received by the buyer', 
                'Return follow-up (No further action)'
            ];

            if ($model->last_step === 'Claim Reject') {
                $model->status = 'Whitelist';
            } elseif (in_array($model->last_step, $solvedSteps)) {
                $model->status = 'Solved';
            } else {
                $model->status = 'Pending';
            }

            // 5. Priority Auto Fill
            $p_cool = [
                'Claim Receive (10x shipping fee)', 'Claim Receive (Full)', 
                'Complaint Canceled by buyer/No Respons', 'Product has been delivered (Late Delivery)', 
                'Refund has been transferred by finance (SPF)', 'Return Refund (Full)', 
                'Return Refund (Partial)', 'Seller Win'
            ];
            $p1 = ['Analysis MP (Non Late Delivery)', 'Follow Up to After Sales Team', 'Follow Up WH'];
            $p2 = ['On the way return & plan banding', 'Follow Up KAE to KAM', 'Follow Up KAE to Brand'];
            $p3 = ['Follow Up Courier (MP Non aktif)', 'On the way return & plan refund', 'Pending return & plan banding', 'Pending return & plan refund', 'Pending RGO & plan refund', 'Waiting Data From Customer'];
            $p4 = ['On the way return & plan replace', 'Pending return & plan replace'];
            $p5 = ['Analysis MP (Late Delivery)', 'Return not authorized'];
            $p6 = ['Kingdee Processing (Waiting AWB for replacement product)', 'Refund processing by finance (SPF)', 'Replacement product on the way'];
            $p7 = ['Waiting Claim', 'Waiting Money Receive'];

            if ($model->last_step === 'Claim Reject') {
                $model->priority = 'Mines';
            } elseif (in_array($model->last_step, $p_cool)) {
                $model->priority = 'Cool';
            } elseif (in_array($model->last_step, $p1)) {
                $model->priority = 'P1';
            } elseif (in_array($model->last_step, $p2)) {
                $model->priority = 'P2';
            } elseif (in_array($model->last_step, $p3)) {
                $model->priority = 'P3';
            } elseif (in_array($model->last_step, $p4)) {
                $model->priority = 'P4';
            } elseif (in_array($model->last_step, $p5)) {
                $model->priority = 'P5';
            } elseif (in_array($model->last_step, $p6)) {
                $model->priority = 'P6';
            } elseif (in_array($model->last_step, $p7)) {
                $model->priority = 'P7';
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
                    $model->riwayat_oos = 'Ada Riwayat OOS';
                } else {
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
