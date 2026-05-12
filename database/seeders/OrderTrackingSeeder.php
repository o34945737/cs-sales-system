<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\OrderTracking;
use App\Models\Platform;
use App\Models\SubCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OrderTrackingSeeder extends Seeder
{
    public function run(): void
    {
        $brand = Brand::query()->where('name', 'ANTA')->first();
        $platform = Platform::query()->where('name', 'SHOPEE')->first();
        $agent = User::query()->whereHas('roles', fn($q) => $q->where('name', 'CS'))->first();
        $subCase = SubCase::query()->where('name', 'Late Delivery')->first();

        if (!$brand || !$platform || !$agent || !$subCase) {
            return;
        }

        $items = [
            [
                'data_source' => 'WH',
                'tanggal_input' => now()->toDateString(),
                'tanggal_order' => now()->subDays(2)->toDateString(),
                'brand' => $brand->name,
                'platform' => $platform->name,
                'order_id' => 'ORD-TRACK-1001',
                'value' => 500000,
                'cause_by' => $subCase->default_cause_by ?? 'J&T',
                'awb' => 'AWB-TRACK-1001',
                'erp_status' => 'Open',
                'payment_method' => 'NON COD',
                'wh_note' => 'Barang sudah dipacking',
                'cs_name' => $agent->name,
                'category' => $subCase->name,
                'last_step' => 'Follow Up Courier (MP Non aktif)',
                'update' => 'Menunggu update dari kurir',
                'tanggal_update' => now()->toDateTimeString(),
                'value_receive' => null,
                'insurance_info' => 'N',
            ],
            [
                'data_source' => 'Finance',
                'tanggal_input' => now()->toDateString(),
                'tanggal_order' => now()->subDays(5)->toDateString(),
                'brand' => $brand->name,
                'platform' => 'LAZADA',
                'order_id' => 'ORD-TRACK-1002',
                'value' => 750000,
                'cause_by' => 'LEX',
                'awb' => 'AWB-TRACK-1002',
                'erp_status' => 'Pending',
                'payment_method' => 'NON COD',
                'wh_note' => null,
                'cs_name' => $agent->name,
                'category' => 'Misunderstanding of the product',
                'last_step' => 'Seller Win',
                'update' => 'Selesai',
                'tanggal_update' => now()->toDateTimeString(),
                'value_receive' => 750000,
                'insurance_info' => 'Y',
            ],
        ];

        foreach ($items as $item) {
            OrderTracking::create($item);
        }
    }
}
