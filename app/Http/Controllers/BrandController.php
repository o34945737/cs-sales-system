<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = Brand::query();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $brands = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Brand $brand) => $this->transformBrand($brand));

        return Inertia::render('Brands/Index', [
            'brands' => $brands,
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
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'is_active' => ['required', 'boolean'],
        ]);

        Brand::create($data);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dibuat.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brands', 'name')->ignore($brand->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $brand->update($data);

        return redirect()->route('brands.index')->with('success', 'Brand berhasil diperbarui.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return redirect()->route('brands.index')->with('success', 'Brand berhasil dihapus.');
    }

    private function transformBrand(Brand $brand): array
    {
        return [
            'id' => $brand->id,
            'name' => $brand->name,
            'is_active' => (bool) $brand->is_active,
            'created_at' => optional($brand->created_at)?->toDateTimeString(),
        ];
    }
}
