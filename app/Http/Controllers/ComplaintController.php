<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Throwable;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $allowedSortFields = [
            'created_at',
            'tanggal_complaint',
            'tanggal_order',
            'order_id',
            'username',
            'brand',
            'status',
            'priority',
            'sla',
            'cs_name',
        ];

        $sortField = $request->get('sort', 'tanggal_complaint');
        $sortField = in_array($sortField, $allowedSortFields, true) ? $sortField : 'tanggal_complaint';
        $sortOrder = $request->get('order', 'desc') === 'asc' ? 'asc' : 'desc';

        $applySearch = function ($query) use ($request) {
            if ($request->filled('search')) {
                $search = trim((string) $request->search);

                $query->where(function ($q) use ($search) {
                    $q->where('order_id', 'like', "%{$search}%")
                        ->orWhere('resi', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('platform', 'like', "%{$search}%")
                        ->orWhere('summary_case', 'like', "%{$search}%")
                        ->orWhere('cs_name', 'like', "%{$search}%");
                });
            }

            return $query;
        };

        $applyStatus = function ($query) use ($request) {
            if ($request->filled('status') && $request->status !== 'All') {
                $query->where('status', $request->status);
            }

            return $query;
        };

        $applyCsFilter = function ($query) use ($request) {
            if ($request->filled('cs_name')) {
                $query->where('cs_name', $request->cs_name);
            }

            return $query;
        };

        $listQuery = Complaint::query();
        $applySearch($listQuery);
        $applyStatus($listQuery);
        $applyCsFilter($listQuery);

        if ($sortField === 'sla') {
            $listQuery->orderByRaw('COALESCE(DATEDIFF(COALESCE(tanggal_update, CURRENT_DATE), tanggal_complaint), 0) ' . $sortOrder);
        } else {
            $listQuery->orderBy($sortField, $sortOrder);
        }

        $statusSummaryQuery = Complaint::query();
        $applySearch($statusSummaryQuery);
        $applyCsFilter($statusSummaryQuery);

        $csSummaryQuery = Complaint::query();
        $applySearch($csSummaryQuery);
        $applyStatus($csSummaryQuery);

        $statusSummary = [
            'all' => (clone $statusSummaryQuery)->count(),
            'pending' => (clone $statusSummaryQuery)->where('status', 'Pending')->count(),
            'solved' => (clone $statusSummaryQuery)->where('status', 'Solved')->count(),
            'whitelist' => (clone $statusSummaryQuery)->where('status', 'Whitelist')->count(),
        ];

        $overviewQuery = Complaint::query();
        $applySearch($overviewQuery);
        $applyCsFilter($overviewQuery);

        return Inertia::render('Complaints/Index', [
            'complaints' => $listQuery->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'sort' => $request->input('sort'),
                'order' => $request->input('order'),
                'cs_name' => $request->input('cs_name'),
            ],
            'cs_summary' => $csSummaryQuery
                ->select('cs_name', \DB::raw('count(*) as total'))
                ->groupBy('cs_name')
                ->orderByDesc('total')
                ->get(),
            'status_summary' => $statusSummary,
            'overview' => [
                'total' => (clone $overviewQuery)->count(),
                'pending' => (clone $overviewQuery)->where('status', 'Pending')->count(),
                'solved' => (clone $overviewQuery)->where('status', 'Solved')->count(),
                'whitelist' => (clone $overviewQuery)->where('status', 'Whitelist')->count(),
                'agents' => Complaint::query()
                    ->when($request->filled('search'), function ($query) use ($request) {
                        $search = trim((string) $request->search);

                        $query->where(function ($q) use ($search) {
                            $q->where('order_id', 'like', "%{$search}%")
                                ->orWhere('resi', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhere('brand', 'like', "%{$search}%")
                                ->orWhere('platform', 'like', "%{$search}%")
                                ->orWhere('summary_case', 'like', "%{$search}%")
                                ->orWhere('cs_name', 'like', "%{$search}%");
                        });
                    })
                    ->distinct('cs_name')
                    ->whereNotNull('cs_name')
                    ->count('cs_name'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'tanggal_complaint' => 'required|date',
            'tanggal_order' => 'required|date',
            'jam_customer_complaint' => 'required',
            'brand' => 'required|string',
            'platform' => 'required|string',
            'order_id' => 'required|string',
            'username' => 'required|string',
            'resi' => 'required|string',
            'sku' => 'required|string',
            'sub_case' => 'required|string',
            'cause_by' => 'required|string',
            'summary_case' => 'required|string',
            'update_long_text' => 'required|string',
            'cs_name' => 'required|string',
            'last_step' => 'required|string',
            'step_cs_selesai' => 'required|in:YES,NO',
            'tanggal_update' => 'required|date',
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES|nullable|date',
            'reason_whitelist' => 'required_if:last_step,Claim Reject|nullable|string',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons|nullable|string',
            'value_of_product' => 'nullable|numeric|min:0',
            'proof' => 'nullable|string|max:1000',
            'video_unboxing' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000',
        ], [
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.',
        ]);

        $data = collect($request->all())->except(['_token', 'video_unboxing'])->toArray();

        try {
            if ($request->hasFile('video_unboxing')) {
                $data['video_unboxing'] = $request->file('video_unboxing')->store('complaints/videos', 'public');
            }

            Complaint::create($data);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Complaint gagal dibuat. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Complaint berhasil dibuat.');
    }

    public function update(Request $request, Complaint $complaint)
    {
        $request->validate([
            'step_cs_selesai' => 'nullable|in:YES,NO',
            'last_step' => 'nullable|string',
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES|nullable|date',
            'reason_whitelist' => 'required_if:last_step,Claim Reject|nullable|string',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons|nullable|string',
            'proof' => 'nullable|string|max:1000',
        ]);

        $data = collect($request->all())->except(['_token', '_method'])->toArray();

        try {
            $complaint->update($data);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Complaint gagal diperbarui. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Complaint berhasil diperbarui.');
    }

    public function destroy(Complaint $complaint)
    {
        try {
            $complaint->delete();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->back()->with('error', 'Complaint gagal dihapus. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Complaint berhasil dihapus.');
    }
}
