<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    public function edit(Request $request): Response
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => now()->setTimestamp($session->last_activity)->toDateTimeString(),
                'is_current' => $session->id === $currentSessionId,
            ]);

        $loginActivities = LoginActivity::query()
            ->where('user_id', $request->user()->id)
            ->latest('logged_in_at')
            ->limit(12)
            ->get()
            ->map(fn (LoginActivity $activity) => [
                'id' => $activity->id,
                'status' => $activity->status,
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'logged_in_at' => optional($activity->logged_in_at)?->toDateTimeString(),
                'logged_out_at' => optional($activity->logged_out_at)?->toDateTimeString(),
                'failure_reason' => $activity->failure_reason,
            ]);

        return Inertia::render('settings/Security', [
            'sessions' => $sessions,
            'loginActivities' => $loginActivities,
        ]);
    }

    public function destroyOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $revokedSessionIds = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->pluck('id');

        if ($revokedSessionIds->isNotEmpty()) {
            LoginActivity::query()
                ->whereIn('session_id', $revokedSessionIds)
                ->whereNull('logged_out_at')
                ->update([
                    'logged_out_at' => now(),
                ]);
        }

        DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('success', 'Sesi lain berhasil dihentikan.');
    }
}
