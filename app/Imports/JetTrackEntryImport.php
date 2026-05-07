<?php

namespace App\Imports;

use App\Models\JetTrackEntry;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class JetTrackEntryImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
    public array $importedAwbs = [];

    private int $rowNum = 1;

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        $awb              = trim((string) ($rowArr['awb'] ?? ''));
        $orderId          = trim((string) ($rowArr['order_id'] ?? '')) ?: null;
        $kondisiBarang    = trim((string) ($rowArr['kondisi_barang'] ?? ''));
        $videoUrl         = trim((string) ($rowArr['video_url'] ?? '')) ?: null;
        $warehouseDocLink = trim((string) ($rowArr['warehouse_doc_link'] ?? '')) ?: null;
        $notes            = trim((string) ($rowArr['notes'] ?? '')) ?: null;
        $isActive         = isset($rowArr['is_active']) ? (bool)(int) $rowArr['is_active'] : true;

        if (!$awb) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: awb kosong";
            return;
        }

        if (!$kondisiBarang) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: kondisi_barang kosong";
            return;
        }

        $existing = JetTrackEntry::where('awb', $awb)->first();

        if ($existing) {
            $existing->update([
                'order_id'           => $orderId ?: $existing->order_id,
                'kondisi_barang'     => $kondisiBarang,
                'video_url'          => $videoUrl ?: $existing->video_url,
                'warehouse_doc_link' => $warehouseDocLink ?: $existing->warehouse_doc_link,
                'notes'              => $notes ?: $existing->notes,
                'is_active'          => $isActive,
            ]);
            $this->results['updated']++;
        } else {
            JetTrackEntry::create([
                'awb'                => $awb,
                'order_id'           => $orderId,
                'kondisi_barang'     => $kondisiBarang,
                'video_url'          => $videoUrl,
                'warehouse_doc_link' => $warehouseDocLink,
                'notes'              => $notes,
                'is_active'          => $isActive,
            ]);
            $this->results['created']++;
        }

        $this->importedAwbs[] = $awb;
    }
}
