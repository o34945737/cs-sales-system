<?php

namespace Database\Seeders;

use App\Models\ReasonWhitelist;
use Illuminate\Database\Seeder;

class ReasonWhitelistSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'No repacking indication',
            'Packing not proper',
            "Customer's evidence is stronger than us",
            'Our evidences are not strong (platform T&C)',
            'CCTV does not show receipt number',
            'Late Respons',
        ];

        foreach ($items as $name) {
            ReasonWhitelist::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
