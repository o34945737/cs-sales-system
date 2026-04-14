<?php

namespace Database\Seeders;

use App\Models\OosReason;
use Illuminate\Database\Seeder;

class OosReasonSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Stock kosong di gudang',
            'Inventory mismatch',
            'Barang rusak sebelum kirim',
            'Barang tertahan inbound',
            'Sinkronisasi stok marketplace terlambat',
        ];

        foreach ($items as $name) {
            OosReason::query()->updateOrCreate(
                ['name' => $name],
                ['is_active' => true],
            );
        }
    }
}
