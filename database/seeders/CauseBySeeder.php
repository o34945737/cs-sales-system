<?php

namespace Database\Seeders;

use App\Models\CauseBy;
use Illuminate\Database\Seeder;

class CauseBySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'CS',
            'KAE',
            'WH',
            'ANTERAJA',
            'CHAT++',
            'CUSTOM LOGISTICS',
            'GOJEK/GRAB',
            'GTL',
            'INDOPAKET',
            'J&T',
            'JNE',
            'KURIR REKOMENDASI',
            'LEX',
            'NINJA',
            'POS',
            'SAP EXPRESS',
            'SICEPAT',
            'SPX',
            'STREAMER',
            'CUSTOMER',
            'BRAND',
            'PROMO',
            'PART',
        ];

        foreach ($items as $name) {
            CauseBy::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
