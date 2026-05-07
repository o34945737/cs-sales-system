<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JetTrackEntryTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [1, 'JT-AWB-12345', 'ORD-12345', 'Baik', '', '', '', 1],
            [2, 'JT-AWB-12346', 'ORD-12346', 'Rusak Ringan', 'https://drive.google.com/file/xxx', 'https://docs.google.com/xxx', 'Pecah sebagian', 1],
        ];
    }

    public function headings(): array
    {
        return ['no', 'awb', 'order_id', 'kondisi_barang', 'video_url', 'warehouse_doc_link', 'notes', 'is_active'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8, 'B' => 20, 'C' => 20, 'D' => 22,
            'E' => 40, 'F' => 40, 'G' => 28, 'H' => 12,
        ];
    }
}
