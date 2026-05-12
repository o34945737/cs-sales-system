<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->unsignedBigInteger('complaint_id')->nullable()->after('id');
            $table->index('complaint_id');
        });

        // Back-fill: match existing oos records to complaints by order_id.
        DB::table('oos')
            ->select(['id', 'order_id'])
            ->whereNotNull('order_id')
            ->where('order_id', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    $complaintId = DB::table('complaints')
                        ->whereRaw('TRIM(order_id) = ?', [trim((string) $row->order_id)])
                        ->whereNull('deleted_at')
                        ->value('id');

                    if ($complaintId) {
                        DB::table('oos')
                            ->where('id', $row->id)
                            ->update(['complaint_id' => $complaintId]);
                    }
                }
            });

        Schema::table('oos', function (Blueprint $table) {
            $table->foreign('complaint_id')->references('id')->on('complaints')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oos', function (Blueprint $table) {
            $table->dropForeign(['complaint_id']);
            $table->dropIndex(['complaint_id']);
            $table->dropColumn('complaint_id');
        });
    }
};
