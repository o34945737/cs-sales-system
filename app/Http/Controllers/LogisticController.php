<?php

namespace App\Http\Controllers;

use App\Models\Logistic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LogisticController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = Logistic::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $logistics = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Logistic $logistic) => $this->transformLogistic($logistic));

        return Inertia::render('Logistics/Index', [
            'logistics' => $logistics,
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
            'name' => ['required', 'string', 'max:255', 'unique:logistics,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        Logistic::create($data);

        return redirect()->route('logistics.index')->with('success', 'Logistics berhasil dibuat.');
    }

    public function update(Request $request, Logistic $logistic): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('logistics', 'name')->ignore($logistic->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $logistic->update($data);

        return redirect()->route('logistics.index')->with('success', 'Logistics berhasil diperbarui.');
    }

    public function destroy(Logistic $logistic): RedirectResponse
    {
        $logistic->delete();

        return redirect()->route('logistics.index')->with('success', 'Logistics berhasil dihapus.');
    }

    private function transformLogistic(Logistic $logistic): array
    {
        return [
            'id' => $logistic->id,
            'name' => $logistic->name,
            'is_active' => (bool) $logistic->is_active,
            'created_at' => optional($logistic->created_at)?->toDateTimeString(),
        ];
    }
}
