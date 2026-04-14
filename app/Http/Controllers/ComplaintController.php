<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\CauseBy;
use App\Models\Complaint;
use App\Models\LastStep;
use App\Models\Platform;
use App\Models\ReasonLateResponse;
use App\Models\ReasonWhitelist;
use App\Models\SubCase;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;
use Inertia\Inertia;

class ComplaintController extends Controller
{
    private const SOURCE_OPTIONS = ['AFTERSALES', 'B2B', 'PRESALES', 'SOCMED', 'BRAND/OPS'];

    public function index(Request $request)
    {
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
        $causeByNames = CauseBy::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->all();
        $lastStepOptions = LastStep::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'status_result', 'priority_level'])
            ->map(fn (LastStep $lastStep) => [
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
            'brandOptions' => $brandOptions,
            'platformOptions' => $platformOptions,
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
                ->mapWithKeys(fn (SubCase $subCase) => [$subCase->name => $subCase->default_cause_by])
                ->all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->complaintRules(), [
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
        $request->validate($this->complaintRules(forUpdate: true), [
            'tanggal_step_cs_selesai.required_if' => 'Tanggal harus diisi jika Step CS Selesai = YES.',
            'reason_whitelist.required_if' => 'Reason Whitelist wajib diisi jika Last Step = Claim Reject.',
            'reason_late_respons.required_if' => 'Reason Late Respons wajib diisi jika Whitelist = Late Respons.',
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

    private function complaintRules(bool $forUpdate = false): array
    {
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
            'source' => [$required, 'string', Rule::in(self::SOURCE_OPTIONS)],
            'tanggal_complaint' => [$required, 'date'],
            'tanggal_order' => [$required, 'date'],
            'jam_customer_complaint' => [$required, 'date_format:H:i:s'],
            'brand' => [$required, 'string', Rule::in($brandOptions)],
            'platform' => [$required, 'string', Rule::in($platformOptions)],
            'order_id' => [$required, 'string'],
            'username' => [$required, 'string'],
            'resi' => [$required, 'string'],
            'sku' => [$required, 'string'],
            'sub_case' => [$required, 'string', Rule::in($subCaseOptions)],
            'cause_by' => [$required, 'string', Rule::in($causeByOptions)],
            'summary_case' => [$required, 'string'],
            'update_long_text' => [$required, 'string'],
            'cs_name' => [$required, 'string'],
            'last_step' => [$required, 'string', Rule::in($lastStepOptions)],
            'step_cs_selesai' => [$required, Rule::in(['YES', 'NO'])],
            'tanggal_update' => [$required, 'date'],
            'tanggal_step_cs_selesai' => ['required_if:step_cs_selesai,YES', 'nullable', 'date'],
            'reason_whitelist' => ['required_if:last_step,Claim Reject', 'nullable', 'string', Rule::in($reasonWhitelistOptions)],
            'reason_late_respons' => ['required_if:reason_whitelist,Late Respons', 'nullable', 'string', Rule::in($reasonLateResponseOptions)],
            'value_of_product' => ['nullable', 'numeric', 'min:0'],
            'proof' => ['nullable', 'string', 'max:1000'],
            'video_unboxing' => [$forUpdate ? 'sometimes' : 'nullable', 'nullable', 'file', 'mimes:mp4,mov,ogg,qt', 'max:20000'],
        ];
    }
}
