<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->string('cs_name')->nullable()->after('platform')->index();
        });
    }

    public function down(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->dropIndex(['cs_name']);
            $table->dropColumn('cs_name');
        });
    }
};
