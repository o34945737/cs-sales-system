<?php

namespace App\Services;

use App\Models\OrderTracking;
use Illuminate\Support\Facades\Cache;
use Revolution\Google\Sheets\Facades\Sheets;

class GoogleSheetsRgoService
{
    private string $spreadsheetId;
    private string $sheetName;

    public function __construct()
    {
        $this->spreadsheetId = (string) env('RGO_SHEET_ID', '');
        $this->sheetName     = (string) env('RGO_SHEET_NAME', 'Sheet1');
    }

    public function isConfigured(): bool
    {
        return filled($this->spreadsheetId)
            && config('google.service.enable', false);
    }

    /**
     * Pull from Google Sheet, match by order_id, and update system rgo_status
     * to follow the sheet (sheet is source of truth).
     *
     * Returns per-order entries with status:
     *   - matched     : sheet == system, no update needed
     *   - updated     : was different, system updated to match sheet
     *   - not_in_system: order_id from sheet not found in order_trackings
     *
     * @return array{
     *     entries: array<int, array{order_id:string, before:?string, after:?string, sheet:?string, reason:string}>,
     *     summary: array{matched:int, updated:int, not_in_system:int, total:int},
     *     errors: string[]
     * }
     */
    public function sync(): array
    {
        $entries = [];
        $errors  = [];

        $rows = Sheets::spreadsheet($this->spreadsheetId)
            ->sheet($this->sheetName)
            ->get();

        if ($rows->isEmpty()) {
            $errors[] = 'Sheet kosong atau tidak ditemukan.';
            return [
                'entries' => [],
                'summary' => ['matched' => 0, 'updated' => 0, 'not_in_system' => 0, 'total' => 0],
                'errors'  => $errors,
            ];
        }

        $header = $rows->first();
        $data   = Sheets::collection($header, $rows->skip(1));

        $sheetMap = [];
        foreach ($data as $row) {
            $orderId   = ltrim(trim((string) ($row->get('order_id') ?? '')), '#');
            $rgoStatus = trim((string) ($row->get('rgo_status') ?? ''));
            if (!$orderId) {
                continue;
            }
            $sheetMap[$orderId] = $rgoStatus !== '' ? $rgoStatus : null;
        }

        if (empty($sheetMap)) {
            $errors[] = 'Tidak ada order_id valid di sheet.';
            return [
                'entries' => [],
                'summary' => ['matched' => 0, 'updated' => 0, 'not_in_system' => 0, 'total' => 0],
                'errors'  => $errors,
            ];
        }

        $systemMap = OrderTracking::query()
            ->whereIn('order_id', array_keys($sheetMap))
            ->pluck('rgo_status', 'order_id')
            ->all();

        $matched = $updated = $notInSystem = 0;
        $updatedOrderIds = [];

        foreach ($sheetMap as $orderId => $sheetStatus) {
            if (!array_key_exists($orderId, $systemMap)) {
                $entries[] = [
                    'order_id' => $orderId,
                    'before'   => null,
                    'after'    => null,
                    'sheet'    => $sheetStatus,
                    'reason'   => 'not_in_system',
                ];
                $notInSystem++;
                continue;
            }

            $systemStatus = $systemMap[$orderId] !== null ? trim((string) $systemMap[$orderId]) : null;

            if ($this->statusEquals($systemStatus, $sheetStatus)) {
                $entries[] = [
                    'order_id' => $orderId,
                    'before'   => $systemStatus,
                    'after'    => $systemStatus,
                    'sheet'    => $sheetStatus,
                    'reason'   => 'matched',
                ];
                $matched++;
                continue;
            }

            OrderTracking::where('order_id', $orderId)->update([
                'rgo_status'    => $sheetStatus,
                'rgo_synced_at' => now(),
            ]);

            $entries[] = [
                'order_id' => $orderId,
                'before'   => $systemStatus,
                'after'    => $sheetStatus,
                'sheet'    => $sheetStatus,
                'reason'   => 'updated',
            ];
            $updated++;
            $updatedOrderIds[] = $orderId;
        }

        if (!empty($updatedOrderIds)) {
            app(OrderTrackingAutomationService::class)
                ->recomputeByOrderIds(array_unique($updatedOrderIds));
        }

        Cache::put('rgo_last_sync', now(), now()->addHour());

        return [
            'entries' => $entries,
            'summary' => [
                'matched'       => $matched,
                'updated'       => $updated,
                'not_in_system' => $notInSystem,
                'total'         => count($entries),
            ],
            'errors' => $errors,
        ];
    }

    public function lastSyncedAt(): ?string
    {
        $time = Cache::get('rgo_last_sync');
        return $time?->diffForHumans();
    }

    private function statusEquals(?string $a, ?string $b): bool
    {
        $normalize = fn(?string $v) => $v === null ? '' : strtolower(trim($v));
        return $normalize($a) === $normalize($b);
    }
}
