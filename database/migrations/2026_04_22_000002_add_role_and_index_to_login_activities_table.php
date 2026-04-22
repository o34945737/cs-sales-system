<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->string('role')->nullable()->after('email');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::table('login_activities', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn('role');
        });
    }
};
