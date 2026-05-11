<?php

namespace App\Imports;

use App\Models\OrderTracking;
use App\Models\OrderTrackingErpStatus;
use App\Models\OrderTrackingErpStatusHistory;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class OrderTrackingErpImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['updated' => 0, 'pending' => 0, 'failed' => 0, 'errors' => []];

    private array $validStatuses;
    private int $nextSortOrder;
    private int $rowNum = 1;

    public function __construct(
        private readonly string $batchId,
        private readonly ?int $uploadedBy = null,
    ) {
        $this->validStatuses = OrderTrackingErpStatus::where('is_active', true)->pluck('name')->all();
        $this->nextSortOrder = (int) OrderTrackingErpStatus::max('sort_order') + 1;
    }

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        $orderId   = trim((string) ($rowArr['order_id'] ?? ''));
        $erpStatus = trim((string) ($rowArr['erp_status'] ?? $rowArr['status'] ?? ''));

        if (!$orderId) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id kosong";
            return;
        }

        if (!$erpStatus) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: erp_status/status kosong";
            return;
        }

        $this->ensureErpStatusExists($erpStatus);

        OrderTrackingErpStatusHistory::create([
            'order_id'    => $orderId,
            'erp_status'  => $erpStatus,
            'batch_id'    => $this->batchId,
            'uploaded_by' => $this->uploadedBy,
        ]);

        $updated = OrderTracking::where('order_id', $orderId)->update(['erp_status' => $erpStatus]);

        if ($updated > 0) {
            $this->results['updated']++;
        } else {
            $this->results['pending']++;
        }
    }

    private function ensureErpStatusExists(string $erpStatus): void
    {
        if (in_array($erpStatus, $this->validStatuses, true)) {
            return;
        }

        $status = OrderTrackingErpStatus::firstOrCreate(
            ['name' => $erpStatus],
            [
                'is_active' => true,
                'sort_order' => $this->nextSortOrder++,
            ],
        );

        if (!$status->is_active) {
            $status->update(['is_active' => true]);
        }

        $this->validStatuses[] = $erpStatus;
    }
}
