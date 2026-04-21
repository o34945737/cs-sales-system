<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderTrackingErpStatusRequest;
use App\Http\Requests\UpdateOrderTrackingErpStatusRequest;
use App\Models\OrderTrackingErpStatus;
use Illuminate\Http\Request;
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
}
