<?php

namespace Database\Seeders;

use App\Models\SkuCode;
use Illuminate\Database\Seeder;

class SkuCodeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'sku' => 'SKU-ANTA-RUN-001',
                'product_name' => 'Sepatu Running ANTA Flashfoam',
            ],
            [
                'sku' => 'SKU-ANTA-BASKET-002',
                'product_name' => 'Sepatu Basket ANTA Shock Wave',
            ],
            [
                'sku' => 'SKU-ANTA-LIFESTYLE-003',
                'product_name' => 'Sepatu Lifestyle ANTA Street Comfort',
            ],
        ];

        foreach ($items as $item) {
            SkuCode::query()->updateOrCreate(
                ['sku' => $item['sku']],
                ['product_name' => $item['product_name']],
            );
        }
    }
}
