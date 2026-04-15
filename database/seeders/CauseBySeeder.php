<?php

namespace Database\Seeders;

use App\Models\CauseBy;
use Illuminate\Database\Seeder;

class CauseBySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'J&T',
            'SAP EXPRESS',
            'ANTERAJA',
            'LEX',
            'POS',
            'NINJA',
            'SICEPAT',
            'KURIR REKOMENDASI',
            'SPX',
            'INDOPAKET',
            'GTL',
            'CUSTOM LOGISTICS',
            'GRAB',
            'JNE',
            'GOJEK',
            'CS',
            'Chat++',
            'STREAMER',
            'KAE',
            'WH',
            'PART',
            'BRAND',
            'CUSTOMER',
            'PROMO',
        ];

        foreach ($items as $name) {
            CauseBy::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
