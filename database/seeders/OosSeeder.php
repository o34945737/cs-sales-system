<?php

namespace Database\Seeders;

use App\Models\Oos;
use Illuminate\Database\Seeder;

class OosSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'order_id' => 'ORD-ANTA-1002',
                'tanggal_input' => now()->subDays(3)->toDateString(),
                'brand' => 'ANTA',
                'platform' => 'SHOPEE',
                'cs_name' => 'CS Escalation',
                'product_name' => 'Sepatu Basket ANTA Shock Wave',
                'sku' => 'SKU-ANTA-BASKET-002',
                'reason' => 'OOS No Bugs',
                'solusi' => 'Tawarkan Varian Lain',
                'note_detail_varian' => 'Warna hitam size 42 kosong di gudang utama.',
                'update_cs' => 'Done Blast',
                'tanggal_blast' => now()->subDays(2)->toDateString(),
                'feedback_customers' => 'Customer menunggu penawaran varian pengganti.',
            ],
        ];

        foreach ($items as $item) {
            Oos::query()->updateOrCreate(
                ['order_id' => $item['order_id']],
                $item,
            );
        }
    }
}
