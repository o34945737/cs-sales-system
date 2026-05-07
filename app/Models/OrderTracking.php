<?php

namespace App\Models;

use App\Services\OrderTrackingAutomationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_source', 'tanggal_input', 'tanggal_order', 'brand', 'platform',
        'order_id', 'value', 'cause_by', 'awb', 'erp_status', 'payment_method',
        'wh_note', 'cs_name', 'category', 'last_step', 'update', 'tanggal_update',
        'value_receive', 'insurance_info', 'video_unboxing_wh', 'bap_wh',
        'update_wh', 'update_finance', 'status', 'month', 'automation_track',
        'tanggal_tts', 'reason_whitelist', 'reason_late_respons', 'kondisi_barang',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            app(OrderTrackingAutomationService::class)->applyCreatingDefaults($model);
        });

        static::saving(function ($model) {
            app(OrderTrackingAutomationService::class)->compute($model);
        });
    }
}
