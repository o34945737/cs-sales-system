<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    private const SUPER_ADMIN_ROLE = 'Super Admin';

    public function index(Request $request): Response
    {
        $baseQuery = User::query()->with('roles');

        $filteredQuery = (clone $baseQuery)
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->input('search'));

                $query->where(function ($userQuery) use ($search) {
                    $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role') && $request->input('role') !== 'All', function ($query) use ($request) {
                $query->role($request->input('role'));
            })
            ->when($request->filled('status') && $request->input('status') !== 'All', function ($query) use ($request) {
                $query->where('is_active', $request->input('status') === 'Active');
            })
            ->orderBy('name');

        $users = (clone $filteredQuery)
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user) => $this->transformUser($user));

        $recentActivities = AdminActivityLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AdminActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'target_label' => $log->target_label,
                'actor_name' => $log->actor?->name ?? 'System',
                'created_at' => optional($log->created_at)?->toDateTimeString(),
                'metadata' => $log->metadata ?? [],
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->pluck('name')->values(),
            'filters' => [
                'search' => $request->input('search'),
                'role' => $request->input('role', 'All'),
                'status' => $request->input('status', 'All'),
            ],
            'metrics' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'inactive' => (clone $baseQuery)->where('is_active', false)->count(),
                'super_admin' => User::role(self::SUPER_ADMIN_ROLE)->count(),
            ],
            'recentActivities' => $recentActivities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['required', 'boolean'],
            'force_password_reset' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'],
                'force_password_reset' => $data['force_password_reset'],
            ]);

            $user->syncRoles([$data['role']]);
            $this->logActivity(
                auth()->user(),
                'user.created',
                $user,
                [
                    'role' => $data['role'],
                    'is_active' => $data['is_active'],
                    'force_password_reset' => $data['force_password_reset'],
                ]
            );
        });

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
            'is_active' => ['required', 'boolean'],
            'force_password_reset' => ['required', 'boolean'],
        ]);

        if ($request->user()->is($user) && ! $data['is_active']) {
            return back()->withErrors([
                'is_active' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.',
            ]);
        }

        if ($request->user()->is($user) && $data['role'] !== self::SUPER_ADMIN_ROLE) {
            return back()->withErrors([
                'role' => 'Anda tidak bisa melepas role Super Admin dari akun Anda sendiri.',
            ]);
        }

        $isCurrentSuperAdmin = $user->hasRole(self::SUPER_ADMIN_ROLE);
        $remainingSuperAdmins = $this->remainingSuperAdmins($user);
        $remainingActiveSuperAdmins = $this->remainingActiveSuperAdmins($user);

        if ($isCurrentSuperAdmin && $data['role'] !== self::SUPER_ADMIN_ROLE && $remainingSuperAdmins === 0) {
            return back()->withErrors([
                'role' => 'Minimal harus ada satu user dengan role Super Admin.',
            ]);
        }

        if ($isCurrentSuperAdmin && ! $data['is_active'] && $remainingActiveSuperAdmins === 0) {
            return back()->withErrors([
                'is_active' => 'Minimal harus ada satu Super Admin yang aktif.',
            ]);
        }

        DB::transaction(function () use ($user, $data, $request): void {
            $before = $this->transformUser($user);

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
                'force_password_reset' => $data['force_password_reset'],
            ]);

            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles([$data['role']]);

            $this->logActivity(
                $request->user(),
                'user.updated',
                $user,
                [
                    'before' => $before,
                    'after' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $data['role'],
                        'is_active' => $user->is_active,
                        'force_password_reset' => $user->force_password_reset,
                    ],
                ]
            );
        });

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors([
                'delete' => 'Anda tidak bisa menghapus akun Anda sendiri.',
            ]);
        }

        if ($user->hasRole(self::SUPER_ADMIN_ROLE) && $this->remainingSuperAdmins($user) === 0) {
            return back()->withErrors([
                'delete' => 'Minimal harus ada satu user dengan role Super Admin.',
            ]);
        }

        if ($user->hasRole(self::SUPER_ADMIN_ROLE) && $user->is_active && $this->remainingActiveSuperAdmins($user) === 0) {
            return back()->withErrors([
                'delete' => 'Minimal harus ada satu Super Admin yang aktif.',
            ]);
        }

        $this->logActivity(
            $request->user(),
            'user.deleted',
            $user,
            [
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
                'is_active' => (bool) $user->is_active,
            ]
        );

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'force_password_reset' => (bool) $user->force_password_reset,
            'roles' => $user->getRoleNames()->values()->all(),
            'created_at' => optional($user->created_at)?->toDateTimeString(),
        ];
    }

    private function remainingSuperAdmins(User $excludedUser): int
    {
        return User::role(self::SUPER_ADMIN_ROLE)
            ->whereKeyNot($excludedUser->id)
            ->count();
    }

    private function remainingActiveSuperAdmins(User $excludedUser): int
    {
        return User::role(self::SUPER_ADMIN_ROLE)
            ->whereKeyNot($excludedUser->id)
            ->where('is_active', true)
            ->count();
    }

    private function logActivity(?User $actor, string $action, User $target, array $metadata = []): void
    {
        AdminActivityLog::create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_type' => User::class,
            'target_id' => $target->id,
            'target_label' => $target->name,
            'metadata' => $metadata,
        ]);
    }
}
