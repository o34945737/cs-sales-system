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
            } else {
                $model->month = null;
            }

            // Status mengikuti master Last Step seperti di Complaint.
            $lastStep = $model->last_step
                ? LastStep::query()
                    ->where('name', $model->last_step)
                    ->where('is_active', true)
                    ->first(['status_result'])
                : null;

            if ($lastStep?->status_result) {
                $model->status = $lastStep->status_result;
            } elseif (!$model->status) {
                $model->status = 'Pending';
            }

            // Tanggal TTS (Order Date + 24 days only Lazada)
            if ($model->platform && stripos($model->platform, 'lazada') !== false && $model->tanggal_order) {
                $model->tanggal_tts = Carbon::parse($model->tanggal_order)->addDays(24)->toDateString();
            } else {
                $model->tanggal_tts = null;
            }

            if ($model->last_step !== 'Claim Reject') {
                $model->reason_whitelist = null;
                $model->reason_late_respons = null;
            }

            if ($model->reason_whitelist !== 'Late Respons') {
                $model->reason_late_respons = null;
            }

            // Automation Track
            if ($model->order_id) {
                $existInComplaint = Complaint::where('order_id', $model->order_id)->exists();
                $existInRgo = OrderTrackingRgoEntry::query()
                    ->where('order_id', $model->order_id)
                    ->where('is_active', true)
                    ->exists();
                $jetTrackEntry = filled($model->awb)
                    ? JetTrackEntry::query()
                        ->where('awb', $model->awb)
                        ->where('is_active', true)
                        ->first(['kondisi_barang'])
                    : null;

                if ($existInComplaint) {
                    $model->automation_track = 'MERGER';
                    $model->kondisi_barang = null;
                } elseif ($existInRgo) {
                    $model->automation_track = 'Sudah diRGO';
                    $model->kondisi_barang = null;
                } elseif ($jetTrackEntry) {
                    $model->automation_track = 'ADA DI JET TRACK - ' . $jetTrackEntry->kondisi_barang;
                    $model->kondisi_barang = $jetTrackEntry->kondisi_barang;
                } else {
                    $model->automation_track = null;
                    $model->kondisi_barang = null;
                }
            } else {
                $model->automation_track = null;
                $model->kondisi_barang = null;
            }
        });
    }
}
