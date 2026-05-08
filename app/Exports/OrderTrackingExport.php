<?php

namespace App\Exports;

use App\Models\OrderTracking;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderTrackingExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents
{
    public function __construct(private readonly Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function map($orderTracking): array
    {
        /** @var OrderTracking $orderTracking */
        return [
            $orderTracking->data_source,
            $this->formatDate($orderTracking->tanggal_input),
            $this->formatDate($orderTracking->tanggal_order),
            $orderTracking->brand,
            $orderTracking->platform,
            $orderTracking->order_id,
            $orderTracking->value,
            $orderTracking->awb,
            $orderTracking->erp_status,
            $orderTracking->payment_method,
            $orderTracking->wh_note,
            $orderTracking->cs_name,
            $orderTracking->last_step,
            $orderTracking->category,
            $orderTracking->cause_by,
            $orderTracking->update,
            $this->formatDate($orderTracking->tanggal_update),
            $orderTracking->value_receive,
            $orderTracking->insurance_info,
            $orderTracking->update_wh,
            $orderTracking->update_finance,
            $orderTracking->reason_whitelist,
            $orderTracking->reason_late_respons,
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
                    'size' => 11,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:W{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, // data_source
            'B' => 16, // tanggal_input
            'C' => 16, // tanggal_order
            'D' => 16, // brand
            'E' => 16, // platform
            'F' => 22, // order_id
            'G' => 14, // value
            'H' => 20, // awb
            'I' => 20, // erp_status
            'J' => 20, // payment_method
            'K' => 22, // wh_note
            'L' => 22, // cs_name
            'M' => 20, // last_step
            'N' => 20, // category
            'O' => 20, // cause_by
            'P' => 25, // update
            'Q' => 16, // tanggal_update
            'R' => 14, // value_receive
            'S' => 22, // insurance_info
            'T' => 22, // update_wh
            'U' => 22, // update_finance
            'V' => 22, // reason_whitelist
            'W' => 22, // reason_late_respons
        ];
    }

    private function formatDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        return date_create((string) $value)?->format('d-m-Y');
    }
}
