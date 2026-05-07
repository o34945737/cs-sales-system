<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTrackingErpTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [1, 'ORD-12345', 'Pending'],
            [2, 'ORD-12346', 'Process'],
            [3, 'ORD-12347', 'Done'],
        ];
    }

    public function headings(): array
    {
        return ['no', 'order_id', 'erp_status'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 8, 'B' => 28, 'C' => 22];
    }
}
