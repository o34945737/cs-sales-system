<?php

namespace App\Http\Controllers;

use App\Models\ComplaintStepStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintStepStatusController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = ComplaintStepStatus::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $complaintStepStatuses = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(ComplaintStepStatus $complaintStepStatus) => $this->transformComplaintStepStatus($complaintStepStatus));

        return Inertia::render('ComplaintStepStatuses/Index', [
            'complaintStepStatuses' => $complaintStepStatuses,
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
            'name' => ['required', 'string', 'max:255', 'unique:complaint_step_statuses,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        ComplaintStepStatus::create($data);

        return redirect()->route('complaint-step-statuses.index')->with('success', 'Complaint step status berhasil dibuat.');
    }

    public function update(Request $request, ComplaintStepStatus $complaintStepStatus): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('complaint_step_statuses', 'name')->ignore($complaintStepStatus->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $complaintStepStatus->update($data);

        return redirect()->route('complaint-step-statuses.index')->with('success', 'Complaint step status berhasil diperbarui.');
    }

    public function destroy(ComplaintStepStatus $complaintStepStatus): RedirectResponse
    {
        $complaintStepStatus->delete();

        return redirect()->route('complaint-step-statuses.index')->with('success', 'Complaint step status berhasil dihapus.');
    }

    private function transformComplaintStepStatus(ComplaintStepStatus $complaintStepStatus): array
    {
        return [
            'id' => $complaintStepStatus->id,
            'name' => $complaintStepStatus->name,
            'is_active' => (bool) $complaintStepStatus->is_active,
            'created_at' => optional($complaintStepStatus->created_at)?->toDateTimeString(),
        ];
    }
}
