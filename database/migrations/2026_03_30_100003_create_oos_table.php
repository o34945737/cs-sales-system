<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('oos', function (Blueprint $table) {
            $table->id();

            // --- A. Field Input 1-13 ---
            $table->date('tanggal_input')->nullable();
            $table->string('brand')->nullable();
            $table->string('platform')->nullable();
            $table->string('order_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('reason')->nullable();
            $table->string('solusi')->nullable();
            $table->longText('note_detail_varian')->nullable();
            $table->string('update_cs')->nullable(); // Done Blast, Cancel
            $table->date('tanggal_blast')->nullable();
            $table->longText('feedback_customers')->nullable();
            $table->string('month')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('oos');
    }
};
