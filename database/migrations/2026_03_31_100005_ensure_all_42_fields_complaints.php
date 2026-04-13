<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Unify names with those provided in the latest list
            if (!Schema::hasColumn('complaints', 'external_internal')) {
                $table->string('external_internal')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'month')) {
                $table->string('month')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'ai_template')) {
                $table->string('ai_template')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'kae')) {
                $table->string('kae')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'reason_late_handling')) {
                $table->string('reason_late_handling')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'summary_case')) {
                $table->text('summary_case')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'update_ai')) {
                $table->text('update_ai')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'available_qty')) {
                $table->integer('available_qty')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'status_qty')) {
                $table->string('status_qty')->nullable();
            }
            if (!Schema::hasColumn('complaints', 'sla')) {
                $table->integer('sla')->default(0);
            }
            if (!Schema::hasColumn('complaints', 'oos')) {
                $table->string('oos')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropColumn([
                'external_internal', 'month', 'ai_template', 'kae', 
                'reason_late_handling', 'summary_case', 'update_ai', 
                'available_qty', 'status_qty', 'sla', 'oos'
            ]);
        });
    }
};
