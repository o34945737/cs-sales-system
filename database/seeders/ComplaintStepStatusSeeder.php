<?php

namespace Database\Seeders;

use App\Models\ComplaintStepStatus;
use Illuminate\Database\Seeder;

class ComplaintStepStatusSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['YES', 'NO'] as $name) {
            ComplaintStepStatus::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
