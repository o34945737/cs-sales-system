<?php

namespace Database\Seeders;

use App\Models\LastStep;
use Illuminate\Database\Seeder;

class LastStepSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // === External (involves courier / marketplace / customer) ===
            ['name' => 'Analysis MP (Late Delivery)',                              'status_result' => 'Pending', 'priority_level' => 'P5', 'type' => 'External'],
            ['name' => 'Analysis MP (Non Late Delivery)',                          'status_result' => 'Pending', 'priority_level' => 'P1', 'type' => 'External'],
            ['name' => 'Follow Up Courier (MP Non aktif)',                         'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'On the way return & plan banding',                         'status_result' => 'Pending', 'priority_level' => 'P2', 'type' => 'External'],
            ['name' => 'On the way return & plan refund',                          'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'On the way return & plan replace',                         'status_result' => 'Pending', 'priority_level' => 'P4', 'type' => 'External'],
            ['name' => 'Pending return & plan banding',                            'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'Pending return & plan refund',                             'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'Pending return & plan replace',                            'status_result' => 'Pending', 'priority_level' => 'P4', 'type' => 'External'],
            ['name' => 'Pending RGO & plan refund',                               'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'Waiting Claim',                                            'status_result' => 'Pending', 'priority_level' => 'P7', 'type' => 'External'],
            ['name' => 'Waiting Data From Customer',                               'status_result' => 'Pending', 'priority_level' => 'P3', 'type' => 'External'],
            ['name' => 'Waiting Money Receive',                                    'status_result' => 'Pending', 'priority_level' => 'P7', 'type' => 'External'],
            ['name' => 'Return not authorized',                                    'status_result' => 'Pending', 'priority_level' => 'P5', 'type' => 'External'],

            // === Internal (involves internal teams) ===
            ['name' => 'Follow Up to After Sales Team',                            'status_result' => 'Pending', 'priority_level' => 'P1', 'type' => 'Internal'],
            ['name' => 'Follow Up KAE to Brand',                                   'status_result' => 'Pending', 'priority_level' => 'P2', 'type' => 'Internal'],
            ['name' => 'Follow Up KAE to KAM',                                     'status_result' => 'Pending', 'priority_level' => 'P2', 'type' => 'Internal'],
            ['name' => 'Follow Up WH',                                             'status_result' => 'Pending', 'priority_level' => 'P1', 'type' => 'Internal'],
            ['name' => 'Kingdee Processing (Waiting AWB for replacement product)', 'status_result' => 'Pending', 'priority_level' => 'P6', 'type' => 'Internal'],
            ['name' => 'Replacement product on the way',                           'status_result' => 'Pending', 'priority_level' => 'P6', 'type' => 'Internal'],
            ['name' => 'Refund processing by finance (SPF)',                       'status_result' => 'Pending', 'priority_level' => 'P6', 'type' => 'Internal'],

            // === Solved / Whitelist (no dashboard pending type) ===
            ['name' => 'Claim Receive (10x shipping fee)',                         'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Claim Receive (Full)',                                     'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Claim Reject',                                             'status_result' => 'Whitelist', 'priority_level' => 'Mines', 'type' => null],
            ['name' => 'Complaint Canceled by buyer/No Respons',                  'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Product has been delivered (Late Delivery)',               'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Refund has been transferred by finance (SPF)',             'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Return Refund (Full)',                                     'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Return Refund (Partial)',                                  'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Seller Win',                                               'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'The replacement product has been received by the buyer',   'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
            ['name' => 'Return follow-up (No further action)',                     'status_result' => 'Solved',    'priority_level' => 'Cool', 'type' => null],
        ];

        foreach ($items as $item) {
            LastStep::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'status_result' => $item['status_result'],
                    'priority_level' => $item['priority_level'],
                    'type' => $item['type'],
                    'is_active' => true,
                ],
            );
        }
    }
}
