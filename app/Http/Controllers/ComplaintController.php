<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Complaint;
use App\Models\ComplaintPower;
use App\Models\ComplaintSource;
use App\Models\LastStep;
use App\Models\Oos;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SkuCode;
use App\Models\SubCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $priorityOrder = ['Cool', 'Mines', 'P1', 'P2', 'P3', 'P4', 'P5', 'P6', 'P7'];

        $sourceOptions = ComplaintSource::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $complaintPowerOptions = ComplaintPower::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
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
        $subCaseOptions = SubCase::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $skuCodeOptions = SkuCode::query()
            ->orderBy('sku')
            ->get(['sku', 'product_name'])
            ->map(fn(SkuCode $skuCode) => [
                'sku' => $skuCode->sku,
                'product_name' => $skuCode->product_name,
            ])
            ->all();
        $causeByNames = CauseBy::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $lastStepOptions = LastStep::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'status_result', 'priority_level'])
            ->map(fn(LastStep $lastStep) => [
                'label' => $lastStep->name,
                'value' => $lastStep->name,
                'status_result' => $lastStep->status_result,
                'priority_level' => $lastStep->priority_level,
            ])
            ->all();
        $reasonWhitelistOptions = ReasonWhitelist::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $reasonLateResponseOptions = ReasonLateResponse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $csNameOptions = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();

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
            'history',
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
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('sub_case', 'like', "%{$search}%")
                        ->orWhere('cause_by', 'like', "%{$search}%")
                        ->orWhere('part_of_bad', 'like', "%{$search}%")
                        ->orWhere('summary_case', 'like', "%{$search}%")
                        ->orWhere('cs_name', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('priority', 'like', "%{$search}%");
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

        $applyBrandFilter = function ($query) use ($request) {
            if ($request->filled('brand') && $request->brand !== 'All') {
                $query->where('brand', $request->brand);
            }

            return $query;
        };

        $applyPriorityFilter = function ($query) use ($request) {
            if ($request->filled('priority') && $request->priority !== 'All') {
                $query->where('priority', $request->priority);
            }

            return $query;
        };

        $applySourceFilter = function ($query) use ($request) {
            if ($request->filled('source') && $request->source !== 'All') {
                $query->where('source', $request->source);
            }

            return $query;
        };

        $applyPlatformFilter = function ($query) use ($request) {
            if ($request->filled('platform') && $request->platform !== 'All') {
                $query->where('platform', $request->platform);
            }

            return $query;
        };

        $applyHistoryFilter = function ($query) use ($request) {
            if ($request->filled('history') && $request->history === 'Repeat') {
                $query->whereNotNull('history')->where('history', '!=', '');
            }

            return $query;
        };

        $applySubCaseFilter = function ($query) use ($request) {
            if ($request->filled('sub_case') && $request->sub_case !== 'All') {
                $query->where('sub_case', $request->sub_case);
            }

            return $query;
        };

        $listQuery = Complaint::query();
        $applySearch($listQuery);
        $applyStatus($listQuery);
        $applyCsFilter($listQuery);
        $applyBrandFilter($listQuery);
        $applyPriorityFilter($listQuery);
        $applySourceFilter($listQuery);
        $applyPlatformFilter($listQuery);
        $applyHistoryFilter($listQuery);
        $applySubCaseFilter($listQuery);

        if ($sortField === 'sla') {
            $listQuery->orderByRaw(
                "COALESCE(CASE
                    WHEN status = 'Solved' AND tanggal_update IS NOT NULL THEN DATEDIFF(tanggal_update, tanggal_complaint)
                    ELSE DATEDIFF(CURRENT_DATE, tanggal_complaint)
                END, 0) {$sortOrder}"
            );
        } else {
            $listQuery->orderBy($sortField, $sortOrder);
        }

        $statusSummaryQuery = Complaint::query();
        $applyStatus($statusSummaryQuery);
        $applyCsFilter($statusSummaryQuery);
        $applyBrandFilter($statusSummaryQuery);
        $applyPriorityFilter($statusSummaryQuery);
        $applySourceFilter($statusSummaryQuery);
        $applyPlatformFilter($statusSummaryQuery);
        $applyHistoryFilter($statusSummaryQuery);
        $applySubCaseFilter($statusSummaryQuery);

        $csSummaryQuery = Complaint::query();
        $applySearch($csSummaryQuery);
        $applyStatus($csSummaryQuery);
        $applyBrandFilter($csSummaryQuery);
        $applyPriorityFilter($csSummaryQuery);
        $applySourceFilter($csSummaryQuery);
        $applyPlatformFilter($csSummaryQuery);
        $applyHistoryFilter($csSummaryQuery);
        $applySubCaseFilter($csSummaryQuery);

        $prioritySummaryQuery = Complaint::query();
        $applySearch($prioritySummaryQuery);
        $applyStatus($prioritySummaryQuery);
        $applyCsFilter($prioritySummaryQuery);
        $applyBrandFilter($prioritySummaryQuery);
        $applySourceFilter($prioritySummaryQuery);
        $applyPlatformFilter($prioritySummaryQuery);
        $applyHistoryFilter($prioritySummaryQuery);
        $applySubCaseFilter($prioritySummaryQuery);

        $statusSummary = [
            'all' => (clone $statusSummaryQuery)->count(),
            'pending' => (clone $statusSummaryQuery)->where('status', 'Pending')->count(),
            'solved' => (clone $statusSummaryQuery)->where('status', 'Solved')->count(),
            'whitelist' => (clone $statusSummaryQuery)->where('status', 'Whitelist')->count(),
        ];

        $overviewQuery = Complaint::query();
        $applySearch($overviewQuery);
        $applyStatus($overviewQuery);
        $applyCsFilter($overviewQuery);
        $applyBrandFilter($overviewQuery);
        $applyPriorityFilter($overviewQuery);
        $applySourceFilter($overviewQuery);
        $applyPlatformFilter($overviewQuery);
        $applyHistoryFilter($overviewQuery);
        $applySubCaseFilter($overviewQuery);

        return Inertia::render('Complaints/Index', [
            'complaints' => $listQuery->paginate(15)->withQueryString(),
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'brand' => $request->input('brand'),
                'priority' => $request->input('priority'),
                'sort' => $request->input('sort'),
                'order' => $request->input('order'),
                'cs_name' => $request->input('cs_name'),
                'source' => $request->input('source'),
                'platform' => $request->input('platform'),
                'history' => $request->input('history'),
                'sub_case' => $request->input('sub_case'),
            ],
            'cs_summary' => $csSummaryQuery
                ->select('cs_name', DB::raw('count(*) as total'))
                ->groupBy('cs_name')
                ->orderByDesc('total')
                ->get(),
            'status_summary' => $statusSummary,
            'overview' => [
                'total' => (clone $overviewQuery)->count(),
                'pending' => (clone $overviewQuery)->where('status', 'Pending')->count(),
                'solved' => (clone $overviewQuery)->where('status', 'Solved')->count(),
                'whitelist' => (clone $overviewQuery)->where('status', 'Whitelist')->count(),
                'agents' => (clone $overviewQuery)
                    ->distinct('cs_name')
                    ->whereNotNull('cs_name')
                    ->count('cs_name'),
            ],
            'priority_summary' => collect($priorityOrder)
                ->mapWithKeys(fn(string $priority) => [$priority => (clone $prioritySummaryQuery)->where('priority', $priority)->count()])
                ->all(),
            'brandOptions' => $brandOptions,
            'csNameOptions' => $csNameOptions,
            'platformOptions' => $platformOptions,
            'skuCodeOptions' => $skuCodeOptions,
            'sourceOptions' => $sourceOptions,
            'complaintPowerOptions' => $complaintPowerOptions,
            'subCaseOptions' => $subCaseOptions,
            'causeByOptions' => array_values(array_unique(array_merge(
                ['?'],
                $causeByNames
            ))),
            'lastStepOptions' => $lastStepOptions,
            'reasonWhitelistOptions' => $reasonWhitelistOptions,
            'reasonLateResponseOptions' => $reasonLateResponseOptions,
            'autoCauseByMap' => SubCase::query()
                ->where('is_active', true)
                ->whereNotNull('default_cause_by')
                ->orderBy('name')
                ->get(['name', 'default_cause_by'])
                ->mapWithKeys(fn(SubCase $subCase) => [$subCase->name => $subCase->default_cause_by])
                ->all(),
            'oosOrderIds' => Oos::query()
                ->whereNotNull('order_id')
                ->distinct()
                ->orderBy('order_id')
                ->pluck('order_id')
                ->all(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = $this->complaintRules($request);
        $data = $request->validate($rules, [
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.',
            'jam_customer_complaint.date_format' => 'Format jam harus HH:mm atau HH:mm:ss.',
            'video_unboxing.mimes' => 'Video unboxing harus berupa file video dengan format mp4, mov, ogg, atau qt.',
            'video_unboxing.max' => 'Ukuran video unboxing maksimal 5 MB.',
            'proof_attachment.mimes' => 'Proof attachment harus berupa file dengan format jpg, jpeg, png, pdf, mp4, mov, ogg, atau qt.',
            'proof_attachment.max' => 'Ukuran proof attachment maksimal 500 KB.',
        ]);

        try {
            if ($request->hasFile('video_unboxing')) {
                $data['video_unboxing'] = $request->file('video_unboxing')->store('complaints/videos', 'public');
            }

            if ($request->hasFile('proof_attachment')) {
                $data['proof_attachment'] = $request->file('proof_attachment')->store('complaints/proofs', 'public');
            }

            Complaint::create($data);
        } catch (\Exception $exception) {
            report($exception);

            return back()->withErrors(['general' => 'Database Error: ' . $exception->getMessage()])->withInput();
        }

        return redirect()->back()->with('success', 'Complaint berhasil dibuat.');
    }

    /**
     * API Endpoint for real-time customer history check
     */
    public function getCustomerHistory($username)
    {
        $count = Complaint::where('username', $username)->count();

        $label = '';
        if ($count === 1) {
            $label = 'Customer ini complaint ke 2';
        } elseif ($count >= 2) {
            $newCount = $count + 1;
            $label = "Customer ini complaint ke {$newCount}x";
        }

        return response()->json([
            'username' => $username,
            'count' => $count,
            'label' => $label,
        ]);
    }

    public function show(Complaint $complaint)
    {
        return response()->json([
            'complaint' => $complaint,
        ]);
    }

    public function update(Request $request, Complaint $complaint)
    {
        $rules = $this->complaintRules($request, forUpdate: true);
        $data = $request->validate($rules, [
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.',
            'jam_customer_complaint.date_format' => 'Format jam harus HH:mm atau HH:mm:ss.',
            'video_unboxing.mimes' => 'Video unboxing harus berupa file video dengan format mp4, mov, ogg, atau qt.',
            'video_unboxing.max' => 'Ukuran video unboxing maksimal 5 MB.',
            'proof_attachment.mimes' => 'Proof attachment harus berupa file dengan format jpg, jpeg, png, pdf, mp4, mov, ogg, atau qt.',
            'proof_attachment.max' => 'Ukuran proof attachment maksimal 500 KB.',
        ]);

        try {
            if ($request->hasFile('video_unboxing')) {
                $oldVideoUnboxing = $complaint->video_unboxing;
                $data['video_unboxing'] = $request->file('video_unboxing')->store('complaints/videos', 'public');
                $this->deletePublicFile($oldVideoUnboxing);
            } else {
                unset($data['video_unboxing']);
            }

            if ($request->hasFile('proof_attachment')) {
                $oldProofAttachment = $complaint->proof_attachment;
                $data['proof_attachment'] = $request->file('proof_attachment')->store('complaints/proofs', 'public');
                $this->deletePublicFile($oldProofAttachment);
            } else {
                unset($data['proof_attachment']);
            }

            $complaint->update($data);
        } catch (\Exception $exception) {
            report($exception);

            return back()->withErrors(['general' => 'Database Error: ' . $exception->getMessage()])->withInput();
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

        return redirect()->back()->with('success', 'Complaint berhasil diarsipkan.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:complaints,id'],
        ]);

        try {
            Complaint::whereIn('id', $request->input('ids', []))->delete();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->back()->with('error', 'Gagal mengarsipkan beberapa data. Silakan coba lagi.');
        }

        return redirect()->back()->with('success', 'Semua data yang dipilih berhasil diarsipkan.');
    }

    private function complaintRules(Request $request, bool $forUpdate = false): array
    {
        $sourceOptions = ComplaintSource::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $complaintPowerOptions = ComplaintPower::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
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
        $subCaseOptions = SubCase::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $skuOptions = SkuCode::query()
            ->whereNotNull('sku')
            ->orderBy('sku')
            ->pluck('sku')
            ->all();
        $csNameOptions = User::query()
            ->whereHas('roles', fn($q) => $q->where('name', 'CS'))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $causeByOptions = array_values(array_unique(array_merge(
            ['?'],
            CauseBy::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name')
                ->all()
        )));
        $lastStepOptions = LastStep::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $reasonWhitelistOptions = ReasonWhitelist::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $reasonLateResponseOptions = ReasonLateResponse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $required = $forUpdate ? 'sometimes' : 'required';

        return [
            'source' => [$required, 'string', Rule::in($sourceOptions)],
            'tanggal_complaint' => [$required, 'date'],
            'tanggal_order' => [$required, 'date'],
            'jam_customer_complaint' => [$required, 'string'], // Simplified validation to allow multiple formats
            'brand' => [$required, 'string', Rule::in($brandOptions)],
            'platform' => [$required, 'string', Rule::in($platformOptions)],
            'order_id' => [$required, 'string'],
            'username' => [$required, 'string'],
            'resi' => [$required, 'string'],
            'sku' => empty($skuOptions) ? [$required, 'string'] : [$required, 'string', Rule::in($skuOptions)],
            'product_name' => ['nullable', 'string'],
            'qty' => ['nullable', 'integer'],
            'sub_case' => [$required, 'string', Rule::in($subCaseOptions)],
            'cause_by' => [
                $required,
                'string',
                Rule::in($causeByOptions),
                function ($attribute, $value, $fail) use ($request) {
                    $subCase = $request->input('sub_case');
                    if ($subCase) {
                        $defaultCauseBy = SubCase::where('name', $subCase)->value('default_cause_by');
                        if ($defaultCauseBy && $value !== $defaultCauseBy) {
                            $fail("Cause/By untuk Sub Case '{$subCase}' harus '{$defaultCauseBy}'.");
                        }
                    }
                },
            ],
            'summary_case' => [$required, 'string'],
            'update_long_text' => [$required, 'string'],
            'part_of_bad' => ['nullable', 'string', 'max:255'],
            'cs_name' => [$required, 'string', Rule::in($csNameOptions)],
            'last_step' => [$required, 'string', Rule::in($lastStepOptions)],
            'step_cs_selesai' => [$required, 'string', Rule::in(['YES', 'NO'])],
            'complaint_power' => empty($complaintPowerOptions) ? [$required, 'string'] : [$required, 'string', Rule::in($complaintPowerOptions)],
            'history' => ['nullable', 'string'],
            'tanggal_update' => [$required, 'date'],
            'tanggal_step_cs_selesai' => ['required_if:step_cs_selesai,YES', 'nullable', 'date'],
            'reason_whitelist' => ['required_if:last_step,Claim Reject', 'nullable', 'string', Rule::in($reasonWhitelistOptions)],
            'reason_late_respons' => ['required_if:reason_whitelist,Late Respons', 'nullable', 'string', Rule::in($reasonLateResponseOptions)],
            'proof' => ['nullable', 'string', 'max:1000'],
            'proof_attachment' => [$forUpdate ? 'sometimes' : 'nullable', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf,mp4,mov,ogg,qt', 'max:500'],
            'video_unboxing' => [$forUpdate ? 'sometimes' : 'nullable', 'nullable', 'file', 'mimes:mp4,mov,ogg,qt', 'max:5120'],
        ];
    }

    private function deletePublicFile(?string $path): void
    {
        if (! $path || preg_match('/^https?:\/\//i', $path)) {
            return;
        }

        Storage::disk('public')->delete(ltrim($path, '/'));
    }
}
