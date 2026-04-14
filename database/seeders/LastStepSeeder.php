<?php

namespace Database\Seeders;

use App\Models\LastStep;
use Illuminate\Database\Seeder;

class LastStepSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Claim Receive (10x shipping fee)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Claim Receive (Full)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Claim Reject', 'status_result' => 'Whitelist', 'priority_level' => 'Mines'],
            ['name' => 'Complaint Canceled by buyer/No Respons', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Follow Up Courier (MP Non aktif)', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'Analysis MP (Late Delivery)', 'status_result' => 'Pending', 'priority_level' => 'P5'],
            ['name' => 'Analysis MP (Non Late Delivery)', 'status_result' => 'Pending', 'priority_level' => 'P1'],
            ['name' => 'Kingdee Processing (Waiting AWB for replacement product)', 'status_result' => 'Pending', 'priority_level' => 'P6'],
            ['name' => 'On the way return & plan banding', 'status_result' => 'Pending', 'priority_level' => 'P2'],
            ['name' => 'On the way return & plan refund', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'On the way return & plan replace', 'status_result' => 'Pending', 'priority_level' => 'P4'],
            ['name' => 'Pending return & plan banding', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'Pending return & plan refund', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'Pending return & plan replace', 'status_result' => 'Pending', 'priority_level' => 'P4'],
            ['name' => 'Pending RGO & plan refund', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'Product has been delivered (Late Delivery)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Refund has been transferred by finance (SPF)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Refund processing by finance (SPF)', 'status_result' => 'Pending', 'priority_level' => 'P6'],
            ['name' => 'Replacement product on the way', 'status_result' => 'Pending', 'priority_level' => 'P6'],
            ['name' => 'Return Refund (Full)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Return Refund (Partial)', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'Seller Win', 'status_result' => 'Solved', 'priority_level' => 'Cool'],
            ['name' => 'The replacement product has been received by the buyer', 'status_result' => 'Solved', 'priority_level' => null],
            ['name' => 'Follow Up to After Sales Team', 'status_result' => 'Pending', 'priority_level' => 'P1'],
            ['name' => 'Waiting Claim', 'status_result' => 'Pending', 'priority_level' => 'P7'],
            ['name' => 'Waiting Money Receive', 'status_result' => 'Pending', 'priority_level' => 'P7'],
            ['name' => 'Waiting Data From Customer', 'status_result' => 'Pending', 'priority_level' => 'P3'],
            ['name' => 'Follow Up KAE to Brand', 'status_result' => 'Pending', 'priority_level' => 'P2'],
            ['name' => 'Follow Up WH', 'status_result' => 'Pending', 'priority_level' => 'P1'],
            ['name' => 'Follow Up KAE to KAM', 'status_result' => 'Pending', 'priority_level' => 'P2'],
            ['name' => 'Return not authorized', 'status_result' => 'Pending', 'priority_level' => 'P5'],
            ['name' => 'Return follow-up (No further action)', 'status_result' => 'Solved', 'priority_level' => null],
        ];

        foreach ($items as $item) {
            LastStep::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'status_result' => $item['status_result'],
                    'priority_level' => $item['priority_level'],
                    'is_active' => true,
                ],
            );
        }
    }
}
