<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('complaints', 'external_internal')) $table->string('external_internal')->nullable();
            if (!Schema::hasColumn('complaints', 'month')) $table->string('month')->nullable();
            if (!Schema::hasColumn('complaints', 'ai_template')) $table->string('ai_template')->nullable();
            if (!Schema::hasColumn('complaints', 'kae')) $table->string('kae')->nullable();
            if (!Schema::hasColumn('complaints', 'reason_late_handling')) $table->string('reason_late_handling')->nullable();
        });
    }

    public function down()
    {
        // No action
    }
};
