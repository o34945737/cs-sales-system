<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Add FK for complaint_power instead of string field
            $table->foreignId('complaint_power_id')->nullable()->after('complaint_power')->constrained('complaint_powers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['complaint_power_id']);
            $table->dropColumn('complaint_power_id');
        });
    }
};
