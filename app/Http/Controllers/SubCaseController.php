<?php

namespace App\Http\Controllers;

use App\Models\SubCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubCaseController extends Controller
{
    private const CAUSE_BY_OPTIONS = [
        'CS',
        'KAE',
        'WH',
        'ANTERAJA',
        'CHAT++',
        'CUSTOM LOGISTICS',
        'GOJEK/GRAB',
        'GTL',
        'INDOPAKET',
        'J&T',
        'JNE',
        'KURIR REKOMENDASI',
        'LEX',
        'NINJA',
        'POS',
        'SAP EXPRESS',
        'SICEPAT',
        'SPX',
        'STREAMER',
        'CUSTOMER',
        'BRAND',
        'PROMO',
        'PART',
    ];

    public function index(Request $request): Response
    {
        $baseQuery = SubCase::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($subCaseQuery) use ($search) {
                    $subCaseQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('default_cause_by', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $subCases = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (SubCase $subCase) => $this->transformSubCase($subCase));

        return Inertia::render('SubCases/Index', [
            'subCases' => $subCases,
            'causeByOptions' => self::CAUSE_BY_OPTIONS,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status', 'All'),
            ],
            'metrics' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
                'auto_cause_by' => (clone $baseQuery)->whereNotNull('default_cause_by')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:sub_cases,name'],
            'default_cause_by' => ['nullable', 'string', Rule::in(self::CAUSE_BY_OPTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);

        SubCase::create($data);

        return redirect()->route('sub-cases.index')->with('success', 'Sub case berhasil dibuat.');
    }

    public function update(Request $request, SubCase $subCase): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sub_cases', 'name')->ignore($subCase->id)],
            'default_cause_by' => ['nullable', 'string', Rule::in(self::CAUSE_BY_OPTIONS)],
            'is_active' => ['required', 'boolean'],
        ]);

        $subCase->update($data);

        return redirect()->route('sub-cases.index')->with('success', 'Sub case berhasil diperbarui.');
    }

    public function destroy(SubCase $subCase): RedirectResponse
    {
        $subCase->delete();

        return redirect()->route('sub-cases.index')->with('success', 'Sub case berhasil dihapus.');
    }

    private function transformSubCase(SubCase $subCase): array
    {
        return [
            'id' => $subCase->id,
            'name' => $subCase->name,
            'default_cause_by' => $subCase->default_cause_by,
            'is_active' => (bool) $subCase->is_active,
            'created_at' => optional($subCase->created_at)?->toDateTimeString(),
        ];
    }
}
