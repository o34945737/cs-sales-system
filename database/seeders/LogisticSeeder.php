<?php

namespace Database\Seeders;

use App\Models\Logistic;
use Illuminate\Database\Seeder;

class LogisticSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['JNE', 'J&T', 'SPX', 'ANTERAJA', 'NINJA XPRESS'] as $name) {
            Logistic::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
