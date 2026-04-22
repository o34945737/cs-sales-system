<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\DailyProductivity;
use App\Models\Oos;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const LAST_STEP_EXTERNAL = [
        'Analysis MP (Late Delivery)',
        'Analysis MP (Non Late Delivery)',
        'Follow Up Courier (MP Non aktif)',
        'On the way return & plan banding/refund/replace',
        'Pending return & plan banding/refund/replace',
        'Pending RGO & plan refund',
        'Waiting Claim',
        'Waiting Data From Customer',
    ];

    private const LAST_STEP_INTERNAL = [
        'Follow Up to After Sales Team',
        'Follow Up KAE to Brand/KAM',
        'Kingdee Processing',
        'Follow Up WH',
        'Replacement product on the way',
    ];

    public function index(Request $request): Response
    {
        $today     = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->format('Y-m');

        // ── Section 1-4: Summary cards ────────────────────────────────────────
        $pendingComplaintCount  = Complaint::query()->where('status', 'Pending')->count();
        $pendingOtCount         = OrderTracking::query()->where('status', 'Pending')->count();
        $oosTodayCount          = Oos::query()->whereDate('tanggal_input', $today)->count();
        $totalComplaintCount    = Complaint::query()->count();

        // ── Section 5-7: Weekly trend (last 7 days) ───────────────────────────
        $weeklyComplaint = $this->weeklyTrend(
            Complaint::query(),
            'created_at',
            ['new' => null, 'solved' => fn($q) => $q->where('status', 'Solved')]
        );

        $weeklyBadReview = $this->weeklySimple(BadReview::query(), 'created_at');
        $weeklyOos       = $this->weeklySimple(Oos::query(), 'created_at');

        // ── Section 8: Pending by Cause By ───────────────────────────────────
        $pendingByCauseBy = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('cause_by as label, COUNT(*) as total')
            ->groupBy('cause_by')
            ->orderByDesc('total')
            ->get();

        // ── Section 9: Pending by Level Customer (complaint_power) ────────────
        $pendingByLevel = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('complaint_power as label, COUNT(*) as total')
            ->groupBy('complaint_power')
            ->orderByDesc('total')
            ->get();

        // ── Section 10: Pending by Sub Case + SLA ────────────────────────────
        $pendingBySubCase = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('sub_case as label, COUNT(*) as total, SUM(CASE WHEN sla = "SLA" THEN 1 ELSE 0 END) as sla_ok, SUM(CASE WHEN sla != "SLA" AND sla IS NOT NULL THEN 1 ELSE 0 END) as sla_breach')
            ->groupBy('sub_case')
            ->orderByDesc('total')
            ->get();

        // ── Section 11: Pending by Last Step External ─────────────────────────
        $pendingByLastStepExternal = Complaint::query()
            ->where('status', 'Pending')
            ->whereIn('last_step', self::LAST_STEP_EXTERNAL)
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        // ── Section 12: Pending by Last Step Internal ─────────────────────────
        $pendingByLastStepInternal = Complaint::query()
            ->where('status', 'Pending')
            ->whereIn('last_step', self::LAST_STEP_INTERNAL)
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        // ── Section 13: Bad Review current month by Brand ─────────────────────
        $badReviewByBrand = BadReview::query()
            ->where('month', $thisMonth)
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        // ── Section 14: Bad Review current month by Category ─────────────────
        $badReviewByCategory = BadReview::query()
            ->where('month', $thisMonth)
            ->selectRaw('category_review as label, COUNT(*) as total')
            ->groupBy('category_review')
            ->orderByDesc('total')
            ->get();

        // ── Section 15: Pending OT by Brand ──────────────────────────────────
        $pendingOtByBrand = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        // ── Section 16: Pending OT by Platform ───────────────────────────────
        $pendingOtByPlatform = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('platform as label, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        // ── Section 17: Pending OT by Logistics ──────────────────────────────
        $pendingOtByLogistics = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('logistics as label, COUNT(*) as total')
            ->groupBy('logistics')
            ->orderByDesc('total')
            ->get();

        // ── Section 18: Pending OT by Order Date ─────────────────────────────
        $pendingOtByOrderDate = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('DATE(tanggal_order) as label, COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ── Section 19: Pending OT by Auto Track ─────────────────────────────
        $pendingOtByAutoTrack = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('automation_track as label, COUNT(*) as total')
            ->groupBy('automation_track')
            ->orderByDesc('total')
            ->get();

        // ── Section 20: Pending OT by Data Source ────────────────────────────
        $pendingOtByDataSource = OrderTracking::query()
            ->where('status', 'Pending')
            ->selectRaw('data_source as label, COUNT(*) as total')
            ->groupBy('data_source')
            ->orderByDesc('total')
            ->get();

        // ── Section 21: OOS today & yesterday without Done Blast ─────────────
        $oosTodayNoDoneBlast     = Oos::query()->whereDate('tanggal_input', $today)->where('update_cs', '!=', 'Done Blast')->count();
        $oosYesterdayNoDoneBlast = Oos::query()->whereDate('tanggal_input', $yesterday)->where('update_cs', '!=', 'Done Blast')->count();

        // ── Section 22: OOS current month by Brand ────────────────────────────
        $oosByBrand = Oos::query()
            ->where('month', $thisMonth)
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        // ── Section 23-26: Agent performance today ────────────────────────────
        $agentDistributed = Complaint::query()
            ->whereDate('created_at', $today)
            ->selectRaw('cs_name as agent, COUNT(*) as total')
            ->groupBy('cs_name')
            ->orderByDesc('total')
            ->get();

        $agentHandled = Complaint::query()
            ->where('status', 'Pending')
            ->whereNotNull('cs_name')
            ->selectRaw('cs_name as agent, COUNT(*) as total')
            ->groupBy('cs_name')
            ->orderByDesc('total')
            ->get();

        $agentSolved = Complaint::query()
            ->where('status', 'Solved')
            ->whereDate('updated_at', $today)
            ->selectRaw('cs_name as agent, COUNT(*) as total')
            ->groupBy('cs_name')
            ->orderByDesc('total')
            ->get();

        $agentRecap = Complaint::query()
            ->where('month', $thisMonth)
            ->selectRaw('cs_name as agent, COUNT(*) as total, SUM(CASE WHEN status="Solved" THEN 1 ELSE 0 END) as solved, SUM(CASE WHEN status="Pending" THEN 1 ELSE 0 END) as pending')
            ->groupBy('cs_name')
            ->orderByDesc('total')
            ->get();

        // ── Section 27: Productivity form (today's entries) ──────────────────
        $productivity = DailyProductivity::query()
            ->whereDate('tanggal', $today)
            ->orderBy('cs_name')
            ->get();

        // ── Section A: Controller Brand Real Time ─────────────────────────────
        $brandRealTime = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('brand as label, COUNT(*) as total, SUM(CASE WHEN complaint_power="Hard Complaint" THEN 1 ELSE 0 END) as hard, SUM(CASE WHEN complaint_power="Normal Complaint" THEN 1 ELSE 0 END) as normal')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        // ── Section B: Agent Interface ────────────────────────────────────────
        // B28: totals
        $agentInterface28 = [
            'solved'     => Complaint::query()->where('status', 'Solved')->whereDate('updated_at', $today)->count(),
            'pending'    => Complaint::query()->where('status', 'Pending')->count(),
            'whitelist'  => Complaint::query()->whereNotNull('reason_whitelist')->where('reason_whitelist', '!=', '')->count(),
        ];

        // B29: by priority
        $agentInterface29 = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('priority as label, COUNT(*) as total')
            ->groupBy('priority')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard', [
            // Cards
            'pendingComplaintCount'  => $pendingComplaintCount,
            'pendingOtCount'         => $pendingOtCount,
            'oosTodayCount'          => $oosTodayCount,
            'totalComplaintCount'    => $totalComplaintCount,

            // Weekly trends
            'weeklyComplaint'        => $weeklyComplaint,
            'weeklyBadReview'        => $weeklyBadReview,
            'weeklyOos'              => $weeklyOos,

            // Complaint breakdown
            'pendingByCauseBy'       => $pendingByCauseBy,
            'pendingByLevel'         => $pendingByLevel,
            'pendingBySubCase'       => $pendingBySubCase,
            'pendingByLastStepExt'   => $pendingByLastStepExternal,
            'pendingByLastStepInt'   => $pendingByLastStepInternal,

            // Bad Review
            'badReviewByBrand'       => $badReviewByBrand,
            'badReviewByCategory'    => $badReviewByCategory,

            // OT breakdown
            'pendingOtByBrand'       => $pendingOtByBrand,
            'pendingOtByPlatform'    => $pendingOtByPlatform,
            'pendingOtByLogistics'   => $pendingOtByLogistics,
            'pendingOtByOrderDate'   => $pendingOtByOrderDate,
            'pendingOtByAutoTrack'   => $pendingOtByAutoTrack,
            'pendingOtByDataSource'  => $pendingOtByDataSource,

            // OOS
            'oosTodayNoDoneBlast'    => $oosTodayNoDoneBlast,
            'oosYesterdayNoDoneBlast'=> $oosYesterdayNoDoneBlast,
            'oosByBrand'             => $oosByBrand,

            // Agent
            'agentDistributed'       => $agentDistributed,
            'agentHandled'           => $agentHandled,
            'agentSolved'            => $agentSolved,
            'agentRecap'             => $agentRecap,

            // Productivity
            'productivity'           => $productivity,
            'today'                  => $today->toDateString(),

            // Section A
            'brandRealTime'          => $brandRealTime,

            // Section B
            'agentInterface28'       => $agentInterface28,
            'agentInterface29'       => $agentInterface29,
        ]);
    }

    public function storeProductivity(Request $request)
    {
        $data = $request->validate([
            'cs_name'               => ['required', 'string', 'max:100'],
            'tanggal'               => ['required', 'date'],
            'complaint_handled'     => ['required', 'integer', 'min:0'],
            'complaint_solved'      => ['required', 'integer', 'min:0'],
            'bad_review_handled'    => ['required', 'integer', 'min:0'],
            'order_tracking_handled'=> ['required', 'integer', 'min:0'],
            'oos_handled'           => ['required', 'integer', 'min:0'],
            'total_ticket'          => ['required', 'integer', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:1000'],
        ]);

        DailyProductivity::updateOrCreate(
            ['cs_name' => $data['cs_name'], 'tanggal' => $data['tanggal']],
            $data
        );

        return redirect()->route('dashboard')->with('success', 'Produktivitas berhasil disimpan.');
    }

    private function weeklyTrend($baseQuery, string $dateCol, array $series): array
    {
        $days = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->toDateString());

        return $days->map(function ($date) use ($baseQuery, $dateCol, $series) {
            $point = ['date' => $date];
            foreach ($series as $key => $scope) {
                $q = (clone $baseQuery)->whereDate($dateCol, $date);
                if ($scope !== null) {
                    $scope($q);
                }
                $point[$key] = $q->count();
            }
            return $point;
        })->values()->all();
    }

    private function weeklySimple($baseQuery, string $dateCol): array
    {
        $days = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->toDateString());

        return $days->map(fn($date) => [
            'date'  => $date,
            'total' => (clone $baseQuery)->whereDate($dateCol, $date)->count(),
        ])->values()->all();
    }
}
