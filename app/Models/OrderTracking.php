<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Month
            if ($model->tanggal_input) {
                $model->month = Carbon::parse($model->tanggal_input)->format('F Y');
            }

            // Status (Samakan dengan complaint)
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

            // Tanggal TTS (Order Date + 24 days only Lazada)
            if ($model->platform && stripos($model->platform, 'lazada') !== false && $model->tanggal_order) {
                $model->tanggal_tts = Carbon::parse($model->tanggal_order)->addDays(24)->toDateString();
            }

            // Automation Track
            if ($model->order_id) {
                // Merger jika ada di file COMPLAINT
                $existInComplaint = \App\Models\Complaint::where('order_id', $model->order_id)->exists();
                if ($existInComplaint) {
                    $model->automation_track = 'MERGER';
                } 
                // Untuk RGO dan Jet track, dikarenakan logikanya bergantung api lain atau tabel terpisah
                // (misal table return_rgo), jika belum ada table tsb bisa ditangani menyusul
                // else if ($ada_di_rgo) { $model->automation_track = 'Sudah diRGO'; }
            }
        });
    }
}
