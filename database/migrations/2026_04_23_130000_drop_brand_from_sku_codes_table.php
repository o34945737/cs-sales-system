<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('sku_codes', 'brand') ? 'brand' : null,
            Schema::hasColumn('sku_codes', 'available_qty') ? 'available_qty' : null,
            Schema::hasColumn('sku_codes', 'status_qty') ? 'status_qty' : null,
            Schema::hasColumn('sku_codes', 'default_value_of_product') ? 'default_value_of_product' : null,
            Schema::hasColumn('sku_codes', 'is_active') ? 'is_active' : null,
        ]));

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('sku_codes', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    public function down(): void
    {
        Schema::table('sku_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('sku_codes', 'brand')) {
                $table->string('brand')->nullable()->after('product_name');
            }

            if (!Schema::hasColumn('sku_codes', 'available_qty')) {
                $table->integer('available_qty')->default(0)->after('brand');
            }

            if (!Schema::hasColumn('sku_codes', 'status_qty')) {
                $table->string('status_qty')->nullable()->after('available_qty');
            }

            if (!Schema::hasColumn('sku_codes', 'default_value_of_product')) {
                $table->decimal('default_value_of_product', 15, 2)->nullable()->after('status_qty');
            }

            if (!Schema::hasColumn('sku_codes', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('default_value_of_product');
            }
        });
    }
};
