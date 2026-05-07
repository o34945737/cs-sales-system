<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bad_reviews', function (Blueprint $table) {
            $table->index('tanggal_review', 'idx_br_tanggal_review');
            $table->index('order_id', 'idx_br_order_id');
            $table->index('cs_name', 'idx_br_cs_name');
            $table->index('status', 'idx_br_status');
            $table->index('star', 'idx_br_star');
            $table->index('brand', 'idx_br_brand');
            $table->index('platform', 'idx_br_platform');
            $table->index(['brand', 'status'], 'idx_br_brand_status');
            $table->index(['cs_name', 'status'], 'idx_br_cs_status');
            $table->index(['star', 'status'], 'idx_br_star_status');
        });
    }

    public function down(): void
    {
        Schema::table('bad_reviews', function (Blueprint $table) {
            $table->dropIndex('idx_br_tanggal_review');
            $table->dropIndex('idx_br_order_id');
            $table->dropIndex('idx_br_cs_name');
            $table->dropIndex('idx_br_status');
            $table->dropIndex('idx_br_star');
            $table->dropIndex('idx_br_brand');
            $table->dropIndex('idx_br_platform');
            $table->dropIndex('idx_br_brand_status');
            $table->dropIndex('idx_br_cs_status');
            $table->dropIndex('idx_br_star_status');
        });
    }
};
