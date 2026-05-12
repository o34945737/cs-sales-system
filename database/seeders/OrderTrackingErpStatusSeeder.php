<?php

namespace Database\Seeders;

use App\Models\OrderTrackingErpStatus;
use Illuminate\Database\Seeder;

class OrderTrackingErpStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Open',      'sort_order' => 1, 'is_active' => true],
            ['name' => 'Proses',    'sort_order' => 2, 'is_active' => true],
            ['name' => 'Shipped',   'sort_order' => 3, 'is_active' => true],
            ['name' => 'Delivered', 'sort_order' => 4, 'is_active' => true],
            ['name' => 'Closed',    'sort_order' => 5, 'is_active' => true],
        ];

        OrderTrackingErpStatus::query()
            ->whereNotIn('name', array_column($statuses, 'name'))
            ->update(['is_active' => false]);

        foreach ($statuses as $status) {
            OrderTrackingErpStatus::updateOrCreate(
                ['name' => $status['name']],
                ['is_active' => $status['is_active'], 'sort_order' => $status['sort_order']]
            );
        }
    }
}
