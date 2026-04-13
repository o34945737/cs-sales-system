<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        $sessionId = $request->session()->getId();

        if ($user) {
            $user->force_password_reset = (bool) $user->force_password_reset;
            $user->last_login_at = now();
            $user->last_login_ip = $request->ip();
            $user->last_login_user_agent = $request->userAgent();
            $user->save();

            LoginActivity::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'success',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => $sessionId,
                'logged_in_at' => now(),
            ]);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $sessionId = $request->session()->getId();
        $loginActivity = LoginActivity::query()
            ->where('session_id', $sessionId)
            ->whereNull('logged_out_at')
            ->latest('id')
            ->first();

        if (! $loginActivity && $request->user()) {
            $loginActivity = LoginActivity::query()
                ->where('user_id', $request->user()->id)
                ->where('status', 'success')
                ->whereNull('logged_out_at')
                ->latest('id')
                ->first();
        }

        $loginActivity?->update([
            'logged_out_at' => now(),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
