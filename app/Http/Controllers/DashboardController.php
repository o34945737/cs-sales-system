<?php

namespace App\Http\Controllers;

use App\Models\BadReview;
use App\Models\Complaint;
use App\Models\Oos;
use App\Models\OrderTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $today = Carbon::today();
        [$monthStart, $monthEnd] = $this->currentMonthRange();

        $pendingComplaintCount = Complaint::query()
            ->where('status', 'Pending')
            ->count();

        $pendingOtCount = OrderTracking::query()
            ->where('status', 'Pending')
            ->count();

        $oosTodayCount = Oos::query()
            ->whereDate('tanggal_input', $today)
            ->count();

        $totalTaskCount = $pendingComplaintCount + $pendingOtCount + $oosTodayCount;

        $agentRecap = Complaint::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->selectRaw('cs_name as agent, COUNT(*) as total, SUM(CASE WHEN status = "Solved" THEN 1 ELSE 0 END) as solved, SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END) as pending')
            ->groupBy('cs_name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($item) => [
                'agent' => $item->agent,
                'total' => (int) $item->total,
                'solved' => (int) $item->solved,
                'pending' => (int) $item->pending,
            ]);

        return Inertia::render('Dashboard/Overview', [
            'pendingComplaintCount' => $pendingComplaintCount,
            'pendingOtCount' => $pendingOtCount,
            'oosTodayCount' => $oosTodayCount,
            'totalTaskCount' => $totalTaskCount,
            'agentRecap' => $agentRecap,
        ]);
    }

    public function complaintAnalytics(): Response
    {
        $weeklyComplaint = collect(range(6, 0))
            ->map(fn (int $i) => Carbon::today()->subDays($i)->toDateString())
            ->map(fn (string $date) => [
                'date' => $date,
                'new' => Complaint::query()->whereDate('created_at', $date)->count(),
                'solved' => Complaint::query()->where('status', 'Solved')->whereDate('updated_at', $date)->count(),
            ])
            ->all();

        $pendingByCauseBy = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('cause_by as label, COUNT(*) as total')
            ->groupBy('cause_by')
            ->orderByDesc('total')
            ->get();

        $pendingByPlatform = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('platform as label, COUNT(*) as total')
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        $pendingByLevel = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('complaint_power as label, COUNT(*) as total')
            ->groupBy('complaint_power')
            ->orderByDesc('total')
            ->get();

        $pendingBySubCase = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('sub_case as label, COUNT(*) as total, 0 as sla_ok, 0 as sla_breach')
            ->groupBy('sub_case')
            ->orderByDesc('total')
            ->get();

        $brandRealTime = Complaint::query()
            ->where('status', 'Pending')
            ->selectRaw('brand as label, COUNT(*) as total, SUM(CASE WHEN complaint_power = "Hard Complaint" THEN 1 ELSE 0 END) as hard, SUM(CASE WHEN complaint_power = "Normal Complaint" THEN 1 ELSE 0 END) as normal')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        $complaintByStatus = Complaint::query()
            ->selectRaw('status as label, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $pendingByLastStep = Complaint::query()
            ->where('status', 'Pending')
            ->whereNotNull('last_step')
            ->selectRaw('last_step as label, COUNT(*) as total')
            ->groupBy('last_step')
            ->orderByDesc('total')
            ->get();

        $totalComplaintCount = Complaint::query()->count();

        return Inertia::render('Dashboard/ComplaintAnalytics', [
            'weeklyComplaint' => $weeklyComplaint,
            'pendingByCauseBy' => $pendingByCauseBy,
            'pendingByPlatform' => $pendingByPlatform,
            'pendingByLevel' => $pendingByLevel,
            'pendingBySubCase' => $pendingBySubCase,
            'brandRealTime' => $brandRealTime,
            'complaintByStatus' => $complaintByStatus,
            'pendingByLastStep' => $pendingByLastStep,
            'totalComplaintCount' => $totalComplaintCount,
        ]);
    }

    public function performanceMonitoring(): Response
    {
        [$monthStart, $monthEnd] = $this->currentMonthRange();

        $weeklyBadReview = $this->weeklySimple(BadReview::query(), 'created_at');
        $weeklyOos = $this->weeklySimple(Oos::query(), 'created_at');

        $badReviewByBrand = BadReview::query()
            ->whereBetween('tanggal_review', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        $oosByBrand = Oos::query()
            ->whereBetween('tanggal_input', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->selectRaw('brand as label, COUNT(*) as total')
            ->groupBy('brand')
            ->orderByDesc('total')
            ->get();

        return Inertia::render('Dashboard/PerformanceMonitoring', [
            'weeklyBadReview' => $weeklyBadReview,
            'weeklyOos' => $weeklyOos,
            'badReviewByBrand' => $badReviewByBrand,
            'oosByBrand' => $oosByBrand,
        ]);
    }

    private function currentMonthRange(): array
    {
        $now = Carbon::now();

        return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
    }

    private function weeklySimple($baseQuery, string $dateCol): array
    {
        return collect(range(6, 0))
            ->map(fn (int $i) => Carbon::today()->subDays($i)->toDateString())
            ->map(fn (string $date) => [
                'date' => $date,
                'total' => (clone $baseQuery)->whereDate($dateCol, $date)->count(),
            ])
            ->values()
            ->all();
    }
}
