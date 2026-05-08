<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ComplaintTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'After Sales',
                'username',
                '08-05-2026',
                '08-05-2026',
                '14:30:00',
                'CLEAR',
                'SHOPEE',
                'ORD-12345',
                'JNE123456',
                'SKU-001',
                1,
                'OOS',
                'Bukti link / catatan proof',
                'Isi manual part of bad',
                'Ringkasan case complaint',
                'Update tindak lanjut complaint',
                'Nama CS sesuai master data',
                'Last step from master data',
                '08-05-2026',
                'NO',
                'Normal Complaint',
                '',
                '',
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'source',
            'username',
            'tanggal_complaint',
            'tanggal_order',
            'jam_customer_complaint',
            'brand',
            'platform',
            'order_id',
            'resi',
            'sku',
            'qty',
            'sub_case',
            'proof',
            'part_of_bad',
            'summary_case',
            'update_long_text',
            'cs_name',
            'last_step',
            'tanggal_update',
            'step_cs_selesai',
            'complaint_power',
            'tanggal_step_cs_selesai',
            'reason_whitelist',
            'reason_late_respons',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A:AA')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 18,
            'C' => 18,
            'D' => 22,
            'E' => 16,
            'F' => 16,
            'G' => 22,
            'H' => 22,
            'I' => 20,
            'J' => 18,
            'K' => 28,
            'L' => 10,
            'M' => 22,
            'N' => 18,
            'O' => 28,
            'P' => 32,
            'Q' => 32,
            'R' => 18,
            'S' => 22,
            'T' => 24,
            'U' => 18,
            'V' => 22,
            'W' => 24,
            'X' => 18,
            'Y' => 22,
            'Z' => 24,
            'AA' => 24,
        ];
    }
}
