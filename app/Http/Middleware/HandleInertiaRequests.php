<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $user = $request->user();

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'is_active' => (bool) $user->is_active,
                    'force_password_reset' => (bool) $user->force_password_reset,
                    'last_login_at' => $user->last_login_at,
                    'last_login_ip' => $user->last_login_ip,
                    'last_login_user_agent' => $user->last_login_user_agent,
                    'roles' => $user->getRoleNames()->values()->all(),
                ] : null,
                'can' => [
                    'view_brands' => $user?->can('view brands') ?? false,
                    'create_brands' => $user?->can('create brands') ?? false,
                    'update_brands' => $user?->can('update brands') ?? false,
                    'delete_brands' => $user?->can('delete brands') ?? false,
                    'view_platforms' => $user?->can('view platforms') ?? false,
                    'create_platforms' => $user?->can('create platforms') ?? false,
                    'update_platforms' => $user?->can('update platforms') ?? false,
                    'delete_platforms' => $user?->can('delete platforms') ?? false,
                    'view_logistics' => $user?->can('view logistics') ?? false,
                    'create_logistics' => $user?->can('create logistics') ?? false,
                    'update_logistics' => $user?->can('update logistics') ?? false,
                    'delete_logistics' => $user?->can('delete logistics') ?? false,
                    'view_sub_cases' => $user?->can('view sub cases') ?? false,
                    'create_sub_cases' => $user?->can('create sub cases') ?? false,
                    'update_sub_cases' => $user?->can('update sub cases') ?? false,
                    'delete_sub_cases' => $user?->can('delete sub cases') ?? false,
                    'view_last_steps' => $user?->can('view last steps') ?? false,
                    'create_last_steps' => $user?->can('create last steps') ?? false,
                    'update_last_steps' => $user?->can('update last steps') ?? false,
                    'delete_last_steps' => $user?->can('delete last steps') ?? false,
                    'view_users' => $user?->can('view users') ?? false,
                    'create_users' => $user?->can('create users') ?? false,
                    'update_users' => $user?->can('update users') ?? false,
                    'delete_users' => $user?->can('delete users') ?? false,
                    'view_dashboard' => $user?->can('view dashboard') ?? false,
                    'access_complaints' => $user?->can('access complaints') ?? false,
                    'access_bad_reviews' => $user?->can('access bad reviews') ?? false,
                    'access_order_trackings' => $user?->can('access order trackings') ?? false,
                    'access_oos' => $user?->can('access oos') ?? false,
                ],
            ],
            'features' => [
                'public_registration' => false,
            ],
        ]);
    }
}
