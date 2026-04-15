<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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
        $this->call(ComplaintStepStatusSeeder::class);
        $this->call(PartOfBadSeeder::class);
        $this->call(LogisticSeeder::class);
        $this->call(CauseBySeeder::class);
        $this->call(SubCaseSeeder::class);
        $this->call(LastStepSeeder::class);
        $this->call(ReasonWhitelistSeeder::class);
        $this->call(ReasonLateResponseSeeder::class);
        $this->call(OrderTrackingDataSourceSeeder::class);
        $this->call(OosReasonSeeder::class);
        $this->call(OosSolutionSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $admin->assignRole(Role::findByName('Super Admin'));

        $user = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'is_active' => true,
            ]
        );
        $user->assignRole(Role::findByName('CS'));

        User::query()
            ->with('roles')
            ->get()
            ->filter(fn(User $existingUser) => $existingUser->roles->isEmpty())
            ->each(function (User $existingUser): void {
                $defaultRole = User::role('Super Admin')->exists() ? 'CS' : 'Super Admin';
                $existingUser->assignRole(Role::findByName($defaultRole));
            });
    }
}
