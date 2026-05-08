<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BadReviewTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                '08-05-2026',
                1,
                'ANTA',
                'SHOPEE',
                'ORD-111111',
                'customer_name',
                'SKU-ANTA-BASKET-002',
                'Bad Quality Product',
                'Customer memberi review buruk karena produk bermasalah.',
                'Follow Up Customer',
                'Test User',
                '08-05-2026',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'tanggal_review',
            'star',
            'brand',
            'platform',
            'order_id',
            'username',
            'sku',
            'category_review',
            'review_notes',
            'progress',
            'cs_name',
            'tanggal_update',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A:N')->getAlignment()
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
            'B' => 16,
            'C' => 16,
            'D' => 22,
            'E' => 22,
            'F' => 10,
            'G' => 28,
            'H' => 18,
            'I' => 24,
            'J' => 18,
            'K' => 36,
            'L' => 22,
            'M' => 18,
            'N' => 22,
        ];
    }
}
