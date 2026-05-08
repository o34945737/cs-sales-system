<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Complaint;
use App\Models\ComplaintPower;
use App\Models\ComplaintSource;
use App\Models\LastStep;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use DateTimeImmutable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ComplaintImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

    private int $rowNum = 1;
    private array $defaultCauseByBySubCase;
    private array $validBrandNames;
    private array $validCauseByNames;
    private array $validComplaintPowers;
    private array $validCsNames;
    private array $validLastStepNames;
    private array $validPlatforms;
    private array $validReasonLateResponses;
    private array $validReasonWhitelists;
    private array $validSkuCodes;
    private array $validSources;
    private array $validSubCases;

    public function __construct()
    {
        $this->defaultCauseByBySubCase = SubCase::query()
            ->where('is_active', true)
            ->whereNotNull('default_cause_by')
            ->pluck('default_cause_by', 'name')
            ->all();
        $this->validBrandNames = Brand::query()->where('is_active', true)->pluck('name')->all();
        $this->validCauseByNames = array_values(array_unique(array_merge(['?'], CauseBy::query()->where('is_active', true)->pluck('name')->all())));
        $this->validComplaintPowers = ComplaintPower::query()->where('is_active', true)->pluck('name')->all();
        $this->validCsNames = User::query()
            ->whereHas('roles', fn($query) => $query->where('name', 'CS'))
            ->where('is_active', true)
            ->pluck('name')
            ->all();
        $this->validLastStepNames = LastStep::query()->where('is_active', true)->pluck('name')->all();
        $this->validPlatforms = Platform::query()->where('is_active', true)->pluck('name')->all();
        $this->validReasonLateResponses = ReasonLateResponse::query()->where('is_active', true)->pluck('name')->all();
        $this->validReasonWhitelists = ReasonWhitelist::query()->where('is_active', true)->pluck('name')->all();
        $this->validSkuCodes = SkuCode::query()->whereNotNull('sku')->pluck('sku')->all();
        $this->validSources = ComplaintSource::query()->where('is_active', true)->pluck('name')->all();
        $this->validSubCases = SubCase::query()->where('is_active', true)->pluck('name')->all();
    }

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        if ($this->isEmptyRow($rowArr)) {
            return;
        }

        $payload = [
            'source' => $this->string($rowArr['source'] ?? null),
            'tanggal_complaint' => $this->parseDate($rowArr['tanggal_complaint'] ?? null),
            'tanggal_order' => $this->parseDate($rowArr['tanggal_order'] ?? null),
            'jam_customer_complaint' => $this->parseTime($rowArr['jam_customer_complaint'] ?? null),
            'brand' => $this->string($rowArr['brand'] ?? null),
            'platform' => $this->string($rowArr['platform'] ?? null),
            'order_id' => $this->string($rowArr['order_id'] ?? null),
            'username' => $this->string($rowArr['username'] ?? null),
            'resi' => $this->string($rowArr['resi'] ?? null),
            'sku' => $this->string($rowArr['sku'] ?? null),
            'product_name' => $this->nullableString($rowArr['product_name'] ?? null),
            'qty' => $this->parseInteger($rowArr['qty'] ?? null),
            'sub_case' => $this->string($rowArr['sub_case'] ?? null),
            'proof' => $this->nullableString($rowArr['proof'] ?? null),
            'summary_case' => $this->string($rowArr['summary_case'] ?? null),
            'update_long_text' => $this->string($rowArr['update_long_text'] ?? null),
            'part_of_bad' => $this->nullableString($rowArr['part_of_bad'] ?? null),
            'cs_name' => $this->string($rowArr['cs_name'] ?? null),
            'last_step' => $this->string($rowArr['last_step'] ?? null),
            'step_cs_selesai' => strtoupper($this->string($rowArr['step_cs_selesai'] ?? 'NO')),
            'complaint_power' => $this->string($rowArr['complaint_power'] ?? null),
            'history' => $this->nullableString($rowArr['history'] ?? null),
            'tanggal_update' => $this->parseDate($rowArr['tanggal_update'] ?? null),
            'tanggal_step_cs_selesai' => $this->parseDate($rowArr['tanggal_step_cs_selesai'] ?? null),
            'reason_whitelist' => $this->nullableString($rowArr['reason_whitelist'] ?? null),
            'reason_late_respons' => $this->nullableString($rowArr['reason_late_respons'] ?? null),
        ];
        $payload['cause_by'] = $this->resolveCauseBy($payload['sub_case'], $rowArr['cause_by'] ?? null);

        if ($payload['step_cs_selesai'] !== 'YES') {
            $payload['tanggal_step_cs_selesai'] = null;
        }

        $errors = $this->validatePayload($payload);

        if ($errors) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: " . implode(', ', $errors);
            return;
        }

        try {
            $existing = Complaint::query()->where('order_id', $payload['order_id'])->first();

            if ($existing) {
                $existing->update($payload);
                $this->results['updated']++;
            } else {
                Complaint::create($payload);
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
                'sub_case',
                'cause_by',
                'summary_case',
                'update_long_text',
                'cs_name',
                'last_step',
                'step_cs_selesai',
                'complaint_power',
                'tanggal_update',
            ] as $field
        ) {
            if (!filled($payload[$field] ?? null)) {
                $errors[] = "{$field} wajib diisi";
            }
        }

        $this->validateInList($errors, 'source', $payload['source'] ?? null, $this->validSources);
        $this->validateInList($errors, 'brand', $payload['brand'] ?? null, $this->validBrandNames);
        $this->validateInList($errors, 'platform', $payload['platform'] ?? null, $this->validPlatforms);
        $this->validateInList($errors, 'sku', $payload['sku'] ?? null, $this->validSkuCodes);
        $this->validateInList($errors, 'sub_case', $payload['sub_case'] ?? null, $this->validSubCases);
        $this->validateInList($errors, 'cause_by', $payload['cause_by'] ?? null, $this->validCauseByNames);
        $this->validateInList($errors, 'cs_name', $payload['cs_name'] ?? null, $this->validCsNames);
        $this->validateInList($errors, 'last_step', $payload['last_step'] ?? null, $this->validLastStepNames);
        $this->validateInList($errors, 'step_cs_selesai', $payload['step_cs_selesai'] ?? null, ['YES', 'NO']);
        $this->validateInList($errors, 'complaint_power', $payload['complaint_power'] ?? null, $this->validComplaintPowers);
        $this->validateInList($errors, 'reason_whitelist', $payload['reason_whitelist'] ?? null, $this->validReasonWhitelists);
        $this->validateInList($errors, 'reason_late_respons', $payload['reason_late_respons'] ?? null, $this->validReasonLateResponses);

        if (($payload['last_step'] ?? null) === 'Claim Reject' && !filled($payload['reason_whitelist'] ?? null)) {
            $errors[] = 'reason_whitelist wajib diisi jika last_step Claim Reject';
        }

        if (($payload['reason_whitelist'] ?? null) === 'Late Respons' && !filled($payload['reason_late_respons'] ?? null)) {
            $errors[] = 'reason_late_respons wajib diisi jika reason_whitelist Late Respons';
        }

        if (($payload['step_cs_selesai'] ?? null) === 'YES' && !filled($payload['tanggal_step_cs_selesai'] ?? null)) {
            $errors[] = 'tanggal_step_cs_selesai wajib diisi jika step_cs_selesai YES';
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

    private function parseTime(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (is_numeric($str)) {
            $seconds = (int) round(((float) $str - floor((float) $str)) * 86400);
            return gmdate('H:i:s', $seconds);
        }

        $parsed = date_create($str);
        return $parsed ? $parsed->format('H:i:s') : null;
    }

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function resolveCauseBy(string $subCase, mixed $excelCauseBy): string
    {
        if ($subCase !== '' && filled($this->defaultCauseByBySubCase[$subCase] ?? null)) {
            return $this->defaultCauseByBySubCase[$subCase];
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
