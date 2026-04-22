<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_productivities', function (Blueprint $table) {
            $table->id();
            $table->string('cs_name');
            $table->date('tanggal');
            $table->integer('complaint_handled')->default(0);
            $table->integer('complaint_solved')->default(0);
            $table->integer('bad_review_handled')->default(0);
            $table->integer('order_tracking_handled')->default(0);
            $table->integer('oos_handled')->default(0);
            $table->integer('total_ticket')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['cs_name', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_productivities');
    }
};
