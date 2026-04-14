<?php

namespace App\Http\Controllers;

use App\Models\ReasonWhitelist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReasonWhitelistController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = ReasonWhitelist::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $reasonWhitelists = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ReasonWhitelist $reasonWhitelist) => $this->transformReasonWhitelist($reasonWhitelist));

        return Inertia::render('ReasonWhitelists/Index', [
            'reasonWhitelists' => $reasonWhitelists,
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
            'name' => ['required', 'string', 'max:255', 'unique:reason_whitelists,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        ReasonWhitelist::create($data);

        return redirect()->route('reason-whitelists.index')->with('success', 'Reason whitelist berhasil dibuat.');
    }

    public function update(Request $request, ReasonWhitelist $reasonWhitelist): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('reason_whitelists', 'name')->ignore($reasonWhitelist->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $reasonWhitelist->update($data);

        return redirect()->route('reason-whitelists.index')->with('success', 'Reason whitelist berhasil diperbarui.');
    }

    public function destroy(ReasonWhitelist $reasonWhitelist): RedirectResponse
    {
        $reasonWhitelist->delete();

        return redirect()->route('reason-whitelists.index')->with('success', 'Reason whitelist berhasil dihapus.');
    }

    private function transformReasonWhitelist(ReasonWhitelist $reasonWhitelist): array
    {
        return [
            'id' => $reasonWhitelist->id,
            'name' => $reasonWhitelist->name,
            'is_active' => (bool) $reasonWhitelist->is_active,
            'created_at' => optional($reasonWhitelist->created_at)?->toDateTimeString(),
        ];
    }
}
