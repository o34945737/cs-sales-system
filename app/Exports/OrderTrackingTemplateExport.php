<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTrackingTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'WH',
                '08-05-2026',
                '08-05-2026',
                'ANTA',
                'SHOPEE',
                'ORD-111111',
                150000,
                'JNE111111',
                '',
                'NON COD',
                'wh_note disini',
                'Nama CS sesuai master',
                'Follow Up WH',
                'OOS',
                'KAE',
                'Update notes disini',
                '01-05-2026',
                '',
                'N',
                '',
                '',
                '',
                '',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'data_source',
            'tanggal_input',
            'tanggal_order',
            'brand',
            'platform',
            'order_id',
            'value',
            'awb',
            'erp_status',
            'payment_method',
            'wh_note',
            'cs_name',
            'last_step',
            'category',
            'cause_by',
            'update',
            'tanggal_update',
            'value_receive',
            'insurance_info',
            'update_wh',
            'update_finance',
            'reason_whitelist',
            'reason_late_respons',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 11
                ],
                'A1:Z1000' => [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                    ]
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 16,
            'C' => 16,
            'D' => 16,
            'E' => 16,
            'F' => 22,
            'G' => 14,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 22,
            'L' => 22,
            'M' => 20,
            'N' => 20,
            'O' => 20,
            'P' => 25,
            'Q' => 16,
            'R' => 14,
            'S' => 22,
            'T' => 22,
            'U' => 22,
            'V' => 22,
            'W' => 22,
        ];
    }
}
