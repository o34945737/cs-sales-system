<?php

namespace Database\Seeders;

use App\Models\ComplaintPower;
use Illuminate\Database\Seeder;

class ComplaintPowerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Hard Complaint', 'Normal Complaint'] as $name) {
            ComplaintPower::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
