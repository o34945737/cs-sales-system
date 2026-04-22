<?php

namespace Database\Seeders;

use App\Models\OosReason;
use Illuminate\Database\Seeder;

class OosReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            'Wrong Price',
            'Stock Damage',
            'OOS No Bugs',
            'Bounce Back',
            'Delay Inbound',
        ];

        foreach ($reasons as $name) {
            OosReason::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
