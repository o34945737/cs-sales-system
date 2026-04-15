<?php

namespace Database\Seeders;

use App\Models\PartOfBad;
use Illuminate\Database\Seeder;

class PartOfBadSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Packing', 'Product', 'Accessory'] as $name) {
            PartOfBad::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
