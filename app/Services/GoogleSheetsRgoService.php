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
     * Pull RGO data from Google Sheet and update order_trackings.
     * Sheet must have heading row with at least: order_id, rgo_status
     *
     * @return array{updated: int, skipped: int, errors: string[]}
     */
    public function sync(): array
    {
        $results = ['updated' => 0, 'skipped' => 0, 'errors' => []];

        $rows = Sheets::spreadsheet($this->spreadsheetId)
            ->sheet($this->sheetName)
            ->get();

        if ($rows->isEmpty()) {
            $results['errors'][] = 'Sheet kosong atau tidak ditemukan.';
            return $results;
        }

        $header = $rows->first();
        $data   = Sheets::collection($header, $rows->skip(1));

        $updatedOrderIds = [];

        foreach ($data as $i => $row) {
            $orderId   = ltrim(trim((string) ($row->get('order_id') ?? '')), '#');
            $rgoStatus = trim((string) ($row->get('rgo_status') ?? ''));

            if (!$orderId) {
                $results['skipped']++;
                continue;
            }

            $affected = OrderTracking::where('order_id', $orderId)
                ->update([
                    'rgo_status'    => $rgoStatus ?: null,
                    'rgo_synced_at' => now(),
                ]);

            if ($affected > 0) {
                $results['updated'] += $affected;
                $updatedOrderIds[]   = $orderId;
            } else {
                $results['skipped']++;
            }
        }

        if (!empty($updatedOrderIds)) {
            app(OrderTrackingAutomationService::class)
                ->recomputeByOrderIds(array_unique($updatedOrderIds));
        }

        Cache::put('rgo_last_sync', now(), now()->addHour());

        return $results;
    }

    public function lastSyncedAt(): ?string
    {
        $time = Cache::get('rgo_last_sync');
        return $time?->diffForHumans();
    }
}
