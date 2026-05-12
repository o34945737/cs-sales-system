<?php

use App\Imports\OrderTrackingErpImport;
use App\Models\OrderTracking;
use App\Models\OrderTrackingErpStatus;
use App\Models\OrderTrackingErpStatusHistory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

test('erp status import separates updated and pending order ids', function () {
    $user = User::factory()->create();

    OrderTrackingErpStatus::updateOrCreate(['name' => 'Open'], ['sort_order' => 1, 'is_active' => true]);
    OrderTrackingErpStatus::updateOrCreate(['name' => 'Proses'], ['sort_order' => 2, 'is_active' => true]);
    OrderTrackingErpStatus::updateOrCreate(['name' => 'Shipped'], ['sort_order' => 3, 'is_active' => true]);

    OrderTracking::create([
        'data_source' => 'WH',
        'tanggal_input' => '2026-05-12',
        'tanggal_order' => '2026-05-10',
        'brand' => 'ANTA',
        'platform' => 'Shopee',
        'order_id' => 'OT-EXIST',
        'cause_by' => 'WH',
        'erp_status' => 'Open',
        'cs_name' => 'CS Alpha',
        'category' => 'Late Delivery',
        'last_step' => 'Waiting Claim',
        'tanggal_update' => '2026-05-12',
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'erp-status.csv',
        "no,order_id,erp_status\n1,OT-EXIST,Shipped\n2,OT-MISSING,Shipped\n"
    );

    $importer = new OrderTrackingErpImport('TEST-BATCH', $user->id);

    Excel::import($importer, $file);

    expect($importer->results['updated'])->toBe(1)
        ->and($importer->results['pending'])->toBe(1)
        ->and($importer->importedOrderIds)->toBe(['OT-EXIST']);

    $this->assertDatabaseHas('order_trackings', [
        'order_id' => 'OT-EXIST',
        'erp_status' => 'Proses',
    ]);

    $this->assertDatabaseHas('order_tracking_erp_status_histories', [
        'order_id' => 'OT-EXIST',
        'erp_status' => 'Proses',
        'batch_id' => 'TEST-BATCH',
        'uploaded_by' => $user->id,
    ]);

    $this->assertDatabaseHas('order_tracking_erp_status_histories', [
        'order_id' => 'OT-MISSING',
        'erp_status' => 'Open',
        'batch_id' => 'TEST-BATCH',
        'uploaded_by' => $user->id,
    ]);

    expect(OrderTracking::where('order_id', 'OT-MISSING')->exists())->toBeFalse();
    expect(OrderTrackingErpStatusHistory::where('order_id', 'OT-MISSING')->count())->toBe(1);
});
