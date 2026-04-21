<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            if (!Schema::hasColumn('order_trackings', 'kondisi_barang')) {
                $table->string('kondisi_barang')->nullable()->after('automation_track');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('order_trackings', 'kondisi_barang')) {
                $table->dropColumn('kondisi_barang');
            }
        });
    }
};
