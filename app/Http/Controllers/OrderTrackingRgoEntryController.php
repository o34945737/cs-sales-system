<?php

namespace App\Http\Controllers;

use App\Exports\OrderTrackingRgoTemplateExport;
use App\Imports\OrderTrackingRgoImport;
use App\Models\OrderTrackingRgoEntry;
use App\Services\OrderTrackingAutomationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class OrderTrackingRgoEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = OrderTrackingRgoEntry::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query
                    ->where('order_id', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('order_id');

        $orderTrackingRgoEntries = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(OrderTrackingRgoEntry $entry) => $this->transformEntry($entry));

        return Inertia::render('OrderTrackingRgoEntries/Index', [
            'orderTrackingRgoEntries' => $orderTrackingRgoEntries,
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
            'order_id' => ['required', 'string', 'max:255', 'unique:order_tracking_rgo_entries,order_id'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        OrderTrackingRgoEntry::create($data);

        return redirect()->route('order-tracking-rgo-entries.index')->with('success', 'Data RGO order tracking berhasil dibuat.');
    }

    public function update(Request $request, OrderTrackingRgoEntry $orderTrackingRgoEntry): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'string', 'max:255', Rule::unique('order_tracking_rgo_entries', 'order_id')->ignore($orderTrackingRgoEntry->id)],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $orderTrackingRgoEntry->update($data);

        return redirect()->route('order-tracking-rgo-entries.index')->with('success', 'Data RGO order tracking berhasil diperbarui.');
    }

    public function destroy(OrderTrackingRgoEntry $orderTrackingRgoEntry): RedirectResponse
    {
        $orderTrackingRgoEntry->delete();

        return redirect()->route('order-tracking-rgo-entries.index')->with('success', 'Data RGO order tracking berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new OrderTrackingRgoTemplateExport(), 'rgo-import-template.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $batchId  = 'RGO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(4));
        $importer = new OrderTrackingRgoImport($batchId, auth()->id());

        Excel::import($importer, $request->file('file'));

        if (!empty($importer->importedOrderIds)) {
            app(OrderTrackingAutomationService::class)->recomputeByOrderIds($importer->importedOrderIds);
        }

        return back()->with('import_result', $importer->results);
    }

    private function transformEntry(OrderTrackingRgoEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'order_id' => $entry->order_id,
            'notes' => $entry->notes,
            'is_active' => (bool) $entry->is_active,
            'created_at' => optional($entry->created_at)?->toDateTimeString(),
        ];
    }
}
