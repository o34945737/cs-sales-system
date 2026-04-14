<?php

namespace App\Http\Controllers;

use App\Models\CauseBy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CauseByController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = CauseBy::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $causeBys = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (CauseBy $causeBy) => $this->transformCauseBy($causeBy));

        return Inertia::render('CauseBys/Index', [
            'causeBys' => $causeBys,
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
            'name' => ['required', 'string', 'max:255', 'unique:cause_bys,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        CauseBy::create($data);

        return redirect()->route('cause-bys.index')->with('success', 'Cause by berhasil dibuat.');
    }

    public function update(Request $request, CauseBy $causeBy): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('cause_bys', 'name')->ignore($causeBy->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $causeBy->update($data);

        return redirect()->route('cause-bys.index')->with('success', 'Cause by berhasil diperbarui.');
    }

    public function destroy(CauseBy $causeBy): RedirectResponse
    {
        $causeBy->delete();

        return redirect()->route('cause-bys.index')->with('success', 'Cause by berhasil dihapus.');
    }

    private function transformCauseBy(CauseBy $causeBy): array
    {
        return [
            'id' => $causeBy->id,
            'name' => $causeBy->name,
            'is_active' => (bool) $causeBy->is_active,
            'created_at' => optional($causeBy->created_at)?->toDateTimeString(),
        ];
    }
}
