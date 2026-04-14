<?php

namespace App\Http\Controllers;

use App\Models\OrderTrackingDataSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingDataSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = OrderTrackingDataSource::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $orderTrackingDataSources = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (OrderTrackingDataSource $orderTrackingDataSource) => $this->transformOrderTrackingDataSource($orderTrackingDataSource));

        return Inertia::render('OrderTrackingDataSources/Index', [
            'orderTrackingDataSources' => $orderTrackingDataSources,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status', 'All'),
            ],
            'metrics' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:order_tracking_data_sources,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        OrderTrackingDataSource::create($data);

        return redirect()->route('order-tracking-data-sources.index')->with('success', 'Data source order tracking berhasil dibuat.');
    }

    public function update(Request $request, OrderTrackingDataSource $orderTrackingDataSource): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('order_tracking_data_sources', 'name')->ignore($orderTrackingDataSource->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $orderTrackingDataSource->update($data);

        return redirect()->route('order-tracking-data-sources.index')->with('success', 'Data source order tracking berhasil diperbarui.');
    }

    public function destroy(OrderTrackingDataSource $orderTrackingDataSource): RedirectResponse
    {
        $orderTrackingDataSource->delete();

        return redirect()->route('order-tracking-data-sources.index')->with('success', 'Data source order tracking berhasil dihapus.');
    }

    private function transformOrderTrackingDataSource(OrderTrackingDataSource $orderTrackingDataSource): array
    {
        return [
            'id' => $orderTrackingDataSource->id,
            'name' => $orderTrackingDataSource->name,
            'is_active' => (bool) $orderTrackingDataSource->is_active,
            'created_at' => optional($orderTrackingDataSource->created_at)?->toDateTimeString(),
        ];
    }
}
