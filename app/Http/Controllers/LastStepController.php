<?php

namespace App\Http\Controllers;

use App\Models\LastStep;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LastStepController extends Controller
{
    private const STATUS_OPTIONS = ['Pending', 'Solved', 'Whitelist'];
    private const PRIORITY_OPTIONS = ['Mines', 'Cool', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

    public function index(Request $request): Response
    {
        $baseQuery = LastStep::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($lastStepQuery) use ($search) {
                    $lastStepQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('status_result', 'like', "%{$search}%")
                        ->orWhere('priority_level', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status_filter') && $request->input('status_filter') !== 'All', function ($query) use ($request) {
                $query->where('status_result', $request->input('status_filter'));
            })
            ->when($request->filled('active_state') && $request->input('active_state') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('active_state') === 'Active');
            })
            ->orderBy('name');

        $lastSteps = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (LastStep $lastStep) => $this->transformLastStep($lastStep));

        return Inertia::render('LastSteps/Index', [
            'lastSteps' => $lastSteps,
            'statusOptions' => self::STATUS_OPTIONS,
            'priorityOptions' => self::PRIORITY_OPTIONS,
            'filters' => [
                'search' => $request->input('search'),
                'status_filter' => $request->input('status_filter', 'All'),
                'active_state' => $request->input('active_state', 'All'),
            ],
            'metrics' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status_result', 'Pending')->count(),
                'solved' => (clone $baseQuery)->where('status_result', 'Solved')->count(),
                'whitelist' => (clone $baseQuery)->where('status_result', 'Whitelist')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:last_steps,name'],
            'status_result' => ['required', 'string', Rule::in(self::STATUS_OPTIONS)],
            'priority_level' => ['nullable', 'string', Rule::in(self::PRIORITY_OPTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);

        LastStep::create($data);

        return redirect()->route('last-steps.index')->with('success', 'Last step berhasil dibuat.');
    }

    public function update(Request $request, LastStep $lastStep): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('last_steps', 'name')->ignore($lastStep->id)],
            'status_result' => ['required', 'string', Rule::in(self::STATUS_OPTIONS)],
            'priority_level' => ['nullable', 'string', Rule::in(self::PRIORITY_OPTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);

        $lastStep->update($data);

        return redirect()->route('last-steps.index')->with('success', 'Last step berhasil diperbarui.');
    }

    public function destroy(LastStep $lastStep): RedirectResponse
    {
        $lastStep->delete();

        return redirect()->route('last-steps.index')->with('success', 'Last step berhasil dihapus.');
    }

    private function transformLastStep(LastStep $lastStep): array
    {
        return [
            'id' => $lastStep->id,
            'name' => $lastStep->name,
            'status_result' => $lastStep->status_result,
            'priority_level' => $lastStep->priority_level,
            'is_active' => (bool) $lastStep->is_active,
            'created_at' => optional($lastStep->created_at)?->toDateTimeString(),
        ];
    }
}
