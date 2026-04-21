<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $activeBrands = ['ANTA', 'WHISKAS', 'CLEAR'];

        Brand::query()
            ->whereIn('name', ['ERKE', 'KAPPA'])
            ->update(['is_active' => false]);

        foreach ($activeBrands as $name) {
            Brand::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
