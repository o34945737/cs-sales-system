<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Platform;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class BadReviewController extends Controller
{
    public function index(Request $request)
    {
        $brandOptions = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $categoryReviewOptions = $this->categoryReviewOptions();
        $causeByOptions = $this->causeByOptions();

        $skuCodeOptions = SkuCode::query()
            ->where('is_active', true)
            ->orderBy('sku')
            ->get(['sku', 'product_name', 'brand', 'available_qty', 'status_qty'])
            ->map(fn(SkuCode $skuCode) => [
                'sku' => $skuCode->sku,
                'product_name' => $skuCode->product_name,
                'brand' => $skuCode->brand,
                'available_qty' => $skuCode->available_qty,
                'status_qty' => $skuCode->status_qty,
            ])
            ->all();

        $csNameOptions = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $baseQuery = BadReview::query();
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('platform', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('cs_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('brand') && $request->brand !== 'All') {
            $baseQuery->where('brand', $request->brand);
        }

        if ($request->filled('platform') && $request->platform !== 'All') {
            $baseQuery->where('platform', $request->platform);
        }

        if ($request->filled('star') && $request->star !== 'All') {
            $baseQuery->where('star', (int)$request->star);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $baseQuery->where('status', $request->status);
        }

        if ($request->filled('cs_name')) {
            $baseQuery->where('cs_name', $request->cs_name);
        }

        $allowedSortFields = [
            'tanggal_review',
            'tanggal_update',
            'order_id',
            'username',
            'brand',
            'platform',
            'star',
            'status',
            'cs_name',
            'created_at',
        ];

        $sortField = $request->get('sort', 'tanggal_review');
        $sortField = in_array($sortField, $allowedSortFields, true) ? $sortField : 'tanggal_review';
        $sortOrder = $request->get('order', 'desc') === 'asc' ? 'asc' : 'desc';
        $baseQuery->orderBy($sortField, $sortOrder);

        $filteredQuery = clone $baseQuery;
        $allBadReviews = $filteredQuery->get();

        $statusSummary = [
            'all' => $allBadReviews->count(),
            'pending' => $allBadReviews->where('status', 'Pending')->count(),
            'solved' => $allBadReviews->where('status', 'Solved')->count(),
        ];

        $starSummary = [
            'all' => $allBadReviews->count(),
            '1' => $allBadReviews->where('star', 1)->count(),
            '2' => $allBadReviews->where('star', 2)->count(),
            '3' => $allBadReviews->where('star', 3)->count(),
        ];

        $csSummary = $allBadReviews
            ->groupBy('cs_name')
            ->map(fn($group, $name) => ['cs_name' => $name ?: 'UNASSIGNED', 'total' => $group->count()])
            ->values()
            ->all();

        $badReviews = $baseQuery->paginate(15)->appends($request->query());

        return Inertia::render('BadReviews/Index', [
            'badReviews' => $badReviews,
            'brandOptions' => $brandOptions,
            'platformOptions' => $platformOptions,
            'categoryReviewOptions' => $categoryReviewOptions,
            'causeByOptions' => $causeByOptions,
            'skuCodeOptions' => $skuCodeOptions,
            'csNameOptions' => $csNameOptions,
            'statusSummary' => $statusSummary,
            'starSummary' => $starSummary,
            'csSummary' => $csSummary,
            'autoCauseByMap' => SubCase::query()
                ->where('is_active', true)
                ->whereNotNull('default_cause_by')
                ->orderBy('name')
                ->get(['name', 'default_cause_by'])
                ->mapWithKeys(fn(SubCase $subCase) => [$subCase->name => $subCase->default_cause_by])
                ->all(),
            'filters' => $request->only(['search', 'brand', 'platform', 'status', 'cs_name', 'star']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->badReviewRules($request));

        try {
            BadReview::create($validated);
            return redirect()->back()->with('success', 'Bad Review berhasil disimpan.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan Bad Review. ' . $e->getMessage());
        }
    }

    public function update(Request $request, BadReview $badReview)
    {
        $validated = $request->validate($this->badReviewRules($request));

        try {
            $badReview->update($validated);
            return redirect()->back()->with('success', 'Bad Review berhasil diupdate.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal mengupdate Bad Review. ' . $e->getMessage());
        }
    }

    public function destroy(BadReview $badReview)
    {
        try {
            $badReview->delete();
            return redirect()->back()->with('success', 'Bad Review berhasil dihapus.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menghapus Bad Review.');
        }
    }

    private function badReviewRules(Request $request): array
    {
        $brandOptions = $this->brandOptions();
        $platformOptions = $this->platformOptions();
        $categoryReviewOptions = $this->categoryReviewOptions();
        $causeByOptions = $this->causeByOptions();
        $csNameOptions = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $skuCatalog = SkuCode::query()
            ->where('is_active', true)
            ->get(['sku', 'brand', 'product_name'])
            ->keyBy('sku');

        $activeSkuOptions = $skuCatalog->keys()->values()->all();

        return [
            'tanggal_review' => ['required', 'date'],
            'month' => ['nullable', 'string'],
            'brand' => [
                'required',
                'string',
                Rule::in($brandOptions),
                function ($attribute, $value, $fail) use ($request, $skuCatalog) {
                    $sku = $request->input('sku');
                    if (!$sku || !$skuCatalog->has($sku)) {
                        return;
                    }

                    $skuBrand = $skuCatalog->get($sku)?->brand;
                    if ($skuBrand && $value !== $skuBrand) {
                        $fail("Brand untuk SKU '{$sku}' harus '{$skuBrand}'.");
                    }
                },
            ],
            'platform' => ['required', 'string', Rule::in($platformOptions)],
            'order_id' => ['required', 'string'],
            'username' => ['required', 'string'],
            'star' => ['required', 'integer', Rule::in([1, 2, 3])],
            'product_name' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request, $skuCatalog) {
                    $sku = $request->input('sku');
                    if (!$sku || !$skuCatalog->has($sku) || blank($value)) {
                        return;
                    }

                    $skuProductName = $skuCatalog->get($sku)?->product_name;
                    if ($skuProductName && $value !== $skuProductName) {
                        $fail("Product Name untuk SKU '{$sku}' harus '{$skuProductName}'.");
                    }
                },
            ],
            'sku' => empty($activeSkuOptions) ? ['nullable', 'string'] : ['nullable', 'string', Rule::in($activeSkuOptions)],
            'category_review' => ['required', 'string', Rule::in($categoryReviewOptions)],
            'cause_by' => [
                'required',
                'string',
                Rule::in($causeByOptions),
                function ($attribute, $value, $fail) use ($request) {
                    $categoryReview = $request->input('category_review');
                    if (!$categoryReview) {
                        return;
                    }

                    $defaultCauseBy = SubCase::query()
                        ->where('name', $categoryReview)
                        ->value('default_cause_by');

                    if ($defaultCauseBy && $value !== $defaultCauseBy) {
                        $fail("Cause/By untuk Sub Case '{$categoryReview}' harus '{$defaultCauseBy}'.");
                    }
                },
            ],
            'review_notes' => ['required', 'string'],
            'progress' => ['required', 'string', Rule::in(['Follow Up Customer', 'Auto Reply'])],
            'tanggal_update' => ['required', 'date'],
            'cs_name' => ['required', 'string', Rule::in($csNameOptions)],
        ];
    }

    private function brandOptions(): array
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function platformOptions(): array
    {
        return Platform::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function categoryReviewOptions(): array
    {
        return SubCase::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function causeByOptions(): array
    {
        return CauseBy::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
