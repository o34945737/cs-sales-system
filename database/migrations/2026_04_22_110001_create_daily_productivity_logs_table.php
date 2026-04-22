<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_productivity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->date('date');
            $table->unsignedInteger('total_complaint')->default(0);
            $table->unsignedInteger('total_bad_review')->default(0);
            $table->unsignedInteger('total_order_tracking')->default(0);
            $table->unsignedInteger('total_oos')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agent_name', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_productivity_logs');
    }
};
