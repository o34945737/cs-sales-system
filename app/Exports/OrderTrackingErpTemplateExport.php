<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTrackingErpTemplateExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    private Collection $rows;

    public function __construct(?Collection $rows = null)
    {
        $this->rows = $rows ?? collect();
    }

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($item, $index) {
            return [
                'no'       => $index + 1,
                'order_id' => $item['order_id'] ?? '',
                'status'   => $item['status'] ?? '',
            ];
            });
            }

            public function headings(): array
            {
            return ['no', 'order_id', 'status'];
            }

            public function styles(Worksheet $sheet): array
            {
            $styles = [
            1 => ['font' => ['bold' => true, 'size' => 11]],
            ];

            $lastRow = $this->rows->count() + 1;

            if ($lastRow > 1) {
            $sheet->getStyle("B2:C{$lastRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFFF99');
            }

            return $styles;
            }

            public function columnWidths(): array
            {
            return [
            'A' => 6,
            'B' => 30,
            'C' => 20,
            ];
            }
}
