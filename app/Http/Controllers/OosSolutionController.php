<?php

namespace App\Http\Controllers;

use App\Models\OosSolution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OosSolutionController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = OosSolution::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $oosSolutions = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (OosSolution $oosSolution) => $this->transformOosSolution($oosSolution));

        return Inertia::render('OosSolutions/Index', [
            'oosSolutions' => $oosSolutions,
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
            'name' => ['required', 'string', 'max:255', 'unique:oos_solutions,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        OosSolution::create($data);

        return redirect()->route('oos-solutions.index')->with('success', 'OOS solution berhasil dibuat.');
    }

    public function update(Request $request, OosSolution $oosSolution): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('oos_solutions', 'name')->ignore($oosSolution->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $oosSolution->update($data);

        return redirect()->route('oos-solutions.index')->with('success', 'OOS solution berhasil diperbarui.');
    }

    public function destroy(OosSolution $oosSolution): RedirectResponse
    {
        $oosSolution->delete();

        return redirect()->route('oos-solutions.index')->with('success', 'OOS solution berhasil dihapus.');
    }

    private function transformOosSolution(OosSolution $oosSolution): array
    {
        return [
            'id' => $oosSolution->id,
            'name' => $oosSolution->name,
            'is_active' => (bool) $oosSolution->is_active,
            'created_at' => optional($oosSolution->created_at)?->toDateTimeString(),
        ];
    }
}
