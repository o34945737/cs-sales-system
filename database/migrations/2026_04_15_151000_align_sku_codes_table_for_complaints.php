<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sku_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('sku_codes', 'sku')) {
                $table->string('sku')->nullable()->after('id');
            }

            if (!Schema::hasColumn('sku_codes', 'product_name')) {
                $table->string('product_name')->nullable()->after('sku');
            }

            if (!Schema::hasColumn('sku_codes', 'brand')) {
                $table->string('brand')->nullable()->after('product_name');
            }

            if (!Schema::hasColumn('sku_codes', 'default_value_of_product')) {
                $table->decimal('default_value_of_product', 15, 2)->nullable()->after('brand');
            }

            if (!Schema::hasColumn('sku_codes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('default_value_of_product');
            }
        });

        if (Schema::hasColumn('sku_codes', 'name_product') && Schema::hasColumn('sku_codes', 'product_name')) {
            DB::table('sku_codes')
                ->where(function ($query) {
                    $query->whereNull('product_name')
                        ->orWhere('product_name', '');
                })
                ->update([
                    'product_name' => DB::raw('name_product'),
                ]);
        }
    }

    public function down(): void
    {
        // No-op. Kolom inti dibiarkan tetap ada agar rollback migration ini
        // tidak merusak struktur dasar `sku_codes` pada instalasi baru.
    }
};
