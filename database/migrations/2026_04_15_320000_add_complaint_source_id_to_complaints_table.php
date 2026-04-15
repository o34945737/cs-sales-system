<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            // Replace enum 'source' with FK to complaint_sources table
            // Add new FK column first
            $table->foreignId('complaint_source_id')->nullable()->after('source')->constrained('complaint_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['complaint_source_id']);
            $table->dropColumn('complaint_source_id');
        });
    }
};
