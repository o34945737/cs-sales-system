<?php

namespace App\Http\Controllers;

use App\Models\ReasonLateResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReasonLateResponseController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = ReasonLateResponse::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $reasonLateResponses = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(ReasonLateResponse $reasonLateResponse) => $this->transformReasonLateResponse($reasonLateResponse));

        return Inertia::render('ReasonLateResponses/Index', [
            'reasonLateResponses' => $reasonLateResponses,
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
            'name' => ['required', 'string', 'max:255', 'unique:reason_late_responses,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        ReasonLateResponse::create($data);

        return redirect()->route('reason-late-responses.index')->with('success', 'Reason Late Response berhasil dibuat.');
    }

    public function update(Request $request, ReasonLateResponse $reasonLateResponse): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('reason_late_responses', 'name')->ignore($reasonLateResponse->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $reasonLateResponse->update($data);

        return redirect()->route('reason-late-responses.index')->with('success', 'Reason Late Response berhasil diperbarui.');
    }

    public function destroy(ReasonLateResponse $reasonLateResponse): RedirectResponse
    {
        $reasonLateResponse->delete();

        return redirect()->route('reason-late-responses.index')->with('success', 'Reason Late Response berhasil dihapus.');
    }

    private function transformReasonLateResponse(ReasonLateResponse $reasonLateResponse): array
    {
        return [
            'id' => $reasonLateResponse->id,
            'name' => $reasonLateResponse->name,
            'is_active' => (bool) $reasonLateResponse->is_active,
            'created_at' => optional($reasonLateResponse->created_at)?->toDateTimeString(),
        ];
    }
}
