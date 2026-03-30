<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            
            // --- A. Field Input ---
            $table->string('source')->nullable();
            $table->date('tanggal_complaint')->nullable();
            $table->date('tanggal_order')->nullable();
            $table->time('jam_customer_complaint')->nullable();
            $table->string('brand')->nullable();
            $table->string('platform')->nullable();
            $table->string('order_id')->nullable();
            $table->string('username')->nullable();
            $table->string('resi')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('value_of_product', 15, 2)->nullable();
            $table->string('product_name')->nullable();
            $table->integer('qty')->nullable(); // Untuk autofill berdasarkan SKU
            $table->string('sub_case')->nullable();
            $table->string('cause_by')->nullable();
            $table->longText('update_long_text')->nullable();
            $table->string('part_of_bad')->nullable();
            $table->string('cs_name')->nullable();
            $table->string('last_step')->nullable();
            $table->string('step_cs_selesai')->nullable(); // YES / NO
            $table->string('level_customer')->nullable(); // Hard / Normal
            $table->dateTime('tanggal_step_cs_selesai')->nullable();
            $table->dateTime('tanggal_update')->nullable();
            $table->string('video_unboxing')->nullable();
            $table->string('proof')->nullable();
            $table->string('reason_whitelist')->nullable();
            $table->string('reason_late_respons')->nullable();

            // --- B. Field Automation ---
            $table->string('cycle')->nullable();
            $table->string('status')->nullable();
            $table->string('priority')->nullable();
            $table->string('category_customer')->nullable();
            $table->string('riwayat_oos')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('complaints');
    }
};
