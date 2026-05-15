<?php

namespace App\Imports;

use App\Models\OrderTracking;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class OrderTrackingRgoImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];
    public array $importedOrderIds = [];

    private int $rowNum = 1;

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        $orderId   = ltrim(trim((string) ($rowArr['order_id'] ?? '')), '#');
        $rgoStatus = trim((string) ($rowArr['rgo_status'] ?? ''));

        if (!$orderId) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id kosong";
            return;
        }

        $affected = OrderTracking::where('order_id', $orderId)
            ->update([
                'rgo_status'    => $rgoStatus !== '' ? $rgoStatus : null,
                'rgo_synced_at' => now(),
            ]);

        if ($affected > 0) {
            $this->results['updated'] += $affected;
            $this->importedOrderIds[] = $orderId;
        } else {
            $this->results['skipped']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id {$orderId} tidak ditemukan di sistem";
        }
    }
}
