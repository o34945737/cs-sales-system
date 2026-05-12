<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\JetTrackEntry;
use App\Models\LastStep;
use App\Models\OrderTracking;
use App\Models\OrderTrackingErpStatusHistory;
use App\Models\OrderTrackingRgoEntry;
use App\Models\Platform;
use Carbon\Carbon;

class OrderTrackingAutomationService
{
    /**
     * Populate erp_status from history when creating a new record without one.
     */
    public function applyCreatingDefaults(OrderTracking $model): void
    {
        if (!filled($model->erp_status)) {
            if ($model->order_id) {
                $history = OrderTrackingErpStatusHistory::where('order_id', $model->order_id)
                    ->latest()
                    ->first(['erp_status']);
                if ($history) {
                    $model->erp_status = $history->erp_status;
                    return;
                }
            }
            // Default to 'Done' if no erp_status and no history
            $model->erp_status = 'Done';
        }
    }

    /**
     * Compute all derived/automation fields before saving.
     */
    public function compute(OrderTracking $model): void
    {
        // Ensure erp_status is never empty - default to 'Done' if missing
        if (!filled($model->erp_status)) {
            if ($model->order_id) {
                $history = OrderTrackingErpStatusHistory::where('order_id', $model->order_id)
                    ->latest()
                    ->first(['erp_status']);
                if ($history) {
                    $model->erp_status = $history->erp_status;
                } else {
                    $model->erp_status = 'Done';
                }
            } else {
                $model->erp_status = 'Done';
            }
        }

        $model->month = $model->tanggal_input
            ? Carbon::parse($model->tanggal_input)->format('F Y')
            : null;

        $linkedComplaint = null;

        if ($model->order_id) {
            $linkedComplaint = Complaint::query()
                ->where('order_id', $model->order_id)
                ->latest('id')
                ->first(['sub_case', 'last_step', 'status', 'reason_whitelist', 'reason_late_respons']);

            if ($linkedComplaint) {
                if (filled($linkedComplaint->sub_case)) {
                    $model->category = $linkedComplaint->sub_case;
                }

                if (filled($linkedComplaint->last_step)) {
                    $model->last_step = $linkedComplaint->last_step;
                }

                $model->reason_whitelist = $linkedComplaint->reason_whitelist;
                $model->reason_late_respons = $linkedComplaint->reason_late_respons;
            }
        }

        // B.1: Status — sync langsung dari complaint kalau linked, fallback ke derive dari last_step
        if ($linkedComplaint && filled($linkedComplaint->status)) {
            $model->status = $linkedComplaint->status;
        } else {
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
        }

        $ttsDays = filled($model->platform)
            ? Platform::query()->whereRaw('LOWER(name) = ?', [strtolower($model->platform)])->value('tts_days')
            : null;

        if ($ttsDays && $model->tanggal_order) {
            $model->tanggal_tts = Carbon::parse($model->tanggal_order)->addDays($ttsDays)->toDateString();
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

        if ($model->order_id) {
            $existInComplaint = (bool) $linkedComplaint;
            $existInRgo = OrderTrackingRgoEntry::query()
                ->where('order_id', $model->order_id)
                ->where('is_active', true)
                ->exists();
            $jetTrackEntry = $this->findJetTrackEntry($model);

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
    }

    /**
     * Re-compute automation for matching OrderTracking records by order_id.
     * Priority: MERGER > Sudah diRGO > ADA DI JET TRACK.
     */
    public function recomputeByOrderIds(array $orderIds): int
    {
        $count = 0;
        OrderTracking::whereIn('order_id', $orderIds)
            ->each(function (OrderTracking $tracking) use (&$count) {
                $this->compute($tracking);
                $tracking->saveQuietly();
                $count++;
            });

        return $count;
    }

    /**
     * Re-compute automation for matching OrderTracking records by awb.
     * Used after JetTrack import.
     */
    public function recomputeByAwbs(array $awbs): int
    {
        $count = 0;
        OrderTracking::whereIn('awb', $awbs)
            ->each(function (OrderTracking $tracking) use (&$count) {
                $this->compute($tracking);
                $tracking->saveQuietly();
                $count++;
            });

        return $count;
    }

    /**
     * Find JetTrackEntry strictly by AWB (sesuai spec B.3c).
     */
    private function findJetTrackEntry(OrderTracking $model): ?JetTrackEntry
    {
        if (!filled($model->awb)) {
            return null;
        }

        return JetTrackEntry::query()
            ->where('awb', $model->awb)
            ->where('is_active', true)
            ->first(['kondisi_barang', 'video_url', 'warehouse_doc_link']);
    }
}
