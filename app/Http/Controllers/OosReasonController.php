<?php

namespace App\Http\Controllers;

use App\Models\OosReason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OosReasonController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = OosReason::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $oosReasons = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (OosReason $oosReason) => $this->transformOosReason($oosReason));

        return Inertia::render('OosReasons/Index', [
            'oosReasons' => $oosReasons,
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
            'name' => ['required', 'string', 'max:255', 'unique:oos_reasons,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        OosReason::create($data);

        return redirect()->route('oos-reasons.index')->with('success', 'OOS reason berhasil dibuat.');
    }

    public function update(Request $request, OosReason $oosReason): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('oos_reasons', 'name')->ignore($oosReason->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $oosReason->update($data);

        return redirect()->route('oos-reasons.index')->with('success', 'OOS reason berhasil diperbarui.');
    }

    public function destroy(OosReason $oosReason): RedirectResponse
    {
        $oosReason->delete();

        return redirect()->route('oos-reasons.index')->with('success', 'OOS reason berhasil dihapus.');
    }

    private function transformOosReason(OosReason $oosReason): array
    {
        return [
            'id' => $oosReason->id,
            'name' => $oosReason->name,
            'is_active' => (bool) $oosReason->is_active,
            'created_at' => optional($oosReason->created_at)?->toDateTimeString(),
        ];
    }
}
