<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Actions\ManageTeam;
use App\Domain\Business\Models\BusinessInvitation;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Landing page of a team invitation link: sign in or register, then join the business. */
class InvitationController extends Controller
{
    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = BusinessInvitation::findByToken($token);
        abort_unless($invitation, 404);

        if ($request->user() && $invitation->business->hasMember($request->user())) {
            $request->session()->put('current_business_id', $invitation->business_id);

            return redirect()->route('dashboard');
        }
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return Inertia::render('Business/Invitation', [
            'token' => $token,
            'invitation' => [
                'business' => $invitation->business->trade_name,
                'inviter' => $invitation->inviter?->name,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'valid' => $invitation->isPending(),
            ],
            'emailMatches' => $request->user() ? strtolower($request->user()->email) === $invitation->email : null,
        ]);
    }

    public function accept(Request $request, string $token, ManageTeam $team): RedirectResponse
    {
        $invitation = BusinessInvitation::findByToken($token);
        abort_unless($invitation, 404);
        $team->accept($invitation, $request->user());

        return redirect()->route('dashboard')->with('success', __('team.joined'));
    }
}
