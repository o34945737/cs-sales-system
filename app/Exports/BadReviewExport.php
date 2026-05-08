<?php

namespace App\Exports;

use App\Models\BadReview;
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

class BadReviewExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithEvents
{
    public function __construct(private readonly Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function map($badReview): array
    {
        /** @var BadReview $badReview */
        return [
            $this->formatDate($badReview->tanggal_review),
            $badReview->brand,
            $badReview->platform,
            $badReview->order_id,
            $badReview->username,
            $badReview->star,
            $badReview->product_name,
            $badReview->sku,
            $badReview->category_review,
            $badReview->cause_by,
            $badReview->review_notes,
            $badReview->progress,
            $this->formatDate($badReview->tanggal_update),
            $badReview->cs_name,
            $badReview->month,
            $badReview->status,
            $badReview->priority,
        ];
    }

    public function headings(): array
    {
        return [
            'tanggal_review',
            'brand',
            'platform',
            'order_id',
            'username',
            'star',
            'product_name',
            'sku',
            'category_review',
            'cause_by',
            'review_notes',
            'progress',
            'tanggal_update',
            'cs_name',
            'month',
            'status',
            'priority',
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

                $sheet->getStyle("A1:Q{$highestRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
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
            'O' => 18,
            'P' => 14,
            'Q' => 14,
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
