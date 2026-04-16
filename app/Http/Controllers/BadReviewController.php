<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use App\Models\Brand;
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
        // Master Data Options
        $brandOptions = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $platformOptions = Platform::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $categoryReviewOptions = SubCase::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $causeByOptions = [
            '?', 'J&T', 'SAP EXPRESS', 'ANTERAJA', 'LEX', 'POS', 
            'NINJA', 'SICEPAT', 'KURIR REKOMENDASI', 'SPX', 
            'INDOPAKET', 'GTL', 'CUSTOM LOGISTICS', 'GRAB', 
            'JNE', 'GOJEK', 'CS', 'Chat++', 'STREAMER', 'KAE', 
            'WH', 'PART', 'BRAND', 'CUSTOMER', 'PROMO'
        ];

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

        // Filtering & Searching
        $baseQuery = BadReview::query();

        // Search
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

        // Filter by Brand
        if ($request->filled('brand') && $request->brand !== 'All') {
            $baseQuery->where('brand', $request->brand);
        }

        // Filter by Platform
        if ($request->filled('platform') && $request->platform !== 'All') {
            $baseQuery->where('platform', $request->platform);
        }

        // Filter by Star (Angka)
        if ($request->filled('star') && $request->star !== 'All') {
            $baseQuery->where('star', (int)$request->star);
        }

        // Filter by Priority
        if ($request->filled('priority') && $request->priority !== 'All') {
            $baseQuery->where('priority', $request->priority);
        }
        // Filter by Status
        if ($request->filled('status') && $request->status !== 'All') {
            $baseQuery->where('status', $request->status);
        }

        // Filter by CS Name
        if ($request->filled('cs_name')) {
            $baseQuery->where('cs_name', $request->cs_name);
        }

        // Sorting
        $sortField = $request->get('sort', 'tanggal_review');
        $sortOrder = $request->get('order', 'desc') === 'asc' ? 'asc' : 'desc';
        $baseQuery->orderBy($sortField, $sortOrder);

        // Get filtered data for summaries
        $filteredQuery = clone $baseQuery;
        $allBadReviews = $filteredQuery->get();

        // Calculate summaries
        $statusSummary = [
            'all' => BadReview::count(),
            'pending' => BadReview::where('status', 'Pending')->count(),
            'solved' => BadReview::where('status', 'Solved')->count(),
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

        // Pagination
        $badReviews = $baseQuery->paginate(15)->appends($request->query());

        // Priority Summary (Static list P1-P7 for example, or dynamic based on master)
        $priorityLevels = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];
        $prioritySummary = collect($priorityLevels)->mapWithKeys(function($p) use ($allBadReviews) {
            return [$p => $allBadReviews->where('priority', $p)->count()];
        })->all();

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
            'prioritySummary' => $prioritySummary,
            'autoCauseByMap' => SubCase::where('is_active', true)->get(['name', 'default_cause_by']),
            'filters' => $request->only(['search', 'brand', 'platform', 'priority', 'status', 'cs_name', 'star']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_review' => 'required|date',
            'month' => 'nullable|string',
            'brand' => 'required|string',
            'platform' => 'required|string',
            'order_id' => 'required|string',
            'username' => 'required|string',
            'star' => 'required|integer|in:1,2,3',
            'product_name' => 'nullable|string',
            'sku' => 'nullable|string',
            'category_review' => 'required|string',
            'cause_by' => 'required|string',
            'review_notes' => 'required|string',
            'progress' => 'required|string|in:Follow Up Customer,Auto Reply',
            'tanggal_update' => 'required',
            'cs_name' => 'required|string',
            'priority' => 'required|string',
        ]);

        try {
            BadReview::create($validated);
            return redirect()->back()->with('success', 'Bad Review berhasil disimpan.');
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan Bad Review. ' . $e->getMessage());
        }
    }

    public function update(Request $request, BadReview $badReview)
    {
        $validated = $request->validate([
            'tanggal_review' => 'required|date',
            'month' => 'nullable|string',
            'brand' => 'required|string',
            'platform' => 'required|string',
            'order_id' => 'required|string',
            'username' => 'required|string',
            'star' => 'required|integer|in:1,2,3',
            'product_name' => 'nullable|string',
            'sku' => 'nullable|string',
            'category_review' => 'required|string',
            'cause_by' => 'required|string',
            'review_notes' => 'required|string',
            'progress' => 'required|string|in:Follow Up Customer,Auto Reply',
            'tanggal_update' => 'required',
            'cs_name' => 'required|string',
            'priority' => 'required|string',
        ]);

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
}
