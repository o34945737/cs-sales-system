<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $statuses = DB::table('order_tracking_erp_statuses')
            ->pluck('name', 'id')
            ->mapWithKeys(fn($name, $id) => [(string) $id => $name])
            ->all();

        if (empty($statuses)) {
            return;
        }

        DB::table('order_trackings')
            ->select(['id', 'erp_status'])
            ->whereNotNull('erp_status')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($statuses) {
                foreach ($rows as $row) {
                    $value = trim((string) $row->erp_status);

                    if (array_key_exists($value, $statuses)) {
                        DB::table('order_trackings')
                            ->where('id', $row->id)
                            ->update(['erp_status' => $statuses[$value]]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Intentionally left blank: converting names back to ids could be lossy
        // if master ERP statuses are edited after this migration runs.
    }
};
