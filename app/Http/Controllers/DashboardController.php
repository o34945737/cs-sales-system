<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\DailyProductivity;
use App\Models\LastStep;
use App\Models\Oos;
use App\Models\OrderTracking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();

        $pendingComplaintCount = Complaint::query()->where('status', 'Pending')->count();
        $pendingOtCount = OrderTracking::query()->where('status', 'Pending')->count();
        $oosTodayCount = Oos::query()->whereDate('tanggal_input', $today)->count();
        $totalTaskCount = $pendingComplaintCount + $pendingOtCount + $oosTodayCount;

        $todayProductivity = DailyProductivity::query()
            ->whereDate('tanggal', $today)
            ->orderBy('cs_name')
            ->get();
        $agentDdayStats = $this->buildAgentDdayStats($today);
        $agentRecap = $this->buildCombinedAgentRecap($agentDdayStats, $todayProductivity);

        return Inertia::render('Dashboard/Overview', [
            'pendingComplaintCount' => $pendingComplaintCount,
            'pendingOtCount' => $pendingOtCount,
            'oosTodayCount' => $oosTodayCount,
            'totalTaskCount' => $totalTaskCount,
            'agentDdayStats' => $agentDdayStats,
            'agentRecap' => $agentRecap,
            'todayProductivity' => $todayProductivity,
            'today' => $today->toDateString(),
        ]);
    }

    public function storeProductivity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cs_name' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'complaint_handled' => ['nullable', 'integer', 'min:0'],
            'complaint_solved' => ['nullable', 'integer', 'min:0'],
            'bad_review_handled' => ['nullable', 'integer', 'min:0'],
            'order_tracking_handled' => ['nullable', 'integer', 'min:0'],
            'oos_handled' => ['nullable', 'integer', 'min:0'],
            'total_ticket' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'complaint_handled' => (int) ($validated['complaint_handled'] ?? 0),
            'complaint_solved' => (int) ($validated['complaint_solved'] ?? 0),
            'bad_review_handled' => (int) ($validated['bad_review_handled'] ?? 0),
            'order_tracking_handled' => (int) ($validated['order_tracking_handled'] ?? 0),
            'oos_handled' => (int) ($validated['oos_handled'] ?? 0),
            'notes' => $validated['notes'] ?? null,
        ];

        $payload['total_ticket'] = (int) ($validated['total_ticket']
            ?? ($payload['complaint_handled'] + $payload['bad_review_handled'] + $payload['order_tracking_handled'] + $payload['oos_handled']));

        DailyProductivity::query()->updateOrCreate(
            [
                'cs_name' => $validated['cs_name'],
                'tanggal' => $validated['tanggal'],
            ],
            $payload,
        );

        return back()->with('success', 'Productivity harian berhasil disimpan.');
    }

    public function complaintAnalytics(): Response
    {
        $weeklyComplaint = collect(range(6, 0))
            ->map(fn(int $i) => Carbon::today()->subDays($i)->toDateString())
            ->map(fn(string $date) => [
                'date' => $date,
                'new' => Complaint::query()->whereDate('created_at', $date)->count(),
                'solved' => Complaint::query()
                    ->where('status', 'Solved')
                    ->whereDate('updated_at', $date)
                    ->count(),
            ])
            ->all();

        $pendingByCauseBy = $this->pendingGroupBy('cause_by');
        $pendingByPlatform = $this->pendingGroupBy('platform');
        $pendingByLevel = $this->pendingGroupBy('complaint_power');
        $pendingBySubCase = Complaint::query()
            ->where('status', 'Pending')
            ->get(['sub_case', 'tanggal_complaint'])
            ->groupBy('sub_case')
            ->map(function (Collection $rows, string|null $label) {
                $slaOk = $rows->filter(function (Complaint $complaint) {
                    if (!$complaint->tanggal_complaint) {
                        return true;
                    }

                    return Carbon::parse($complaint->tanggal_complaint)->diffInDays(Carbon::today()) <= 1;
                })->count();

                return [
                    'label' => $label,
                    'total' => $rows->count(),
                    'sla_ok' => $slaOk,
                    'sla_breach' => $rows->count() - $slaOk,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $pendingByLastStep = Complaint::query()
            ->where('status', 'Pending')
            ->whereNotNull('last_step')
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        $externalSteps = LastStep::query()->where('type', 'External')->pluck('name');
        $pendingByLastStepExternal = Complaint::query()
            ->where('status', 'Pending')
            ->whereIn('last_step', $externalSteps)
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        $internalSteps = LastStep::query()->where('type', 'Internal')->pluck('name');
        $pendingByLastStepInternal = Complaint::query()
            ->where('status', 'Pending')
            ->whereIn('last_step', $internalSteps)
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        $brandRealTime = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw(
                'brand as label, COUNT(*) as total,
                SUM(CASE WHEN complaint_power = "Hard Complaint" THEN 1 ELSE 0 END) as hard,
                SUM(CASE WHEN complaint_power = "Normal Complaint" THEN 1 ELSE 0 END) as normal'
            )
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        $totalComplaintCount = Complaint::query()->count();
        $complaintByStatus = Complaint::query()
            ->selectRaw('status as label, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard/ComplaintAnalytics', [
            'weeklyComplaint' => $weeklyComplaint,
            'pendingByCauseBy' => $pendingByCauseBy,
            'pendingByPlatform' => $pendingByPlatform,
            'pendingByLevel' => $pendingByLevel,
            'pendingBySubCase' => $pendingBySubCase,
            'pendingByLastStep' => $pendingByLastStep,
            'pendingByLastStepExternal' => $pendingByLastStepExternal,
            'pendingByLastStepInternal' => $pendingByLastStepInternal,
            'brandRealTime' => $brandRealTime,
            'totalComplaintCount' => $totalComplaintCount,
            'complaintByStatus' => $complaintByStatus,
        ]);
    }

    public function performanceMonitoring(): Response
    {
        $today = Carbon::today();
        [$monthStart, $monthEnd] = $this->currentMonthRange();
        $monthStartDate = $monthStart->toDateString();
        $monthEndDate = $monthEnd->toDateString();

        $weeklyBadReview = $this->weeklySimple(BadReview::query(), 'created_at');
        $weeklyOos = $this->weeklySimple(Oos::query(), 'created_at');

        $badReviewByBrand = BadReview::query()
            ->whereBetween('tanggal_review', [$monthStartDate, $monthEndDate])
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        $badReviewByCategory = BadReview::query()
            ->whereBetween('tanggal_review', [$monthStartDate, $monthEndDate])
            ->selectRaw('category_review as label, COUNT(*) as total')
            ->groupBy('category_review')
            ->orderByDesc('total')
            ->get();

        $pendingOtBase = fn() => OrderTracking::query()->where('status', 'Pending');

        $pendingOtByBrand = (clone $pendingOtBase())
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        $pendingOtByPlatform = (clone $pendingOtBase())
            ->selectRaw('platform as label, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        $pendingOtByCauseBy = (clone $pendingOtBase())
            ->selectRaw('cause_by as label, COUNT(*) as total')
            ->groupBy('cause_by')
            ->orderByDesc('total')
            ->get();

        $pendingOtByAutoTrack = (clone $pendingOtBase())
            ->selectRaw('automation_track as label, COUNT(*) as total')
            ->groupBy('automation_track')
            ->orderByDesc('total')
            ->get();

        $pendingOtByDataSource = (clone $pendingOtBase())
            ->selectRaw('data_source as label, COUNT(*) as total')
            ->groupBy('data_source')
            ->orderByDesc('total')
            ->get();

        $pendingOtByOrderDate = (clone $pendingOtBase())
            ->whereNotNull('tanggal_order')
            ->selectRaw('tanggal_order as label, COUNT(*) as total')
            ->groupBy('tanggal_order')
            ->orderByDesc('tanggal_order')
            ->limit(14)
            ->get();

        $oosNeedingBlast = Oos::query()
            ->whereIn('tanggal_input', [$today->toDateString(), Carbon::yesterday()->toDateString()])
            ->where(fn($query) => $query->whereNull('update_cs')->orWhere('update_cs', '!=', 'Done Blast'))
            ->selectRaw('tanggal_input as label, COUNT(*) as total')
            ->groupBy('tanggal_input')
            ->orderByDesc('tanggal_input')
            ->get();

        $oosNeedingBlastTotal = Oos::query()
            ->whereIn('tanggal_input', [$today->toDateString(), Carbon::yesterday()->toDateString()])
            ->where(fn($query) => $query->whereNull('update_cs')->orWhere('update_cs', '!=', 'Done Blast'))
            ->count();

        $oosByBrand = Oos::query()
            ->whereBetween('tanggal_input', [$monthStartDate, $monthEndDate])
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard/PerformanceMonitoring', [
            'weeklyBadReview' => $weeklyBadReview,
            'weeklyOos' => $weeklyOos,
            'badReviewByBrand' => $badReviewByBrand,
            'badReviewByCategory' => $badReviewByCategory,
            'pendingOtByBrand' => $pendingOtByBrand,
            'pendingOtByPlatform' => $pendingOtByPlatform,
            'pendingOtByCauseBy' => $pendingOtByCauseBy,
            'pendingOtByOrderDate' => $pendingOtByOrderDate,
            'pendingOtByAutoTrack' => $pendingOtByAutoTrack,
            'pendingOtByDataSource' => $pendingOtByDataSource,
            'oosNeedingBlast' => $oosNeedingBlast,
            'oosNeedingBlastTotal' => $oosNeedingBlastTotal,
            'oosByBrand' => $oosByBrand,
        ]);
    }

    public function agentInterface(): Response
    {
        $dates = collect(range(6, 0))
            ->map(fn(int $i) => Carbon::today()->subDays($i)->toDateString())
            ->all();

        $agentComplaintStats = Complaint::query()
            ->whereNotNull('cs_name')
            ->selectRaw(
                'cs_name as agent,
                SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "Whitelist" THEN 1 ELSE 0 END) as whitelist,
                SUM(CASE WHEN status = "Solved" THEN 1 ELSE 0 END) as solved_total'
            )
            ->groupBy('cs_name')
            ->orderBy('cs_name')
            ->get()
            ->keyBy('agent');

        $dailySolvedRaw = Complaint::query()
            ->where('status', 'Solved')
            ->whereNotNull('cs_name')
            ->whereDate('updated_at', '>=', Carbon::today()->subDays(6))
            ->selectRaw('cs_name as agent, DATE(updated_at) as date, COUNT(*) as count')
            ->groupBy('cs_name', DB::raw('DATE(updated_at)'))
            ->get()
            ->groupBy('agent');

        $agentInterface = $agentComplaintStats->map(function ($row) use ($dates, $dailySolvedRaw) {
            $byDate = $dailySolvedRaw->get($row->agent, collect())->keyBy('date');

            return [
                'agent' => $row->agent,
                'pending' => (int) $row->pending,
                'whitelist' => (int) $row->whitelist,
                'solved_total' => (int) $row->solved_total,
                'daily_solved' => array_map(fn($date) => [
                    'date' => $date,
                    'count' => (int) ($byDate->get($date)?->count ?? 0),
                ], $dates),
            ];
        })->values();

        $pendingByAgentPriority = Complaint::query()
            ->where('status', 'Pending')
            ->whereNotNull('cs_name')
            ->whereNotNull('last_step')
            ->join('last_steps', 'complaints.last_step', '=', 'last_steps.name')
            ->selectRaw('complaints.cs_name as agent, last_steps.priority_level, COUNT(*) as total')
            ->groupBy('complaints.cs_name', 'last_steps.priority_level')
            ->orderBy('complaints.cs_name')
            ->orderBy('last_steps.priority_level')
            ->get()
            ->groupBy('agent')
            ->map(fn(Collection $rows, string $agent) => [
                'agent' => $agent,
                'priorities' => $rows->map(fn($row) => [
                    'priority' => $row->priority_level,
                    'total' => (int) $row->total,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Dashboard/AgentInterface', [
            'agentInterface' => $agentInterface,
            'pendingByAgentPriority' => $pendingByAgentPriority,
            'dates' => $dates,
        ]);
    }

    private function currentMonthRange(): array
    {
        $now = Carbon::now();

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }

    private function weeklySimple($baseQuery, string $dateColumn): array
    {
        return collect(range(6, 0))
            ->map(fn(int $i) => Carbon::today()->subDays($i)->toDateString())
            ->map(fn(string $date) => [
                'date' => $date,
                'total' => (clone $baseQuery)->whereDate($dateColumn, $date)->count(),
            ])
            ->values()
            ->all();
    }

    private function pendingGroupBy(string $column): Collection
    {
        return Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw("{$column} as label, COUNT(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->get();
    }

    private function buildAgentDdayStats(Carbon $today): Collection
    {
        $supportsOosAgent = $this->oosSupportsAgentAssignment();

        $complaintDistributed = $this->pluckByAgent(
            Complaint::query()->whereDate('created_at', $today)->whereNotNull('cs_name')
        );
        $badReviewDistributed = $this->pluckByAgent(
            BadReview::query()->whereDate('tanggal_review', $today)->whereNotNull('cs_name')
        );
        $orderTrackingDistributed = $this->pluckByAgent(
            OrderTracking::query()->whereDate('tanggal_input', $today)->whereNotNull('cs_name')
        );
        $oosDistributed = $supportsOosAgent
            ? $this->pluckByAgent(
                Oos::query()->whereDate('tanggal_input', $today)->whereNotNull('cs_name')
            )
            : [];

        $complaintHandled = $this->pluckByAgent(
            Complaint::query()->whereDate('updated_at', $today)->whereNotNull('cs_name')
        );
        $badReviewHandled = $this->pluckByAgent(
            BadReview::query()->whereDate('tanggal_update', $today)->whereNotNull('cs_name')
        );
        $orderTrackingHandled = $this->pluckByAgent(
            OrderTracking::query()->whereDate('tanggal_update', $today)->whereNotNull('cs_name')
        );
        $oosHandled = $supportsOosAgent
            ? $this->pluckByAgent(
                Oos::query()
                    ->whereDate('updated_at', $today)
                    ->whereNotNull('cs_name')
                    ->whereColumn('updated_at', '>', 'created_at')
            )
            : [];

        $complaintSolved = $this->pluckByAgent(
            Complaint::query()->where('status', 'Solved')->whereDate('updated_at', $today)->whereNotNull('cs_name')
        );
        $badReviewSolved = $this->pluckByAgent(
            BadReview::query()->where('status', 'Solved')->whereDate('tanggal_update', $today)->whereNotNull('cs_name')
        );
        $orderTrackingSolved = $this->pluckByAgent(
            OrderTracking::query()->where('status', 'Solved')->whereDate('tanggal_update', $today)->whereNotNull('cs_name')
        );

        $allAgents = collect(array_unique(array_merge(
            array_keys($complaintDistributed),
            array_keys($badReviewDistributed),
            array_keys($orderTrackingDistributed),
            array_keys($oosDistributed),
            array_keys($complaintHandled),
            array_keys($badReviewHandled),
            array_keys($orderTrackingHandled),
            array_keys($oosHandled),
            array_keys($complaintSolved),
            array_keys($badReviewSolved),
            array_keys($orderTrackingSolved),
        )))->sort()->values();

        return $allAgents->map(fn(string $agent) => [
            'agent' => $agent,
            'dist_complaint' => (int) ($complaintDistributed[$agent] ?? 0),
            'dist_bad_review' => (int) ($badReviewDistributed[$agent] ?? 0),
            'dist_ot' => (int) ($orderTrackingDistributed[$agent] ?? 0),
            'dist_oos' => (int) ($oosDistributed[$agent] ?? 0),
            'dist_total' => (int) (($complaintDistributed[$agent] ?? 0) + ($badReviewDistributed[$agent] ?? 0) + ($orderTrackingDistributed[$agent] ?? 0) + ($oosDistributed[$agent] ?? 0)),
            'handled_complaint' => (int) ($complaintHandled[$agent] ?? 0),
            'handled_bad_review' => (int) ($badReviewHandled[$agent] ?? 0),
            'handled_ot' => (int) ($orderTrackingHandled[$agent] ?? 0),
            'handled_oos' => (int) ($oosHandled[$agent] ?? 0),
            'handled_total' => (int) (($complaintHandled[$agent] ?? 0) + ($badReviewHandled[$agent] ?? 0) + ($orderTrackingHandled[$agent] ?? 0) + ($oosHandled[$agent] ?? 0)),
            'solved_complaint' => (int) ($complaintSolved[$agent] ?? 0),
            'solved_bad_review' => (int) ($badReviewSolved[$agent] ?? 0),
            'solved_ot' => (int) ($orderTrackingSolved[$agent] ?? 0),
            'solved_total' => (int) (($complaintSolved[$agent] ?? 0) + ($badReviewSolved[$agent] ?? 0) + ($orderTrackingSolved[$agent] ?? 0)),
        ]);
    }

    private function buildCombinedAgentRecap(Collection $agentDdayStats, Collection $todayProductivity): Collection
    {
        $productivityByAgent = $todayProductivity
            ->groupBy('cs_name')
            ->map(fn(Collection $rows) => [
                'productivity_total' => (int) $rows->sum('total_ticket'),
                'productivity_entries' => $rows->count(),
            ]);

        $allAgents = $agentDdayStats
            ->pluck('agent')
            ->merge($productivityByAgent->keys())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $agentStatsMap = $agentDdayStats->keyBy('agent');

        return $allAgents
            ->map(function (string $agent) use ($agentStatsMap, $productivityByAgent) {
                $stats = $agentStatsMap->get($agent, []);
                $productivity = $productivityByAgent->get($agent, [
                    'productivity_total' => 0,
                    'productivity_entries' => 0,
                ]);

                return [
                    'agent' => $agent,
                    'distributed' => (int) ($stats['dist_total'] ?? 0),
                    'handled' => (int) ($stats['handled_total'] ?? 0),
                    'solved' => (int) ($stats['solved_total'] ?? 0),
                    'productivity_total' => (int) $productivity['productivity_total'],
                    'productivity_entries' => (int) $productivity['productivity_entries'],
                ];
            })
            ->sortByDesc(fn(array $row) => ($row['productivity_total'] * 1000000) + ($row['handled'] * 1000) + $row['distributed'])
            ->values();
    }

    private function pluckByAgent($query): array
    {
        return $query
            ->selectRaw('cs_name as agent, COUNT(*) as cnt')
            ->groupBy('cs_name')
            ->pluck('cnt', 'agent')
            ->toArray();
    }

    private function oosSupportsAgentAssignment(): bool
    {
        return Schema::hasColumn('oos', 'cs_name');
    }
}
