<?php

namespace App\Imports;

use App\Models\BadReview;
use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Platform;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use DateTimeImmutable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BadReviewImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

    private int $rowNum = 1;
    private array $defaultCauseByBySubCase;
    private array $validBrandNames;
    private array $validCauseByNames;
    private array $validCategoryNames;
    private array $validCsNames;
    private array $validPlatformNames;
    private array $validSkuCodes;

    public function __construct()
    {
        $this->defaultCauseByBySubCase = SubCase::query()
            ->where('is_active', true)
            ->whereNotNull('default_cause_by')
            ->pluck('default_cause_by', 'name')
            ->all();
        $this->validBrandNames = Brand::query()->where('is_active', true)->pluck('name')->all();
        $this->validCauseByNames = CauseBy::query()->where('is_active', true)->pluck('name')->all();
        $this->validCategoryNames = SubCase::query()->where('is_active', true)->pluck('name')->all();
        $this->validCsNames = User::query()
            ->whereHas('roles', fn($query) => $query->where('name', 'CS'))
            ->where('is_active', true)
            ->pluck('name')
            ->all();
        $this->validPlatformNames = Platform::query()->where('is_active', true)->pluck('name')->all();
        $this->validSkuCodes = SkuCode::query()->whereNotNull('sku')->pluck('sku')->all();
    }

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        if ($this->isEmptyRow($rowArr)) {
            return;
        }

        $payload = [
            'tanggal_review' => $this->parseDate($rowArr['tanggal_review'] ?? null),
            'brand' => $this->string($rowArr['brand'] ?? null),
            'platform' => $this->string($rowArr['platform'] ?? null),
            'order_id' => $this->string($rowArr['order_id'] ?? null),
            'username' => $this->string($rowArr['username'] ?? null),
            'star' => $this->parseInteger($rowArr['star'] ?? null),
            'product_name' => $this->nullableString($rowArr['product_name'] ?? null),
            'sku' => $this->nullableString($rowArr['sku'] ?? null),
            'category_review' => $this->string($rowArr['category_review'] ?? null),
            'review_notes' => $this->string($rowArr['review_notes'] ?? null),
            'progress' => $this->string($rowArr['progress'] ?? null),
            'tanggal_update' => $this->parseDate($rowArr['tanggal_update'] ?? null),
            'cs_name' => $this->string($rowArr['cs_name'] ?? null),
        ];
        $payload['cause_by'] = $this->resolveCauseBy($payload['category_review'], $rowArr['cause_by'] ?? null);

        $errors = $this->validatePayload($payload);

        if ($errors) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: " . implode(', ', $errors);
            return;
        }

        try {
            $existing = BadReview::query()->where('order_id', $payload['order_id'])->first();

            if ($existing) {
                $existing->update($payload);
                $this->results['updated']++;
            } else {
                BadReview::create($payload);
                $this->results['created']++;
            }
        } catch (\Throwable $exception) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: {$exception->getMessage()}";
        }
    }

    private function validatePayload(array $payload): array
    {
        $errors = [];

        foreach (
            [
                'tanggal_review',
                'brand',
                'platform',
                'order_id',
                'username',
                'star',
                'category_review',
                'cause_by',
                'review_notes',
                'progress',
                'tanggal_update',
                'cs_name',
            ] as $field
        ) {
            if (!filled($payload[$field] ?? null)) {
                $errors[] = "{$field} wajib diisi";
            }
        }

        $this->validateInList($errors, 'brand', $payload['brand'] ?? null, $this->validBrandNames);
        $this->validateInList($errors, 'platform', $payload['platform'] ?? null, $this->validPlatformNames);
        $this->validateInList($errors, 'sku', $payload['sku'] ?? null, $this->validSkuCodes);
        $this->validateInList($errors, 'category_review', $payload['category_review'] ?? null, $this->validCategoryNames);
        $this->validateInList($errors, 'cause_by', $payload['cause_by'] ?? null, $this->validCauseByNames);
        $this->validateInList($errors, 'progress', $payload['progress'] ?? null, ['Follow Up Customer', 'Auto Reply']);
        $this->validateInList($errors, 'cs_name', $payload['cs_name'] ?? null, $this->validCsNames);

        if (filled($payload['star'] ?? null) && !in_array((int) $payload['star'], [1, 2, 3], true)) {
            $errors[] = "star '{$payload['star']}' harus 1, 2, atau 3";
        }

        return $errors;
    }

    private function parseDate(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (is_numeric($str)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $str)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat('!' . $format, $str);
            $errors = DateTimeImmutable::getLastErrors();

            if ($parsed && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $parsed->format('Y-m-d');
            }
        }

        $parsed = date_create($str);
        return $parsed ? $parsed->format('Y-m-d') : null;
    }

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function resolveCauseBy(string $categoryReview, mixed $excelCauseBy): string
    {
        if ($categoryReview !== '' && filled($this->defaultCauseByBySubCase[$categoryReview] ?? null)) {
            return $this->defaultCauseByBySubCase[$categoryReview];
        }

        $causeBy = $this->string($excelCauseBy);
        return $causeBy !== '' ? $causeBy : '?';
    }

    private function validateInList(array &$errors, string $field, mixed $value, array $validValues): void
    {
        if (!filled($value) || !$validValues) {
            return;
        }

        if (!in_array($value, $validValues, true)) {
            $errors[] = "{$field} '{$value}' tidak ada di master";
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = $this->string($value);
        return $value === '' ? null : $value;
    }

    private function string(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
