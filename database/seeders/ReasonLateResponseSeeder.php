<?php

namespace Database\Seeders;

use App\Models\ReasonLateResponse;
use Illuminate\Database\Seeder;

class ReasonLateResponseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            'No update from warehouse',
            'No response from courier',
            'Pending marketplace feedback',
            'Need internal confirmation',
            'Waiting customer response',
        ];

        foreach ($items as $name) {
            ReasonLateResponse::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
