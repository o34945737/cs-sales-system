<?php

namespace App\Models;

use App\Services\OrderTrackingAutomationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'source', 'tanggal_complaint', 'tanggal_order', 'jam_customer_complaint',
        'brand_id', 'brand', 'platform_id', 'platform',
        'sku_code_id', 'sub_case_id', 'last_step_id', 'cs_user_id',
        'complaint_source_id', 'complaint_power_id', 'part_of_bad_id',
        'order_id', 'username', 'resi',
        'sku', 'product_name', 'value_of_product', 'qty', 'sub_case', 'cause_by', 'proof', 'proof_attachment',
        'summary_case', 'update_long_text', 'part_of_bad', 'cs_name',
        'last_step', 'step_cs_selesai', 'tanggal_step_cs_selesai', 'tanggal_update',
        'reason_whitelist', 'reason_late_respons', 'video_unboxing', 'complaint_power',
        'cycle', 'status', 'priority', 'history', 'oos', 'report_category', 'auto_sync_sla', 'sla',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // 0. SKU master -> autofill product name bila tersedia.
            $skuCode = $model->sku
                ? SkuCode::query()
                    ->where('sku', $model->sku)
                    ->first(['product_name'])
                : null;

            if ($skuCode) {
                if (blank($model->product_name)) {
                    $model->product_name = $skuCode->product_name;
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
                $model->report_category = $defaultCauseBy;
            } else {
                $model->report_category = null;
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

            // 7. History (Penghitungan riwayat username)
            if (empty($model->history) && $model->username) {
                if (! $model->exists) {
                    $count = self::where('username', $model->username)->count();
                    if ($count === 0) {
                        $model->history = null;
                    } elseif ($count === 1) {
                        $model->history = 'Customer ini complaint ke 2';
                    } else {
                        $model->history = 'Customer ini complaint ke '.($count + 1).'x';
                    }
                }
            }

            // 8. Riwayat OOS
            if ($model->order_id) {
                // Hubungkan ke model OOS (kalau ada match di tabel oos)
                $existsInOos = Oos::where('order_id', $model->order_id)->exists();
                if ($existsInOos) {
                    $model->oos = 'Ada Riwayat OOS';
                } else {
                    $model->oos = null;
                }
            }

            $sla = self::resolveSlaForModel($model);
            $model->sla = $sla;
            $model->auto_sync_sla = self::resolveAutoSyncSlaLabel($sla);
        });

        static::saved(function ($model) {
            self::syncOrderTrackingAutomation($model);
        });

        static::deleted(function ($model) {
            self::syncOrderTrackingAutomation($model);
        });

        static::restored(function ($model) {
            self::syncOrderTrackingAutomation($model);
        });
    }

    private static function syncOrderTrackingAutomation(self $model): void
    {
        if (filled($model->order_id)) {
            app(OrderTrackingAutomationService::class)->recomputeByOrderIds([$model->order_id]);
        }
    }

    protected static function resolveSlaForModel(self $model): int
    {
        if (! $model->tanggal_complaint) {
            return 0;
        }

        $startDate = Carbon::parse($model->tanggal_complaint);
        $endDate = $model->status === 'Solved' && $model->tanggal_update
            ? Carbon::parse($model->tanggal_update)
            : Carbon::now();

        return max(0, $startDate->diffInDays($endDate));
    }

    protected static function resolveAutoSyncSlaLabel(int $sla): string
    {
        return $sla <= 0 ? 'Within 1 day' : "Above {$sla} days";
    }

    public function oosRecords(): HasMany
    {
        return $this->hasMany(Oos::class);
    }

    // 6. SLA - Dinamis menggunakan Accessor
    public function getSlaAttribute()
    {
        if (! $this->tanggal_complaint) {
            return null;
        }

        return self::resolveSlaForModel($this);
    }
}
