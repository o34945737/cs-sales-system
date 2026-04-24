<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(BrandSeeder::class);
        $this->call(PlatformSeeder::class);
        $this->call(ComplaintSourceSeeder::class);
        $this->call(ComplaintPowerSeeder::class);
        $this->call(CauseBySeeder::class);
        $this->call(SkuCodeSeeder::class);
        $this->call(SubCaseSeeder::class);
        $this->call(LastStepSeeder::class);
        $this->call(ReasonWhitelistSeeder::class);
        $this->call(ReasonLateResponseSeeder::class);
        $this->call(OrderTrackingErpStatusSeeder::class);
        $this->call(OosReasonSeeder::class);
        $this->call(OosSolutionSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(OosSeeder::class);
        $this->call(OrderTrackingSeeder::class);
        $this->call(ComplaintSeeder::class);
    }
}
