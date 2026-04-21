<?php

namespace App\Http\Controllers;

use App\Models\JetTrackEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JetTrackEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = JetTrackEntry::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query
                    ->where('awb', 'like', "%{$search}%")
                    ->orWhere('kondisi_barang', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('awb');

        $jetTrackEntries = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(JetTrackEntry $entry) => $this->transformEntry($entry));

        return Inertia::render('JetTrackEntries/Index', [
            'jetTrackEntries' => $jetTrackEntries,
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
            'awb' => ['required', 'string', 'max:255', 'unique:jet_track_entries,awb'],
            'kondisi_barang' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        JetTrackEntry::create($data);

        return redirect()->route('jet-track-entries.index')->with('success', 'Data Jet Track berhasil dibuat.');
    }

    public function update(Request $request, JetTrackEntry $jetTrackEntry): RedirectResponse
    {
        $data = $request->validate([
            'awb' => ['required', 'string', 'max:255', Rule::unique('jet_track_entries', 'awb')->ignore($jetTrackEntry->id)],
            'kondisi_barang' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $jetTrackEntry->update($data);

        return redirect()->route('jet-track-entries.index')->with('success', 'Data Jet Track berhasil diperbarui.');
    }

    public function destroy(JetTrackEntry $jetTrackEntry): RedirectResponse
    {
        $jetTrackEntry->delete();

        return redirect()->route('jet-track-entries.index')->with('success', 'Data Jet Track berhasil dihapus.');
    }

    private function transformEntry(JetTrackEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'awb' => $entry->awb,
            'kondisi_barang' => $entry->kondisi_barang,
            'notes' => $entry->notes,
            'is_active' => (bool) $entry->is_active,
            'created_at' => optional($entry->created_at)?->toDateTimeString(),
        ];
    }
}
