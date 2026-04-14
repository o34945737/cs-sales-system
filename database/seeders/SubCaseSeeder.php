<?php

namespace Database\Seeders;

use App\Models\SubCase;
use Illuminate\Database\Seeder;

class SubCaseSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Bad Quality Product', 'default_cause_by' => 'BRAND'],
            ['name' => 'Bad Service', 'default_cause_by' => null],
            ['name' => 'Change Mind', 'default_cause_by' => 'CUSTOMER'],
            ['name' => 'Damaged Packaging', 'default_cause_by' => null],
            ['name' => 'Damaged Product', 'default_cause_by' => null],
            ['name' => 'Expired', 'default_cause_by' => 'BRAND'],
            ['name' => 'Fake Return', 'default_cause_by' => null],
            ['name' => 'Late Delivery', 'default_cause_by' => null],
            ['name' => 'Under Delivery Product', 'default_cause_by' => null],
            ['name' => 'Misunderstanding of the product', 'default_cause_by' => 'CUSTOMER'],
            ['name' => 'No Reason', 'default_cause_by' => null],
            ['name' => 'Promotion', 'default_cause_by' => 'PROMO'],
            ['name' => 'Wrong Product', 'default_cause_by' => null],
            ['name' => 'OOS', 'default_cause_by' => 'KAE'],
            ['name' => 'Refund DP', 'default_cause_by' => null],
            ['name' => 'Lost Product', 'default_cause_by' => null],
        ];

        foreach ($items as $item) {
            SubCase::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'default_cause_by' => $item['default_cause_by'],
                    'is_active' => true,
                ],
            );
        }
    }
}
