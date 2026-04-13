<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('force_password_reset')->default(false)->after('is_active');
            $table->timestamp('last_login_at')->nullable()->after('force_password_reset');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->text('last_login_user_agent')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'force_password_reset',
                'last_login_at',
                'last_login_ip',
                'last_login_user_agent',
            ]);
        });
    }
};
