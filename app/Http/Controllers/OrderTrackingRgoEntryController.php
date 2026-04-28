<?php

namespace App\Http\Controllers;

use App\Models\OrderTracking;
use App\Models\OrderTrackingRgoEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

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
        $csv = "no,order_id,notes,is_active\n";
        $csv .= "1,ORD-12345,,1\n";
        $csv .= "2,ORD-12346,Refund processed,1\n";
        $csv .= "3,ORD-12347,Return approved,1\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="rgo-import-template.csv"');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $results = ['created' => 0, 'updated' => 0, 'failed' => 0, 'errors' => []];
        $importedOrderIds = [];

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        fgetcsv($handle); // skip header row

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if (count($row) < 2) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNum}: format tidak valid (kurang kolom)";
                continue;
            }

            [, $orderId] = $row;
            $notes    = isset($row[2]) ? trim($row[2]) : null;
            $isActive = isset($row[3]) ? (bool)(int)trim($row[3]) : true;
            $orderId  = trim($orderId);

            if (!$orderId) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNum}: order_id kosong";
                continue;
            }

            $existing = OrderTrackingRgoEntry::where('order_id', $orderId)->first();

            if ($existing) {
                $existing->update([
                    'notes'     => $notes ?: $existing->notes,
                    'is_active' => $isActive,
                ]);
                $results['updated']++;
            } else {
                OrderTrackingRgoEntry::create([
                    'order_id' => $orderId,
                    'notes'    => $notes ?: null,
                    'is_active' => $isActive,
                ]);
                $results['created']++;
            }

            $importedOrderIds[] = $orderId;
        }

        fclose($handle);

        // Re-compute automation_track for matching order_trackings
        if (!empty($importedOrderIds)) {
            OrderTracking::whereIn('order_id', $importedOrderIds)
                ->whereNull('automation_track')
                ->update(['automation_track' => 'Sudah diRGO']);
        }

        return back()->with('import_result', $results);
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
