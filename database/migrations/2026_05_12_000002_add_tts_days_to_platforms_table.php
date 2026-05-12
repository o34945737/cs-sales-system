<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->unsignedSmallInteger('tts_days')->nullable()->after('is_active');
        });

        // Seed existing Lazada platform(s) with the previously hard-coded 24-day value
        DB::table('platforms')
            ->whereRaw('LOWER(name) = ?', ['lazada'])
            ->update(['tts_days' => 24]);
    }

    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn('tts_days');
        });
    }
};
