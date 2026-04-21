<?php

namespace Database\Seeders;

use App\Models\OrderTrackingErpStatus;
use Illuminate\Database\Seeder;

class OrderTrackingErpStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Open', 'sort_order' => 1],
            ['name' => 'Proses', 'sort_order' => 2],
            ['name' => 'Shipped', 'sort_order' => 3],
            ['name' => 'Delivered', 'sort_order' => 4],
            ['name' => 'Return Request', 'sort_order' => 5],
            ['name' => 'Returned', 'sort_order' => 6],
            ['name' => 'Reject', 'sort_order' => 7],
            ['name' => 'Cancel', 'sort_order' => 8],
            ['name' => 'Closed', 'sort_order' => 9],
        ];

        foreach ($statuses as $status) {
            OrderTrackingErpStatus::updateOrCreate(
                ['name' => $status['name']],
                ['is_active' => true, 'sort_order' => $status['sort_order']]
            );
        }
    }
}
