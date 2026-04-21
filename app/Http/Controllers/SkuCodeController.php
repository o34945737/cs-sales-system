<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\SkuCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SkuCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $baseQuery = SkuCode::query();
        $brandOptions = Brand::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $filteredQuery = (clone $baseQuery)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($skuQuery) use ($search) {
                    $skuQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('sku');

        $skuCodes = (clone $filteredQuery)
            ->paginate(10)
            ->withQueryString()
            ->through(fn(SkuCode $skuCode) => $this->transformSkuCode($skuCode));

        return Inertia::render('SkuCodes/Index', [
            'skuCodes' => $skuCodes,
            'brandOptions' => $brandOptions,
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
        $data = $request->validate($this->rules());

        SkuCode::create($data);

        return redirect()->route('sku-codes.index')->with('success', 'SKU Code berhasil dibuat.');
    }

    public function update(Request $request, SkuCode $skuCode): RedirectResponse
    {
        $data = $request->validate($this->rules($skuCode));

        $skuCode->update($data);

        return redirect()->route('sku-codes.index')->with('success', 'SKU Code berhasil diperbarui.');
    }

    public function destroy(SkuCode $skuCode): RedirectResponse
    {
        $skuCode->delete();

        return redirect()->route('sku-codes.index')->with('success', 'SKU Code berhasil dihapus.');
    }

    private function rules(?SkuCode $skuCode = null): array
    {
        $brandOptions = Brand::query()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return [
            'sku' => ['required', 'string', 'max:255', Rule::unique('sku_codes', 'sku')->ignore($skuCode?->id)],
            'product_name' => ['required', 'string', 'max:255'],
            'brand' => empty($brandOptions) ? ['nullable', 'string', 'max:255'] : ['nullable', 'string', Rule::in($brandOptions)],
            'available_qty' => ['nullable', 'integer', 'min:0'],
            'status_qty' => ['nullable', 'string', 'max:100'],
            'default_value_of_product' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function transformSkuCode(SkuCode $skuCode): array
    {
        return [
            'id' => $skuCode->id,
            'sku' => $skuCode->sku,
            'product_name' => $skuCode->product_name,
            'brand' => $skuCode->brand,
            'available_qty' => (int) ($skuCode->available_qty ?? 0),
            'status_qty' => $skuCode->status_qty,
            'default_value_of_product' => $skuCode->default_value_of_product !== null
                ? (float) $skuCode->default_value_of_product
                : null,
            'is_active' => (bool) $skuCode->is_active,
            'created_at' => optional($skuCode->created_at)?->toDateTimeString(),
        ];
    }
}
