<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// Wajib memanggil class Role milik Spatie
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar semua Role/Divisi yang ada di spek sistem awal Anda
        $roles = [
            'Super Admin',
            'CS',
            'Finance',
            'WH',           // Warehouse (Gudang)
            'KAE',
            'After Sales', 
            'Brand'
        ];

        foreach ($roles as $role) {
            // firstOrCreate berfungsi agar tidak ada duplikat jabatan
            Role::firstOrCreate(['name' => $role]);
        }
    }
}
