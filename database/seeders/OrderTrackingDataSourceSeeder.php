<?php

namespace Database\Seeders;

use App\Models\OrderTrackingDataSource;
use Illuminate\Database\Seeder;

class OrderTrackingDataSourceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'WH',
            'Finance',
            'Reject Return',
        ];

        foreach ($items as $name) {
            OrderTrackingDataSource::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
