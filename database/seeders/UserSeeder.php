<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@example.com',
                'name' => 'Super Admin',
                'password' => '12345678',
                'is_active' => true,
                'role' => 'Super Admin',
            ],
            [
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => '12345678',
                'is_active' => true,
                'role' => 'CS',
            ],
            [
                'email' => 'cs.anta@example.com',
                'name' => 'CS ANTA 1',
                'password' => '12345678',
                'is_active' => true,
                'role' => 'CS',
            ],
            [
                'email' => 'cs.escalation@example.com',
                'name' => 'CS Escalation',
                'password' => '12345678',
                'is_active' => true,
                'role' => 'CS',
            ],
            [
                'email' => 'kae.anta@example.com',
                'name' => 'KAE ANTA',
                'password' => '12345678',
                'is_active' => true,
                'role' => 'KAE',
            ],
        ];

        foreach ($users as $item) {
            $user = User::query()->updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['name'],
                    'password' => $item['password'],
                    'is_active' => $item['is_active'],
                ],
            );

            $user->syncRoles([Role::findByName($item['role'])]);
        }

        User::query()
            ->with('roles')
            ->get()
            ->filter(fn (User $existingUser) => $existingUser->roles->isEmpty())
            ->each(function (User $existingUser): void {
                $defaultRole = User::role('Super Admin')->exists() ? 'CS' : 'Super Admin';
                $existingUser->assignRole(Role::findByName($defaultRole));
            });
    }
}
