<?php

namespace App\Http\Controllers;

use App\Models\Platform;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = Platform::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $platforms = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Platform $platform) => $this->transformPlatform($platform));

        return Inertia::render('Platforms/Index', [
            'platforms' => $platforms,
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
            'name'     => ['required', 'string', 'max:255', 'unique:platforms,name'],
            'is_active' => ['required', 'boolean'],
            'tts_days' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        Platform::create($data);

        return redirect()->route('platforms.index')->with('success', 'Platform berhasil dibuat.');
    }

    public function update(Request $request, Platform $platform): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255', Rule::unique('platforms', 'name')->ignore($platform->id)],
            'is_active' => ['required', 'boolean'],
            'tts_days' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $platform->update($data);

        return redirect()->route('platforms.index')->with('success', 'Platform berhasil diperbarui.');
    }

    public function destroy(Platform $platform): RedirectResponse
    {
        $platform->delete();

        return redirect()->route('platforms.index')->with('success', 'Platform berhasil dihapus.');
    }

    private function transformPlatform(Platform $platform): array
    {
        return [
            'id'         => $platform->id,
            'name'       => $platform->name,
            'is_active'  => (bool) $platform->is_active,
            'tts_days'   => $platform->tts_days,
            'created_at' => optional($platform->created_at)?->toDateTimeString(),
        ];
    }
}
