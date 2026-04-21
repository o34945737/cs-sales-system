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
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
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
                    'view_complaint_sources' => $user?->can('view complaint sources') ?? false,
                    'create_complaint_sources' => $user?->can('create complaint sources') ?? false,
                    'update_complaint_sources' => $user?->can('update complaint sources') ?? false,
                    'delete_complaint_sources' => $user?->can('delete complaint sources') ?? false,
                    'view_complaint_powers' => $user?->can('view complaint powers') ?? false,
                    'create_complaint_powers' => $user?->can('create complaint powers') ?? false,
                    'update_complaint_powers' => $user?->can('update complaint powers') ?? false,
                    'delete_complaint_powers' => $user?->can('delete complaint powers') ?? false,
                    'view_complaint_step_statuses' => $user?->can('view complaint step statuses') ?? false,
                    'create_complaint_step_statuses' => $user?->can('create complaint step statuses') ?? false,
                    'update_complaint_step_statuses' => $user?->can('update complaint step statuses') ?? false,
                    'delete_complaint_step_statuses' => $user?->can('delete complaint step statuses') ?? false,
                    'view_sku_codes' => $user?->can('view sku codes') ?? false,
                    'create_sku_codes' => $user?->can('create sku codes') ?? false,
                    'update_sku_codes' => $user?->can('update sku codes') ?? false,
                    'delete_sku_codes' => $user?->can('delete sku codes') ?? false,
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
                    'view_reason_whitelists' => $user?->can('view reason whitelists') ?? false,
                    'create_reason_whitelists' => $user?->can('create reason whitelists') ?? false,
                    'update_reason_whitelists' => $user?->can('update reason whitelists') ?? false,
                    'delete_reason_whitelists' => $user?->can('delete reason whitelists') ?? false,
                    'view_reason_late_responses' => $user?->can('view reason late responses') ?? false,
                    'create_reason_late_responses' => $user?->can('create reason late responses') ?? false,
                    'update_reason_late_responses' => $user?->can('update reason late responses') ?? false,
                    'delete_reason_late_responses' => $user?->can('delete reason late responses') ?? false,
                    'view_order_tracking_data_sources' => $user?->can('view order tracking data sources') ?? false,
                    'create_order_tracking_data_sources' => $user?->can('create order tracking data sources') ?? false,
                    'update_order_tracking_data_sources' => $user?->can('update order tracking data sources') ?? false,
                    'delete_order_tracking_data_sources' => $user?->can('delete order tracking data sources') ?? false,
                    'view_order_tracking_erp_statuses' => $user?->can('view order tracking erp statuses') ?? false,
                    'create_order_tracking_erp_statuses' => $user?->can('create order tracking erp statuses') ?? false,
                    'update_order_tracking_erp_statuses' => $user?->can('update order tracking erp statuses') ?? false,
                    'delete_order_tracking_erp_statuses' => $user?->can('delete order tracking erp statuses') ?? false,
                    'view_order_tracking_rgo_entries' => $user?->can('view order tracking rgo entries') ?? false,
                    'create_order_tracking_rgo_entries' => $user?->can('create order tracking rgo entries') ?? false,
                    'update_order_tracking_rgo_entries' => $user?->can('update order tracking rgo entries') ?? false,
                    'delete_order_tracking_rgo_entries' => $user?->can('delete order tracking rgo entries') ?? false,
                    'view_jet_track_entries' => $user?->can('view jet track entries') ?? false,
                    'create_jet_track_entries' => $user?->can('create jet track entries') ?? false,
                    'update_jet_track_entries' => $user?->can('update jet track entries') ?? false,
                    'delete_jet_track_entries' => $user?->can('delete jet track entries') ?? false,
                    'view_oos_reasons' => $user?->can('view oos reasons') ?? false,
                    'create_oos_reasons' => $user?->can('create oos reasons') ?? false,
                    'update_oos_reasons' => $user?->can('update oos reasons') ?? false,
                    'delete_oos_reasons' => $user?->can('delete oos reasons') ?? false,
                    'view_oos_solutions' => $user?->can('view oos solutions') ?? false,
                    'create_oos_solutions' => $user?->can('create oos solutions') ?? false,
                    'update_oos_solutions' => $user?->can('update oos solutions') ?? false,
                    'delete_oos_solutions' => $user?->can('delete oos solutions') ?? false,
                    'view_cause_bys' => $user?->can('view cause bys') ?? false,
                    'create_cause_bys' => $user?->can('create cause bys') ?? false,
                    'update_cause_bys' => $user?->can('update cause bys') ?? false,
                    'delete_cause_bys' => $user?->can('delete cause bys') ?? false,
                    'view_part_of_bads' => $user?->can('view part of bads') ?? false,
                    'create_part_of_bads' => $user?->can('create part of bads') ?? false,
                    'update_part_of_bads' => $user?->can('update part of bads') ?? false,
                    'delete_part_of_bads' => $user?->can('delete part of bads') ?? false,
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
