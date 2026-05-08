<?php

namespace App\Exports;

use App\Models\Complaint;
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

class ComplaintExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents
{
    public function __construct(private readonly Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function map($complaint): array
    {
        /** @var Complaint $complaint */
        return [
            $complaint->source,
            $this->formatDate($complaint->tanggal_complaint),
            $this->formatDate($complaint->tanggal_order),
            $complaint->jam_customer_complaint,
            $complaint->brand,
            $complaint->platform,
            $complaint->order_id,
            $complaint->username,
            $complaint->resi,
            $complaint->sku,
            $complaint->product_name,
            $complaint->qty,
            $complaint->sub_case,
            $complaint->cause_by,
            $complaint->proof,
            $complaint->summary_case,
            $complaint->update_long_text,
            $complaint->part_of_bad,
            $complaint->cs_name,
            $complaint->last_step,
            $complaint->step_cs_selesai,
            $complaint->complaint_power,
            $complaint->history,
            $this->formatDate($complaint->tanggal_update),
            $this->formatDate($complaint->tanggal_step_cs_selesai),
            $complaint->reason_whitelist,
            $complaint->reason_late_respons,
            $complaint->status,
            $complaint->priority,
            $complaint->cycle,
            $complaint->sla,
            $complaint->auto_sync_sla,
            $complaint->oos,
            $complaint->report_category,
        ];
    }

    public function headings(): array
    {
        return [
            'source',
            'tanggal_complaint',
            'tanggal_order',
            'jam_customer_complaint',
            'brand',
            'platform',
            'order_id',
            'username',
            'resi',
            'sku',
            'product_name',
            'qty',
            'sub_case',
            'cause_by',
            'proof',
            'summary_case',
            'update_long_text',
            'part_of_bad',
            'cs_name',
            'last_step',
            'step_cs_selesai',
            'complaint_power',
            'history',
            'tanggal_update',
            'tanggal_step_cs_selesai',
            'reason_whitelist',
            'reason_late_respons',
            'status',
            'priority',
            'cycle',
            'sla',
            'auto_sync_sla',
            'oos',
            'report_category',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A1:AH{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18, 'B' => 18, 'C' => 18, 'D' => 22, 'E' => 16, 'F' => 16,
            'G' => 22, 'H' => 22, 'I' => 20, 'J' => 18, 'K' => 28, 'L' => 10,
            'M' => 22, 'N' => 18, 'O' => 28, 'P' => 32, 'Q' => 32, 'R' => 18,
            'S' => 22, 'T' => 24, 'U' => 18, 'V' => 22, 'W' => 24, 'X' => 18,
            'Y' => 22, 'Z' => 24, 'AA' => 24, 'AB' => 14, 'AC' => 12, 'AD' => 24,
            'AE' => 10, 'AF' => 18, 'AG' => 20, 'AH' => 18,
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
