<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('complaints') && Schema::hasColumn('complaints', 'part_of_bad_id')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->dropConstrainedForeignId('part_of_bad_id');
            });
        }

        if (Schema::hasTable('part_of_bads')) {
            Schema::drop('part_of_bads');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('part_of_bads')) {
            Schema::create('part_of_bads', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('complaints') && !Schema::hasColumn('complaints', 'part_of_bad_id')) {
            Schema::table('complaints', function (Blueprint $table) {
                $table->foreignId('part_of_bad_id')->nullable()->constrained('part_of_bads')->nullOnDelete();
            });
        }
    }
};
