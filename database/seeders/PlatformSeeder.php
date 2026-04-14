<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['SHOPEE', 'TOKOPEDIA', 'TIKTOK', 'LAZADA', 'BLIBLI'] as $name) {
            Platform::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
