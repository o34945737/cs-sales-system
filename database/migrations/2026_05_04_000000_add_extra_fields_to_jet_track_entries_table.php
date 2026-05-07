<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jet_track_entries', function (Blueprint $table) {
            $table->string('order_id')->nullable()->index()->after('awb');
            $table->text('source_url')->nullable()->after('order_id');
            $table->text('video_url')->nullable()->after('kondisi_barang');
            $table->text('warehouse_doc_link')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('jet_track_entries', function (Blueprint $table) {
            $table->dropColumn(['order_id', 'source_url', 'video_url', 'warehouse_doc_link']);
        });
    }
};
