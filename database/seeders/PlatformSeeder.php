<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            ['name' => 'SHOPEE',    'tts_days' => null],
            ['name' => 'TOKOPEDIA', 'tts_days' => null],
            ['name' => 'TIKTOK',    'tts_days' => null],
            ['name' => 'LAZADA',    'tts_days' => 24],
            ['name' => 'BLIBLI',    'tts_days' => null],
        ];

        foreach ($platforms as $platform) {
            Platform::query()->updateOrCreate(
                ['name' => $platform['name']],
                ['is_active' => true, 'tts_days' => $platform['tts_days']],
            );
        }
    }
}
