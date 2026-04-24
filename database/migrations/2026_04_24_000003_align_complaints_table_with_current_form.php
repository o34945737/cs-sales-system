<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('complaints')) {
            return;
        }

        if ($this->usingSqlite()) {
            $this->dropIndexIfExists('complaints', 'idx_complaints_brand_status_priority');
            $this->dropIndexIfExists('complaints', 'idx_complaints_platform_status_priority');
        }

        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'brand_id')) {
                $table->dropConstrainedForeignId('brand_id');
            }

            if (Schema::hasColumn('complaints', 'platform_id')) {
                $table->dropConstrainedForeignId('platform_id');
            }

            if (Schema::hasColumn('complaints', 'sku_code_id')) {
                $table->dropConstrainedForeignId('sku_code_id');
            }

            if (Schema::hasColumn('complaints', 'sub_case_id')) {
                $table->dropConstrainedForeignId('sub_case_id');
            }

            if (Schema::hasColumn('complaints', 'last_step_id')) {
                $table->dropConstrainedForeignId('last_step_id');
            }

            if (Schema::hasColumn('complaints', 'cs_user_id')) {
                $table->dropConstrainedForeignId('cs_user_id');
            }

            if (Schema::hasColumn('complaints', 'complaint_source_id')) {
                $table->dropConstrainedForeignId('complaint_source_id');
            }

            if (Schema::hasColumn('complaints', 'complaint_power_id')) {
                $table->dropConstrainedForeignId('complaint_power_id');
            }
        });

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('complaints', 'external_internal') ? 'external_internal' : null,
            Schema::hasColumn('complaints', 'month') ? 'month' : null,
            Schema::hasColumn('complaints', 'ai_template') ? 'ai_template' : null,
            Schema::hasColumn('complaints', 'kae') ? 'kae' : null,
            Schema::hasColumn('complaints', 'reason_late_handling') ? 'reason_late_handling' : null,
            Schema::hasColumn('complaints', 'value_of_product') ? 'value_of_product' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('complaints', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        $this->ensureIndex('complaints', 'idx_complaints_brand_status_priority', ['brand', 'status', 'priority']);
        $this->ensureIndex('complaints', 'idx_complaints_platform_status_priority', ['platform', 'status', 'priority']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('complaints')) {
            return;
        }

        $this->dropIndexIfExists('complaints', 'idx_complaints_brand_status_priority');
        $this->dropIndexIfExists('complaints', 'idx_complaints_platform_status_priority');

        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->after('jam_customer_complaint')->constrained('brands')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'platform_id')) {
                $table->foreignId('platform_id')->nullable()->after('brand')->constrained('platforms')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'sku_code_id')) {
                $table->foreignId('sku_code_id')->nullable()->after('platform')->constrained('sku_codes')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'sub_case_id')) {
                $table->foreignId('sub_case_id')->nullable()->after('sku_code_id')->constrained('sub_cases')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'last_step_id')) {
                $table->foreignId('last_step_id')->nullable()->after('sub_case_id')->constrained('last_steps')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'cs_user_id')) {
                $table->foreignId('cs_user_id')->nullable()->after('last_step_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'complaint_source_id')) {
                $table->foreignId('complaint_source_id')->nullable()->after('cs_user_id')->constrained('complaint_sources')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'complaint_power_id')) {
                $table->foreignId('complaint_power_id')->nullable()->after('complaint_source_id')->constrained('complaint_powers')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'external_internal')) {
                $table->string('external_internal')->nullable()->after('complaint_power');
            }

            if (! Schema::hasColumn('complaints', 'month')) {
                $table->string('month')->nullable()->after('external_internal');
            }

            if (! Schema::hasColumn('complaints', 'ai_template')) {
                $table->string('ai_template')->nullable()->after('month');
            }

            if (! Schema::hasColumn('complaints', 'kae')) {
                $table->string('kae')->nullable()->after('ai_template');
            }

            if (! Schema::hasColumn('complaints', 'reason_late_handling')) {
                $table->string('reason_late_handling')->nullable()->after('kae');
            }

            if (! Schema::hasColumn('complaints', 'value_of_product')) {
                $table->decimal('value_of_product', 15, 2)->nullable()->after('product_name');
            }
        });

        $this->ensureIndex('complaints', 'idx_complaints_brand_status_priority', ['brand_id', 'status', 'priority']);
        $this->ensureIndex('complaints', 'idx_complaints_platform_status_priority', ['platform_id', 'status', 'priority']);
    }

    private function ensureIndex(string $tableName, string $indexName, array $columns): void
    {
        $currentColumns = $this->getIndexColumns($tableName, $indexName);

        if ($currentColumns === $columns) {
            return;
        }

        if ($currentColumns !== []) {
            $this->dropIndexIfExists($tableName, $indexName);
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if ($this->getIndexColumns($tableName, $indexName) === []) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function getIndexColumns(string $tableName, string $indexName): array
    {
        if ($this->usingSqlite()) {
            $indexExists = collect(DB::select("PRAGMA index_list('{$tableName}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $indexName);

            if (! $indexExists) {
                return [];
            }

            return collect(DB::select("PRAGMA index_info('{$indexName}')"))
                ->sortBy('seqno')
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        }

        return DB::table('information_schema.statistics')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->orderBy('SEQ_IN_INDEX')
            ->pluck('COLUMN_NAME')
            ->filter()
            ->values()
            ->all();
    }

    private function usingSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }
};
