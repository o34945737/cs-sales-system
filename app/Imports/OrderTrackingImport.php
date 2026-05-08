<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Complaint;
use App\Models\LastStep;
use App\Models\OrderTracking;
use App\Models\OrderTrackingDataSource;
use App\Models\OrderTrackingErpStatus;
use App\Models\Platform;
use App\Models\SubCase;
use App\Models\User;
use DateTimeImmutable;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class OrderTrackingImport implements OnEachRow, WithHeadingRow
{
    public array $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];

    private int $rowNum = 1;
    private array $defaultCauseByBySubCase;
    private array $complaintSyncByOrderId;
    private array $validBrandNames;
    private array $validCauseByNames;
    private array $validCategoryNames;
    private array $validCsNames;
    private array $validErpStatusNames;
    private array $validLastStepNames;
    private array $validPlatformNames;
    private array $validSourceNames;

    public function __construct(
        private readonly string $batchId,
        private readonly ?int $uploadedBy = null,
    ) {
        $this->defaultCauseByBySubCase = SubCase::query()
            ->where('is_active', true)
            ->whereNotNull('default_cause_by')
            ->pluck('default_cause_by', 'name')
            ->all();
        $this->complaintSyncByOrderId = Complaint::query()
            ->whereNotNull('order_id')
            ->get(['order_id', 'sub_case', 'last_step', 'reason_whitelist', 'reason_late_respons'])
            ->filter(fn(Complaint $complaint) => filled($complaint->order_id))
            ->keyBy('order_id')
            ->map(fn(Complaint $complaint) => [
                'category' => $complaint->sub_case,
                'last_step' => $complaint->last_step,
                'reason_whitelist' => $complaint->reason_whitelist,
                'reason_late_respons' => $complaint->reason_late_respons,
            ])
            ->all();
        $this->validBrandNames = Brand::query()->where('is_active', true)->pluck('name')->all();
        $this->validCauseByNames = \App\Models\CauseBy::query()->where('is_active', true)->pluck('name')->all();
        $this->validCategoryNames = SubCase::query()->where('is_active', true)->pluck('name')->all();
        $this->validCsNames = User::query()
            ->whereHas('roles', fn($query) => $query->where('name', 'CS'))
            ->where('is_active', true)
            ->pluck('name')
            ->all();
        $this->validErpStatusNames = OrderTrackingErpStatus::query()->where('is_active', true)->pluck('name')->all();
        $this->validLastStepNames = LastStep::query()->where('is_active', true)->pluck('name')->all();
        $this->validPlatformNames = Platform::query()->where('is_active', true)->pluck('name')->all();
        $this->validSourceNames = OrderTrackingDataSource::query()->where('is_active', true)->pluck('name')->all();
    }

    public function onRow(Row $row): void
    {
        $this->rowNum++;
        $rowArr = $row->toArray();

        if ($this->isEmptyRow($rowArr)) {
            return;
        }

        $orderId = trim((string) ($rowArr['order_id'] ?? ''));

        if (!$orderId) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: order_id kosong";
            return;
        }

        $dataSource   = trim((string) ($rowArr['data_source'] ?? ''));
        $tanggalInput = $this->parseDate($rowArr['tanggal_input'] ?? '');
        $tanggalOrder = $this->parseDate($rowArr['tanggal_order'] ?? '');
        $tanggalUpdate = $this->parseDate($rowArr['tanggal_update'] ?? '');
        $brand        = trim((string) ($rowArr['brand'] ?? ''));
        $platform     = trim((string) ($rowArr['platform'] ?? ''));
        $csName       = trim((string) ($rowArr['cs_name'] ?? ''));
        $category     = trim((string) ($rowArr['category'] ?? ''));
        $lastStep     = trim((string) ($rowArr['last_step'] ?? ''));
        $complaintSync = $this->complaintSyncByOrderId[$orderId] ?? null;

        if ($complaintSync) {
            $category = filled($complaintSync['category'] ?? null) ? $complaintSync['category'] : $category;
            $lastStep = filled($complaintSync['last_step'] ?? null) ? $complaintSync['last_step'] : $lastStep;
        }

        $causeBy      = $this->resolveCauseBy($category, $rowArr['cause_by'] ?? null);
        $paymentMethod = $this->nullableStr($rowArr['payment_method'] ?? null);
        $erpStatus = $this->nullableStr($rowArr['erp_status'] ?? null);
        $insuranceInfo = $this->nullableStr($rowArr['insurance_info'] ?? null);
        $reasonWhitelist = $complaintSync && filled($complaintSync['reason_whitelist'] ?? null)
            ? $complaintSync['reason_whitelist']
            : $this->nullableStr($rowArr['reason_whitelist'] ?? null);
        $reasonLateRespons = $complaintSync && filled($complaintSync['reason_late_respons'] ?? null)
            ? $complaintSync['reason_late_respons']
            : $this->nullableStr($rowArr['reason_late_respons'] ?? null);

        if (!$dataSource || !$tanggalInput || !$tanggalOrder) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: data_source, tanggal_input, atau tanggal_order kosong/tidak valid";
            return;
        }

        if (!$tanggalUpdate) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: tanggal_update kosong/tidak valid";
            return;
        }

        $validationErrors = $this->validateManualFields([
            'data_source' => $dataSource,
            'brand' => $brand,
            'platform' => $platform,
            'order_id' => $orderId,
            'cause_by' => $causeBy,
            'erp_status' => $erpStatus,
            'payment_method' => $paymentMethod,
            'cs_name' => $csName,
            'category' => $category,
            'last_step' => $lastStep,
            'insurance_info' => $insuranceInfo,
            'reason_whitelist' => $reasonWhitelist,
            'reason_late_respons' => $reasonLateRespons,
        ]);

        if ($validationErrors) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: " . implode(', ', $validationErrors);
            return;
        }

        $payload = [
            'data_source'       => $dataSource,
            'tanggal_input'     => $tanggalInput,
            'tanggal_order'     => $tanggalOrder,
            'brand'             => $brand,
            'platform'          => $platform,
            'order_id'          => $orderId,
            'value'             => $this->parseNumeric($rowArr['value'] ?? null),
            'cause_by'          => $causeBy,
            'awb'               => $this->nullableStr($rowArr['awb'] ?? null),
            'erp_status'        => $erpStatus,
            'payment_method'    => $paymentMethod,
            'wh_note'           => $this->nullableStr($rowArr['wh_note'] ?? null),
            'cs_name'           => $csName,
            'category'          => $category,
            'last_step'         => $lastStep,
            'update'            => $this->nullableStr($rowArr['update'] ?? null),
            'tanggal_update'    => $tanggalUpdate,
            'value_receive'     => $this->parseNumeric($rowArr['value_receive'] ?? null),
            'insurance_info'    => $insuranceInfo,
            'update_wh'         => $this->nullableStr($rowArr['update_wh'] ?? null),
            'update_finance'    => $this->nullableStr($rowArr['update_finance'] ?? null),
            'reason_whitelist'  => $reasonWhitelist,
            'reason_late_respons' => $reasonLateRespons,
        ];

        try {
            $existing = OrderTracking::where('order_id', $orderId)->first();

            if ($existing) {
                $existing->update($payload);
                $this->results['updated']++;
            } else {
                OrderTracking::create($payload);
                $this->results['created']++;
            }
        } catch (\Throwable $e) {
            $this->results['failed']++;
            $this->results['errors'][] = "Baris {$this->rowNum}: {$e->getMessage()}";
        }
    }

    private function parseDate(mixed $value): ?string
    {
        if (!$value) return null;

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $str = trim((string) $value);
        if (!$str) return null;

        // Handle Excel numeric date serial
        if (is_numeric($str)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $str);
                return $date->format('Y-m-d');
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

    private function parseNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^\d,.\-]/', '', trim((string) $value));
        if ($normalized === '' || $normalized === '-' || $normalized === null) {
            return null;
        }

        $lastComma = strrpos($normalized, ',');
        $lastDot = strrpos($normalized, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($lastComma !== false) {
            $fractionLength = strlen($normalized) - $lastComma - 1;

            if ($fractionLength === 3) {
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif ($lastDot !== false) {
            $fractionLength = strlen($normalized) - $lastDot - 1;

            if ($fractionLength === 3) {
                $normalized = str_replace('.', '', $normalized);
            }
        }

        $num = filter_var($normalized, FILTER_VALIDATE_FLOAT);
        return $num === false ? null : $num;
    }

    private function nullableStr(mixed $value): ?string
    {
        if ($value === null) return null;
        $str = trim((string) $value);
        return $str === '' ? null : $str;
    }

    private function resolveCauseBy(string $category, mixed $excelCauseBy): string
    {
        if ($category !== '' && filled($this->defaultCauseByBySubCase[$category] ?? null)) {
            return $this->defaultCauseByBySubCase[$category];
        }

        $causeBy = trim((string) ($excelCauseBy ?? ''));
        return $causeBy !== '' ? $causeBy : '?';
    }

    private function validateManualFields(array $data): array
    {
        $errors = [];

        foreach (['brand', 'platform', 'cs_name', 'category', 'last_step', 'cause_by'] as $field) {
            if (!filled($data[$field] ?? null)) {
                $errors[] = "{$field} wajib diisi";
            }
        }

        $this->validateInList($errors, 'data_source', $data['data_source'] ?? null, $this->validSourceNames ?: ['WH', 'Finance', 'Reject Return']);
        $this->validateInList($errors, 'brand', $data['brand'] ?? null, $this->validBrandNames);
        $this->validateInList($errors, 'platform', $data['platform'] ?? null, $this->validPlatformNames);
        $this->validateInList($errors, 'cause_by', $data['cause_by'] ?? null, $this->validCauseByNames);
        $this->validateInList($errors, 'erp_status', $data['erp_status'] ?? null, $this->validErpStatusNames);
        $this->validateInList($errors, 'payment_method', $data['payment_method'] ?? null, ['COD', 'NON COD']);
        $this->validateInList($errors, 'cs_name', $data['cs_name'] ?? null, $this->validCsNames);
        $this->validateInList($errors, 'category', $data['category'] ?? null, $this->validCategoryNames);
        $this->validateInList($errors, 'last_step', $data['last_step'] ?? null, $this->validLastStepNames);
        $this->validateInList($errors, 'insurance_info', $data['insurance_info'] ?? null, ['Y', 'N']);

        if (($data['last_step'] ?? null) === 'Claim Reject' && !filled($data['reason_whitelist'] ?? null)) {
            $errors[] = 'reason_whitelist wajib diisi jika last_step Claim Reject';
        }

        if (($data['reason_whitelist'] ?? null) === 'Late Respons' && !filled($data['reason_late_respons'] ?? null)) {
            $errors[] = 'reason_late_respons wajib diisi jika reason_whitelist Late Respons';
        }

        return $errors;
    }

    private function validateInList(array &$errors, string $field, mixed $value, array $validValues): void
    {
        if (!filled($value)) {
            return;
        }

        if (!$validValues) {
            return;
        }

        if (!in_array($value, $validValues, true)) {
            $errors[] = "{$field} '{$value}' tidak ada di master";
        }
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
