<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Complaint;
use Carbon\Carbon;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        // User A: Komplain pertama (History harus NULL)
        Complaint::create([
            'source' => 'After Sales',
            'brand' => 'ANTA',
            'platform' => 'BLIBLI',
            'tanggal_complaint' => Carbon::now()->subDays(10)->toDateString(),
            'jam_customer_complaint' => '10:00',
            'order_id' => 'ORD-001',
            'username' => 'user_perdana',
            'product_name' => 'Sepatu Running A1',
            'summary_case' => 'Ukuran tidak pas',
            'last_step' => 'Pending',
            'cs_name' => 'cs1',
            'status' => 'Pending',
            'priority' => 'P2',
        ]);

        // User B: Komplain Berulang (Total 2x)
        // 1. Komplain Pertama
        Complaint::create([
            'source' => 'After Sales',
            'brand' => 'ANTA',
            'platform' => 'SHOPEE',
            'tanggal_complaint' => Carbon::now()->subDays(5)->toDateString(),
            'jam_customer_complaint' => '11:00',
            'order_id' => 'ORD-002',
            'username' => 'user_repeat',
            'product_name' => 'Sepatu Basket B2',
            'summary_case' => 'Warna luntur',
            'last_step' => 'On Process',
            'cs_name' => 'cs1',
            'status' => 'Pending',
            'priority' => 'P1',
        ]);
        // 2. Komplain Kedua (History harus terisi otomatis "Customer ini complaint ke 2")
        Complaint::create([
            'source' => 'Sales',
            'brand' => 'ANTA',
            'platform' => 'TOKOPEDIA',
            'tanggal_complaint' => Carbon::now()->toDateString(),
            'jam_customer_complaint' => '14:00',
            'order_id' => 'ORD-003',
            'username' => 'user_repeat',
            'product_name' => 'Sepatu Basket B2',
            'summary_case' => 'Sol lepas',
            'last_step' => 'Solved',
            'cs_name' => 'cs1',
            'status' => 'Solved',
            'priority' => 'P1',
        ]);
    }
}
