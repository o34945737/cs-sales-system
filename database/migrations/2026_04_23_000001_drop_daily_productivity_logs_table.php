<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('daily_productivity_logs');
    }

    public function down(): void
    {
        Schema::create('daily_productivity_logs', function (\Illuminate\Database\Schema\Blueprint $table) {
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
};
