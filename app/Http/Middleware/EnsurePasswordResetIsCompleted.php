<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordResetIsCompleted
{
    private const ALLOWED_ROUTES = [
        'password.edit',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user &&
            $user->force_password_reset &&
            ! in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)
        ) {
            return redirect()
                ->route('password.edit')
                ->with('error', 'Untuk keamanan akun, Anda perlu mengganti password sebelum melanjutkan.');
        }

        return $next($request);
    }
}
