<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\BusinessInvitation;
use App\Domain\Identity\AuditLogger;
use App\Domain\Identity\Enums\Role;
use App\Domain\Messaging\Actions\EnsureCaseWorkspace;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Invite colleagues to a business account, accept invitations, change roles and remove members. */
class ManageTeam
{
    public const TTL_DAYS = 7;

    public function __construct(private readonly AuditLogger $audit, private readonly EnsureCaseWorkspace $workspace) {}

    /** Adds or removes the user from every case workspace of the business after a team change. */
    private function syncWorkspaces(Business $business): void
    {
        foreach ($business->cases()->whereHas('conversation')->get() as $case) {
            $this->workspace->handle($case->fresh('business'));
        }
    }

    public function invite(Business $business, User $inviter, string $email, string $role): BusinessInvitation
    {
        $email = strtolower($email);
        if ($business->members()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('team.errors.already_member')]);
        }

        $business->invitations()->where('email', $email)->whereNull('accepted_at')->delete();
        $token = Str::random(48);
        $invitation = $business->invitations()->create([
            'email' => $email, 'role' => $role, 'token_hash' => hash('sha256', $token),
            'invited_by' => $inviter->id, 'expires_at' => now()->addDays(self::TTL_DAYS),
        ]);

        $url = route('invitations.show', ['locale' => app()->getLocale(), 'token' => $token]);
        Notification::route('mail', $email)->notify(new TeamInvitationNotification($business->trade_name, $inviter->name, $url, app()->getLocale()));
        $this->audit->log('team.invited', $business, ['email' => $email, 'role' => $role]);

        return $invitation;
    }

    public function accept(BusinessInvitation $invitation, User $user): Business
    {
        if (! $invitation->isPending() || strtolower($user->email) !== $invitation->email) {
            throw ValidationException::withMessages(['token' => __('team.errors.invalid_invitation')]);
        }

        $business = $invitation->business;
        $business->members()->syncWithoutDetaching([$user->id => ['role' => $invitation->role]]);
        if (! $user->hasRole(Role::Business->value)) {
            $user->assignRole(Role::Business->value);
        }
        $invitation->update(['accepted_at' => now()]);
        $this->syncWorkspaces($business);
        if (session()->isStarted()) {
            session()->put('current_business_id', $business->id);
        }
        $this->audit->log('team.joined', $business, ['user_id' => $user->id], $user->id);

        return $business;
    }

    public function changeRole(Business $business, User $member, string $role): void
    {
        abort_if($member->id === $business->owner_id, 422);
        $business->members()->updateExistingPivot($member->id, ['role' => $role]);
        $this->audit->log('team.role_changed', $business, ['user_id' => $member->id, 'role' => $role]);
    }

    public function remove(Business $business, User $member): void
    {
        abort_if($member->id === $business->owner_id, 422);
        $business->members()->detach($member->id);
        $this->syncWorkspaces($business);
        if ($member->businesses()->doesntExist() && $member->ownedBusinesses()->doesntExist()) {
            $member->removeRole(Role::Business->value);
        }
        $this->audit->log('team.removed', $business, ['user_id' => $member->id]);
    }
}
