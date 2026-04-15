<?php

namespace Database\Seeders;

use App\Models\ComplaintSource;
use Illuminate\Database\Seeder;

class ComplaintSourceSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'After Sales',
            'Pre Sales',
            'Brand',
            'KAE',
            'Socmed',
        ];

        foreach ($items as $name) {
            ComplaintSource::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
