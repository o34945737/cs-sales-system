<?php

namespace App\Http\Controllers;

use App\Models\ComplaintSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintSourceController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = ComplaintSource::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $complaintSources = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(ComplaintSource $complaintSource) => $this->transformComplaintSource($complaintSource));

        return Inertia::render('ComplaintSources/Index', [
            'complaintSources' => $complaintSources,
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
            'name' => ['required', 'string', 'max:255', 'unique:complaint_sources,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        ComplaintSource::create($data);

        return redirect()->route('complaint-sources.index')->with('success', 'Complaint source berhasil dibuat.');
    }

    public function update(Request $request, ComplaintSource $complaintSource): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('complaint_sources', 'name')->ignore($complaintSource->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $complaintSource->update($data);

        return redirect()->route('complaint-sources.index')->with('success', 'Complaint source berhasil diperbarui.');
    }

    public function destroy(ComplaintSource $complaintSource): RedirectResponse
    {
        $complaintSource->delete();

        return redirect()->route('complaint-sources.index')->with('success', 'Complaint source berhasil dihapus.');
    }

    private function transformComplaintSource(ComplaintSource $complaintSource): array
    {
        return [
            'id' => $complaintSource->id,
            'name' => $complaintSource->name,
            'is_active' => (bool) $complaintSource->is_active,
            'created_at' => optional($complaintSource->created_at)?->toDateTimeString(),
        ];
    }
}
