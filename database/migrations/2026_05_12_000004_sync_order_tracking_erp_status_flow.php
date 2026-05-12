<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $flow = [
        1 => 'Open',
        2 => 'Proses',
        3 => 'Shipped',
        4 => 'Delivered',
        5 => 'Closed',
    ];

    public function up(): void
    {
        foreach ($this->flow as $sortOrder => $name) {
            DB::table('order_tracking_erp_statuses')->updateOrInsert(
                ['name' => $name],
                [
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('order_tracking_erp_statuses')
            ->whereNotIn('name', array_values($this->flow))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $legacyMap = [
            ['1', 'Open'],
            ['2', 'Proses'],
            ['3', 'Shipped'],
            ['4', 'Delivered'],
            ['5', 'Closed'],
            ['Return Request', 'Closed'],
        ];

        foreach ($legacyMap as [$from, $to]) {
            DB::table('order_trackings')
                ->whereRaw('erp_status = ?', [$from])
                ->update(['erp_status' => $to]);

            DB::table('order_tracking_erp_status_histories')
                ->whereRaw('erp_status = ?', [$from])
                ->update(['erp_status' => $to]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank: restoring previous master status choices
        // could conflict with user-managed ERP status changes.
    }
};
