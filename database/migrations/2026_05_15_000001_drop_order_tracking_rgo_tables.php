<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_tracking_rgo_import_batches');
        Schema::dropIfExists('order_tracking_rgo_entries');
    }

    public function down(): void
    {
        Schema::create('order_tracking_rgo_entries', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('order_tracking_rgo_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index();
            $table->string('order_id');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
};
