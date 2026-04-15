<?php

namespace App\Http\Controllers;

use App\Models\ComplaintPower;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintPowerController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = ComplaintPower::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $complaintPowers = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(ComplaintPower $complaintPower) => $this->transformComplaintPower($complaintPower));

        return Inertia::render('ComplaintPowers/Index', [
            'complaintPowers' => $complaintPowers,
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
            'name' => ['required', 'string', 'max:255', 'unique:complaint_powers,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        ComplaintPower::create($data);

        return redirect()->route('complaint-powers.index')->with('success', 'Complaint power berhasil dibuat.');
    }

    public function update(Request $request, ComplaintPower $complaintPower): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('complaint_powers', 'name')->ignore($complaintPower->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $complaintPower->update($data);

        return redirect()->route('complaint-powers.index')->with('success', 'Complaint power berhasil diperbarui.');
    }

    public function destroy(ComplaintPower $complaintPower): RedirectResponse
    {
        $complaintPower->delete();

        return redirect()->route('complaint-powers.index')->with('success', 'Complaint power berhasil dihapus.');
    }

    private function transformComplaintPower(ComplaintPower $complaintPower): array
    {
        return [
            'id' => $complaintPower->id,
            'name' => $complaintPower->name,
            'is_active' => (bool) $complaintPower->is_active,
            'created_at' => optional($complaintPower->created_at)?->toDateTimeString(),
        ];
    }
}
