<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            $table->index('brand', 'idx_ot_brand');
            $table->index('platform', 'idx_ot_platform');
            $table->index('order_id', 'idx_ot_order_id');
            $table->index('cs_name', 'idx_ot_cs_name');
            $table->index('status', 'idx_ot_status');
            $table->index('data_source', 'idx_ot_data_source');
            $table->index('tanggal_input', 'idx_ot_tanggal_input');
            $table->index(['brand', 'status'], 'idx_ot_brand_status');
            $table->index(['cs_name', 'status'], 'idx_ot_cs_status');
            $table->index(['tanggal_input', 'status'], 'idx_ot_date_status');
        });
    }

    public function down(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            $table->dropIndex('idx_ot_brand');
            $table->dropIndex('idx_ot_platform');
            $table->dropIndex('idx_ot_order_id');
            $table->dropIndex('idx_ot_cs_name');
            $table->dropIndex('idx_ot_status');
            $table->dropIndex('idx_ot_data_source');
            $table->dropIndex('idx_ot_tanggal_input');
            $table->dropIndex('idx_ot_brand_status');
            $table->dropIndex('idx_ot_cs_status');
            $table->dropIndex('idx_ot_date_status');
        });
    }
};
