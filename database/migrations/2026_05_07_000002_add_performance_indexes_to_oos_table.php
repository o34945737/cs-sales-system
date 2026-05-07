<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->index('tanggal_input', 'idx_oos_tanggal_input');
            $table->index('brand', 'idx_oos_brand');
            $table->index('platform', 'idx_oos_platform');
            $table->index('order_id', 'idx_oos_order_id');
            $table->index('update_cs', 'idx_oos_update_cs');
            $table->index(['brand', 'update_cs'], 'idx_oos_brand_update_cs');
            $table->index(['tanggal_input', 'update_cs'], 'idx_oos_date_update_cs');
        });
    }

    public function down(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->dropIndex('idx_oos_tanggal_input');
            $table->dropIndex('idx_oos_brand');
            $table->dropIndex('idx_oos_platform');
            $table->dropIndex('idx_oos_order_id');
            $table->dropIndex('idx_oos_update_cs');
            $table->dropIndex('idx_oos_brand_update_cs');
            $table->dropIndex('idx_oos_date_update_cs');
        });
    }
};
