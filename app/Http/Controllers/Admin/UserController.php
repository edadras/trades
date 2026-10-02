<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\AuditLogger;
use App\Domain\Identity\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:80'], 'role' => ['nullable', Rule::in(array_column(Role::cases(), 'value'))]]);
        $users = User::with('roles')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('name', 'like', "%{$t}%")->orWhere('email', 'like', "%{$t}%")))
            ->when($filters['role'] ?? null, fn ($q, $r) => $q->role($r))
            ->latest()->paginate(25)->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'status' => $u->status, 'roles' => $u->getRoleNames(),
                'two_factor' => $u->hasTwoFactorEnabled(), 'last_login_at' => $u->last_login_at?->toIso8601String(), 'created_at' => $u->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users', ['users' => $users, 'filters' => $filters, 'roles' => array_column(Role::cases(), 'value')]);
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'], 'roles.*' => [Rule::in(array_column(Role::cases(), 'value'))],
            'status' => ['required', 'in:active,suspended'],
        ]);
        $actor = $request->user();
        $roles = $data['roles'] ?? [];
        // Only super admins may grant or remove admin-level roles.
        $privileged = [Role::Admin->value, Role::SuperAdmin->value];
        $before = $user->getRoleNames()->all();
        $changedPrivileged = array_intersect($privileged, array_merge(array_diff($roles, $before), array_diff($before, $roles)));
        abort_if($changedPrivileged && ! $actor->hasRole(Role::SuperAdmin->value), 403);
        abort_if($user->id === $actor->id && $data['status'] !== 'active', 422);

        $user->syncRoles($roles);
        $user->update(['status' => $data['status']]);
        $audit->log('user.roles_updated', $user, ['before' => $before, 'after' => $roles, 'status' => $data['status']]);

        return back()->with('success', __('app.saved'));
    }
}
