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

            // A. TICKET SOURCE & IDENTIFICATION
            $table->string('source')->nullable()->index();
            $table->date('tanggal_complaint')->nullable()->index();
            $table->date('tanggal_order')->nullable();
            $table->string('jam_customer_complaint')->nullable(); // String for flexible format (H:i or H:i:s)
            
            // Relasi ke master data (FK)
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('brand')->nullable()->index(); // String snapshot for dashboard/reports
            $table->foreignId('platform_id')->nullable()->constrained('platforms')->nullOnDelete();
            $table->string('platform')->nullable()->index(); // String snapshot for dashboard/reports
            $table->foreignId('sku_code_id')->nullable()->constrained('sku_codes')->nullOnDelete();
            $table->foreignId('sub_case_id')->nullable()->constrained('sub_cases')->nullOnDelete();
            $table->foreignId('last_step_id')->nullable()->constrained('last_steps')->nullOnDelete();
            $table->foreignId('cs_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('complaint_source_id')->nullable()->constrained('complaint_sources')->nullOnDelete();
            $table->foreignId('complaint_power_id')->nullable()->constrained('complaint_powers')->nullOnDelete();
            $table->foreignId('part_of_bad_id')->nullable()->constrained('part_of_bads')->nullOnDelete();
            
            // B. ORDER & CUSTOMER DATA
            $table->string('order_id')->nullable()->index();
            $table->string('username')->nullable()->index();
            $table->string('resi')->nullable()->index();
            
            // C. PRODUCT & CASE DETAILS (SNAPSHOTS)
            $table->string('sku')->nullable()->index();
            $table->string('product_name')->nullable()->index();
            $table->decimal('value_of_product', 15, 2)->nullable();
            $table->string('sub_case')->nullable();
            $table->string('cause_by')->nullable();
            $table->text('proof')->nullable();
            
            // D. HANDLING & INVESTIGATION
            $table->text('summary_case')->nullable();
            $table->longText('update_long_text')->nullable();
            $table->string('part_of_bad')->nullable();
            $table->string('cs_name')->nullable()->index();
            $table->string('last_step')->nullable();
            $table->string('step_cs_selesai')->nullable()->default('NO');
            $table->date('tanggal_step_cs_selesai')->nullable()->index();
            $table->date('tanggal_update')->nullable()->index();
            
            // E. SPECIAL CONDITIONS
            $table->string('reason_whitelist')->nullable();
            $table->string('reason_late_respons')->nullable();
            $table->string('video_unboxing')->nullable();
            $table->string('proof_attachment')->nullable();
            $table->string('level_customer')->nullable(); // Alias for complaint_power string
            $table->string('complaint_power')->nullable();
            
            // F. AUTOMATION & CALCULATED FIELDS
            $table->string('cycle')->nullable();
            $table->string('status')->nullable()->default('Pending')->index();
            $table->string('priority')->nullable()->index();
            $table->string('category_customer')->nullable();
            $table->string('oos')->nullable();
            $table->string('riwayat_oos')->nullable();
            $table->string('report_category')->nullable();
            $table->string('auto_sync_sla')->nullable();
            $table->integer('sla')->default(0);

            // G. SYSTEM FIELDS
            $table->timestamps();
            $table->softDeletes();

            // High performance composite indexes for operational dashboard
            $table->index(['brand_id', 'status', 'priority'], 'idx_complaints_brand_status_priority');
            $table->index(['platform_id', 'status', 'priority'], 'idx_complaints_platform_status_priority');
            $table->index(['cs_name', 'status'], 'idx_complaints_cs_status');
            $table->index(['tanggal_complaint', 'status', 'priority'], 'idx_complaints_date_status_priority');
            $table->index(['created_at', 'status'], 'idx_complaints_created_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
