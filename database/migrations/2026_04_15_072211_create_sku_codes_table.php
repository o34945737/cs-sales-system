<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sku_codes')) {
            Schema::create('sku_codes', function (Blueprint $table) {
                $table->id();
                $table->string('sku')->unique();
                $table->string('product_name');
                $table->string('brand')->nullable();
                $table->decimal('default_value_of_product', 15, 2)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Compatibility no-op: the canonical sku_codes table may come from
        // an earlier migration, so we avoid dropping it here.
    }
};
