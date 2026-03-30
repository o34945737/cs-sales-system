<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bad_reviews', function (Blueprint $table) {
            $table->id();

            // --- A. Field Input 1-16 ---
            $table->date('tanggal_review')->nullable();
            $table->string('brand')->nullable();
            $table->string('platform')->nullable();
            $table->string('order_id')->nullable();
            $table->string('username')->nullable();
            $table->integer('star')->nullable(); // 1/2/3
            $table->string('product_name')->nullable();
            $table->string('sku')->nullable();
            $table->string('category_review')->nullable(); // Sama dngn sub case
            $table->string('cause_by')->nullable();
            $table->longText('review_notes')->nullable();
            $table->string('progress')->nullable(); // Follow Up Customer, Auto Reply
            $table->dateTime('tanggal_update')->nullable();
            $table->string('cs_name')->nullable();
            
            // --- B. Field Automation ---
            $table->string('month')->nullable(); // Misal: January 2026
            $table->string('status')->nullable(); // Pending & Solved

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bad_reviews');
    }
};
