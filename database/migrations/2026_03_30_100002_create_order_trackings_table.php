<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_trackings', function (Blueprint $table) {
            $table->id();
            
            // --- A. Field Input 1-29 ---
            $table->string('data_source')->nullable(); // WH, Finance, Reject Return
            $table->date('tanggal_input')->nullable();
            $table->date('tanggal_order')->nullable();
            $table->string('brand')->nullable();
            $table->string('platform')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('value', 15, 2)->nullable();
            $table->string('logistics')->nullable();
            $table->string('awb')->nullable();
            $table->string('erp_status')->nullable();
            $table->string('payment_method')->nullable();
            $table->longText('wh_note')->nullable();
            $table->string('cs_name')->nullable();
            $table->string('category')->nullable(); // = sub case
            $table->string('last_step')->nullable();
            $table->longText('update')->nullable();
            $table->dateTime('tanggal_update')->nullable();
            $table->decimal('value_receive', 15, 2)->nullable();
            $table->string('insurance_info')->nullable(); // Y/N
            $table->string('video_unboxing_wh')->nullable(); 
            $table->string('bap_wh')->nullable();
            $table->longText('update_wh')->nullable();
            $table->longText('update_finance')->nullable();
            
            $table->string('status')->nullable();
            $table->string('month')->nullable();
            $table->string('automation_track')->nullable();
            $table->date('tanggal_tts')->nullable();
            $table->string('reason_whitelist')->nullable();
            $table->string('reason_late_respons')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_trackings');
    }
};
