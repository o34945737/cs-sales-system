<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Check existing and add missing
            if (!Schema::hasColumn('complaints', 'summary_case')) $table->text('summary_case')->nullable();
            if (!Schema::hasColumn('complaints', 'update_ai')) $table->text('update_ai')->nullable();
            if (!Schema::hasColumn('complaints', 'phoenix')) $table->text('phoenix')->nullable();
            if (!Schema::hasColumn('complaints', 'available_qty')) $table->integer('available_qty')->nullable();
            if (!Schema::hasColumn('complaints', 'status_qty')) $table->string('status_qty')->nullable();
            if (!Schema::hasColumn('complaints', 'report_category')) $table->string('report_category')->nullable();
            if (!Schema::hasColumn('complaints', 'complaint_power')) $table->string('complaint_power')->nullable(); // HARD/NORMAL
            if (!Schema::hasColumn('complaints', 'auto_sync_sla')) $table->string('auto_sync_sla')->nullable();
        });
    }

    public function down()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'summary_case', 
                'update_ai', 
                'phoenix', 
                'available_qty', 
                'status_qty',
                'report_category',
                'complaint_power',
                'auto_sync_sla'
            ]);
        });
    }
};
