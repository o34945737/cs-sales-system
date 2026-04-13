<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
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
            'filters' => $request->only(['search', 'status', 'sort', 'order', 'cs_name']),
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
        // Validation with Conditional Logic
        $request->validate([
            'step_cs_selesai' => 'nullable|in:YES,NO',
            'last_step' => 'nullable|string',

            // Conditional validation rules:
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES',
            'reason_whitelist' => 'required_if:last_step,Claim Reject',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons',
            
            // File validation handling
            'video_unboxing' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000', // max 20MB as sample
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5000',
        ], [
            // Custom Messages
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.'
        ]);

        $data = collect($request->all())->except(['_token', 'video_unboxing', 'proof'])->toArray();

        // Handle File Uploads ke folder storage/app/public/
        if ($request->hasFile('video_unboxing')) {
            $data['video_unboxing'] = $request->file('video_unboxing')->store('complaints/videos', 'public');
        }
        if ($request->hasFile('proof')) {
            $data['proof'] = $request->file('proof')->store('complaints/proofs', 'public');
        }

        Complaint::create($data);

        return redirect()->back()->with('success', 'Data Complaint berhasil disimpan.');
    }

    public function update(Request $request, Complaint $complaint)
    {
        // Validasi conditional serupa untuk versi Update
        $request->validate([
            'step_cs_selesai' => 'nullable|in:YES,NO',
            'last_step' => 'nullable|string',
            'tanggal_step_cs_selesai' => 'required_if:step_cs_selesai,YES',
            'reason_whitelist' => 'required_if:last_step,Claim Reject',
            'reason_late_respons' => 'required_if:reason_whitelist,Late Respons',
        ]);
        
        // Handling update data bisa diletakkan di sini
        $data = collect($request->all())->except(['_token', '_method'])->toArray();
        $complaint->update($data);

        return redirect()->back()->with('success', 'Data Complaint berhasil diupdate.');
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();
        return redirect()->back()->with('success', 'Data Complaint dihapus.');
    }
}
