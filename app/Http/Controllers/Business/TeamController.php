<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Actions\ManageTeam;
use App\Domain\Business\Models\BusinessInvitation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();

        return Inertia::render('Business/Team', [
            'members' => $business->members()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $business->owner_id === $u->id ? 'owner' : $u->pivot->role]),
            'invitations' => $business->invitations()->whereNull('accepted_at')->get()->map(fn ($i) => ['id' => $i->id, 'email' => $i->email, 'role' => $i->role, 'expires_at' => $i->expires_at->toIso8601String(), 'expired' => $i->expires_at->isPast()]),
            'canManage' => $business->canManageTeam($request->user()),
        ]);
    }

    public function invite(Request $request, ManageTeam $team): RedirectResponse
    {
        $business = $this->managed($request);
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'role' => ['required', Rule::in(BusinessInvitation::ROLES)]]);
        $team->invite($business, $request->user(), $data['email'], $data['role']);

        return back()->with('success', __('team.invited'));
    }

    public function update(Request $request, User $member, ManageTeam $team): RedirectResponse
    {
        $business = $this->managed($request);
        abort_unless($business->hasMember($member), 404);
        $team->changeRole($business, $member, $request->validate(['role' => ['required', Rule::in(BusinessInvitation::ROLES)]])['role']);

        return back()->with('success', __('app.saved'));
    }

    public function destroy(Request $request, User $member, ManageTeam $team): RedirectResponse
    {
        $business = $this->managed($request);
        abort_unless($business->hasMember($member), 404);
        $team->remove($business, $member);

        return back()->with('success', __('app.deleted'));
    }

    public function revoke(Request $request, BusinessInvitation $invitation): RedirectResponse
    {
        $business = $this->managed($request);
        abort_unless($invitation->business_id === $business->id, 404);
        $invitation->delete();

        return back()->with('success', __('app.deleted'));
    }

    private function managed(Request $request)
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business && $business->canManageTeam($request->user()), 403);

        return $business;
    }
}
