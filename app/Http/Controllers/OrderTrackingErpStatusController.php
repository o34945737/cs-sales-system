<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderTrackingErpStatusRequest;
use App\Http\Requests\UpdateOrderTrackingErpStatusRequest;
use App\Models\OrderTracking;
use App\Models\OrderTrackingErpStatus;
use App\Models\OrderTrackingErpStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OrderTrackingErpStatusController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $statusFilter = $request->input('status', 'All');

        $baseQuery = OrderTrackingErpStatus::query();

        $filteredQuery = (clone $baseQuery)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($statusFilter !== 'All', function ($query) use ($statusFilter) {
                $query->where('is_active', $statusFilter === 'Active');
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        $erpStatuses = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('OrderTrackingErpStatuses/Index', [
            'erpStatuses' => $erpStatuses,
            'filters' => [
                'search' => $search,
                'status' => $statusFilter,
            ],
            'metrics' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(StoreOrderTrackingErpStatusRequest $request)
    {
        OrderTrackingErpStatus::create($request->validated());

        return redirect()
            ->route('order-tracking-erp-statuses.index')
            ->with('success', 'ERP Status berhasil ditambahkan.');
    }

    public function update(UpdateOrderTrackingErpStatusRequest $request, OrderTrackingErpStatus $order_tracking_erp_status)
    {
        $order_tracking_erp_status->update($request->validated());

        return redirect()
            ->route('order-tracking-erp-statuses.index')
            ->with('success', 'ERP Status berhasil diperbarui.');
    }

    public function destroy(OrderTrackingErpStatus $order_tracking_erp_status)
    {
        $order_tracking_erp_status->delete();

        return redirect()
            ->route('order-tracking-erp-statuses.index')
            ->with('success', 'ERP Status berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        $csv = "no,order_id,erp_status\n";
        $csv .= "1,ORD-12345,Pending\n";
        $csv .= "2,ORD-12346,Process\n";
        $csv .= "3,ORD-12347,Done\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="erp-import-template.csv"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $validStatuses = OrderTrackingErpStatus::where('is_active', true)->pluck('name')->all();
        $batchId = 'ERP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $results = ['updated' => 0, 'pending' => 0, 'failed' => 0, 'errors' => []];

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        fgetcsv($handle); // skip header row

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) < 3) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNum}: format tidak valid (kurang kolom)";
                continue;
            }

            [, $orderId, $erpStatus] = $row;
            $orderId   = trim($orderId);
            $erpStatus = trim($erpStatus);

            if (!$orderId) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNum}: order_id kosong";
                continue;
            }

            if (!empty($validStatuses) && !in_array($erpStatus, $validStatuses, true)) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNum}: erp_status '{$erpStatus}' tidak ada di master";
                continue;
            }

            OrderTrackingErpStatusHistory::create([
                'order_id'    => $orderId,
                'erp_status'  => $erpStatus,
                'batch_id'    => $batchId,
                'uploaded_by' => auth()->id(),
            ]);

            $updated = OrderTracking::where('order_id', $orderId)->update(['erp_status' => $erpStatus]);

            if ($updated > 0) {
                $results['updated']++;
            } else {
                $results['pending']++;
            }
        }

        fclose($handle);

        return back()->with('import_result', $results);
    }
}
