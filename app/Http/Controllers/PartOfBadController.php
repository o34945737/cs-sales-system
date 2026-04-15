<?php

namespace App\Http\Controllers;

use App\Models\PartOfBad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartOfBadController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = PartOfBad::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $partOfBads = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(PartOfBad $partOfBad) => $this->transformPartOfBad($partOfBad));

        return Inertia::render('PartOfBads/Index', [
            'partOfBads' => $partOfBads,
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
            'name' => ['required', 'string', 'max:255', 'unique:part_of_bads,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        PartOfBad::create($data);

        return redirect()->route('part-of-bads.index')->with('success', 'Part of bad berhasil dibuat.');
    }

    public function update(Request $request, PartOfBad $partOfBad): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('part_of_bads', 'name')->ignore($partOfBad->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $partOfBad->update($data);

        return redirect()->route('part-of-bads.index')->with('success', 'Part of bad berhasil diperbarui.');
    }

    public function destroy(PartOfBad $partOfBad): RedirectResponse
    {
        $partOfBad->delete();

        return redirect()->route('part-of-bads.index')->with('success', 'Part of bad berhasil dihapus.');
    }

    private function transformPartOfBad(PartOfBad $partOfBad): array
    {
        return [
            'id' => $partOfBad->id,
            'name' => $partOfBad->name,
            'is_active' => (bool) $partOfBad->is_active,
            'created_at' => optional($partOfBad->created_at)?->toDateTimeString(),
        ];
    }
}
