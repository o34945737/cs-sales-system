<?php

namespace Database\Seeders;

use App\Models\OosSolution;
use Illuminate\Database\Seeder;

class OosSolutionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Offer refund',
            'Offer replacement product',
            'Wait restock confirmation',
            'Cancel order after customer approval',
            'Escalate to warehouse and KAE',
        ];

        foreach ($items as $name) {
            OosSolution::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
