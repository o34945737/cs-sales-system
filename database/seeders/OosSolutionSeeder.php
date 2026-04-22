<?php

namespace Database\Seeders;

use App\Models\OosSolution;
use Illuminate\Database\Seeder;

class OosSolutionSeeder extends Seeder
{
    public function run(): void
    {
        $solutions = [
            'Cancel',
            'Perpanjang Masa Garansi',
            'Tawarkan Varian Lain',
        ];

        foreach ($solutions as $name) {
            OosSolution::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
