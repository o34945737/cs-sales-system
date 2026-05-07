<?php

namespace App\Imports;

use App\Models\OrderTrackingRgoEntry;
use App\Models\OrderTrackingRgoImportBatch;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class OrderTrackingRgoImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
    public array $importedOrderIds = [];

    private int $rowNum = 1;

    public function __construct(
        private readonly string $batchId,
        private readonly ?int $uploadedBy = null,
    ) {}

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        $orderId  = trim((string) ($rowArr['order_id'] ?? ''));
        $notes    = trim((string) ($rowArr['notes'] ?? '')) ?: null;
        $isActive = isset($rowArr['is_active']) ? (bool)(int) $rowArr['is_active'] : true;

        if (!$orderId) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id kosong";
            return;
        }

        OrderTrackingRgoImportBatch::create([
            'batch_id'    => $this->batchId,
            'order_id'    => $orderId,
            'notes'       => $notes,
            'is_active'   => $isActive,
            'uploaded_by' => $this->uploadedBy,
        ]);

        $existing = OrderTrackingRgoEntry::where('order_id', $orderId)->first();

        if ($existing) {
            $existing->update([
                'notes'     => $notes ?: $existing->notes,
                'is_active' => $isActive,
            ]);
            $this->results['updated']++;
        } else {
            OrderTrackingRgoEntry::create([
                'order_id'  => $orderId,
                'notes'     => $notes,
                'is_active' => $isActive,
            ]);
            $this->results['created']++;
        }

        $this->importedOrderIds[] = $orderId;
    }
}
