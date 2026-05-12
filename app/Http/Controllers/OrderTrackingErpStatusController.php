<?php

namespace App\Http\Controllers;

use App\Exports\OrderTrackingErpTemplateExport;
use App\Http\Requests\StoreOrderTrackingErpStatusRequest;
use App\Http\Requests\UpdateOrderTrackingErpStatusRequest;
use App\Imports\OrderTrackingErpImport;
use App\Models\OrderTrackingErpStatus;
use App\Services\OrderTrackingAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
        return Excel::download(new OrderTrackingErpTemplateExport(), 'erp-import-template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $batchId  = 'ERP-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $importer = new OrderTrackingErpImport($batchId, auth()->id());

        Excel::import($importer, $request->file('file'));

        // Recompute status, automation_track, dll. untuk semua order_id yang ter-update
        if (!empty($importer->importedOrderIds)) {
            app(OrderTrackingAutomationService::class)
                ->recomputeByOrderIds(array_unique($importer->importedOrderIds));
        }

        return back()->with('import_result', $importer->results);
    }
}
