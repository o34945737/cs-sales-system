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
    public array $results = ['updated' => 0, 'pending' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => [], 'ordered_statuses' => []];

    /** @var string[] order_ids that were successfully updated for recompute after import */
    public array $importedOrderIds = [];

    private array $orderedStatuses;
    private array $statusNamesById = [];
    private int $rowNum = 1;

    public function __construct(
        private readonly string $batchId,
        private readonly ?int $uploadedBy = null,
    ) {
        $statuses = OrderTrackingErpStatus::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->orderedStatuses = $statuses->pluck('name')->all();
        $this->statusNamesById = $statuses
            ->mapWithKeys(fn(OrderTrackingErpStatus $status) => [(string) $status->id => $status->name])
            ->all();

        $this->results['ordered_statuses'] = $this->orderedStatuses;
    }

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        $orderId = ltrim(trim((string) ($rowArr['order_id'] ?? '')), '#');

        if (!$orderId) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id kosong";
            return;
        }

        if (empty($this->orderedStatuses)) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: tidak ada ERP status aktif di master data";
            return;
        }

        $tracking = OrderTracking::where('order_id', $orderId)->first(['id', 'order_id', 'erp_status']);

        // Ambil status saat ini dari order_trackings, fallback ke history
        $historyStatus = OrderTrackingErpStatusHistory::where('order_id', $orderId)->latest()->value('erp_status');
        $currentStatus = $this->statusNameFor($tracking?->erp_status)
            ?? $this->statusNameFor($historyStatus)
            ?? $historyStatus;

        $newStatus = $this->resolveNextStatus($currentStatus);

        if ($currentStatus === $newStatus) {
            $this->results['skipped']++;
            return;
        }

        OrderTrackingErpStatusHistory::create([
            'order_id'    => $orderId,
            'erp_status'  => $newStatus,
            'batch_id'    => $this->batchId,
            'uploaded_by' => $this->uploadedBy,
        ]);

        if ($tracking) {
            $tracking->update(['erp_status' => $newStatus]);

            $this->importedOrderIds[] = $orderId;
            $this->results['updated']++;
        } else {
            $this->results['pending']++;
        }
    }

    private function statusNameFor(?string $value): ?string
    {
        if (!filled($value)) {
            return null;
        }

        $value = trim((string) $value);

        if (isset($this->statusNamesById[$value])) {
            return $this->statusNamesById[$value];
        }

        foreach ($this->orderedStatuses as $status) {
            if (strcasecmp($status, $value) === 0) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Auto-advance: kembalikan status berikutnya dalam urutan.
     */
    private function resolveNextStatus(?string $current): string
    {
        if (!$current || !in_array($current, $this->orderedStatuses, true)) {
            return $this->orderedStatuses[0];
        }

        $index = array_search($current, $this->orderedStatuses, true);

        if ($index >= count($this->orderedStatuses) - 1) {
            return $current;
        }

        return $this->orderedStatuses[$index + 1];
    }
}
