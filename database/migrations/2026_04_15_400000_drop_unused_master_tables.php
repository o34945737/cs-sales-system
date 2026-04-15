<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unused master tables that are not linked to complaints
        Schema::dropIfExists('logistics');
        Schema::dropIfExists('oos_reasons');
        Schema::dropIfExists('oos_solutions');
        Schema::dropIfExists('order_tracking_data_sources');
    }

    public function down(): void
    {
        // Recreate tables if needed for rollback
        // (In production, you may want to keep backups)
    }
};
