<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            if (!Schema::hasColumn('order_trackings', 'rgo_status')) {
                $table->string('rgo_status')->nullable()->after('reason_late_respons');
                $table->index('rgo_status');
            }
            if (!Schema::hasColumn('order_trackings', 'rgo_synced_at')) {
                $table->timestamp('rgo_synced_at')->nullable()->after('rgo_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('order_trackings', 'rgo_status')) {
                $table->dropIndex(['rgo_status']);
                $table->dropColumn('rgo_status');
            }
            if (Schema::hasColumn('order_trackings', 'rgo_synced_at')) {
                $table->dropColumn('rgo_synced_at');
            }
        });
    }
};
