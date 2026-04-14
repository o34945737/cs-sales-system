<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['ANTA', 'ERKE', 'KAPPA'] as $name) {
            Brand::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
