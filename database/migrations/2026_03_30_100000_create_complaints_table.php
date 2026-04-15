<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | A. INPUT / TRANSACTION DATA
            |--------------------------------------------------------------------------
            */

            $table->enum('source', [
                'After Sales',
                'Pre Sales',
                'Brand',
                'KAE',
                'Socmed',
            ])->nullable();

            $table->date('tanggal_complaint')->nullable()->index();
            $table->date('tanggal_order')->nullable();
            $table->time('jam_customer_complaint')->nullable();

            // Relasi ke master
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->nullOnDelete();
            $table->foreignId('sku_code_id')->nullable()->constrained('sku_codes')->nullOnDelete();
            $table->foreignId('sub_case_id')->nullable()->constrained('sub_cases')->nullOnDelete();
            $table->foreignId('cause_id')->nullable()->constrained('cause_bys')->nullOnDelete();
            $table->foreignId('last_step_id')->nullable()->constrained('last_steps')->nullOnDelete();
            $table->foreignId('reason_whitelist_id')->nullable()->constrained('reason_whitelists')->nullOnDelete();
            $table->foreignId('reason_late_response_id')->nullable()->constrained('reason_late_responses')->nullOnDelete();

            // CS sebaiknya relasi ke users
            $table->foreignId('cs_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Data complaint
            $table->string('order_id')->nullable()->index();
            $table->string('username')->nullable()->index();
            $table->string('resi')->nullable();

            /*
            |--------------------------------------------------------------------------
            | SKU SNAPSHOT
            |--------------------------------------------------------------------------
            | Tetap disimpan walaupun ada relasi ke sku_codes,
            | supaya histori tidak berubah jika master SKU berubah
            */
            $table->string('sku')->nullable()->index();
            $table->string('product_name')->nullable();
            $table->decimal('value_of_product', 15, 2)->nullable();

            // Autofill dari SKU jika memang dipakai
            $table->integer('available_qty')->nullable();
            $table->string('status_qty')->nullable();

            $table->longText('update_long_text')->nullable();
            $table->string('part_of_bad')->nullable();

            $table->boolean('step_cs_selesai')->default(false);

            $table->enum('level_customer', [
                'Hard Complaint',
                'Normal Complaint',
            ])->nullable();

            $table->date('tanggal_step_cs_selesai')->nullable();
            $table->date('tanggal_update')->nullable();

            $table->string('video_unboxing_path')->nullable();
            $table->text('proof')->nullable();

            /*
            |--------------------------------------------------------------------------
            | B. AUTOMATION RESULT
            |--------------------------------------------------------------------------
            */

            $table->string('cycle')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('priority')->nullable()->index();
            $table->string('category_customer')->nullable();
            $table->string('riwayat_oos')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['cs_user_id', 'status']);
            $table->index(['brand_id', 'priority']);
            $table->index(['platform_id', 'status']);
            $table->index(['tanggal_complaint', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
