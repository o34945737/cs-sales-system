<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Add FK for part_of_bad instead of string field
            $table->foreignId('part_of_bad_id')->nullable()->after('part_of_bad')->constrained('part_of_bads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['part_of_bad_id']);
            $table->dropColumn('part_of_bad_id');
        });
    }
};
