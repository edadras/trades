<?php

namespace App\Policies;

use App\Domain\Cases\Actions\ReopenCase;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

class SupportCasePolicy
{
    /** Decisions that belong to the business itself; even a super admin does not take them on its behalf. */
    private const BUSINESS_ONLY = ['create', 'update', 'decideMatches', 'confirmOutcome', 'rate'];

    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('super_admin') && ! in_array($ability, self::BUSINESS_ONLY, true) ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CasesCreate->value) && (bool) $user->currentBusiness()?->canOpenCases();
    }

    /** Business members, active experts and staff with case access. */
    public function view(User $user, SupportCase $case): bool
    {
        return $this->isBusinessMember($user, $case)
            || $case->hasActiveExpert($user)
            || $user->can(Permission::CasesViewAll->value);
    }

    /** Owner-side edits of the problem itself (only before AI processing starts). */
    public function update(User $user, SupportCase $case): bool
    {
        return $this->isBusinessMember($user, $case) && in_array($case->status, [CaseStatus::Draft, CaseStatus::Waiting], true);
    }

    /** Collaborate in the workspace: messages, tasks, documents, appointments. */
    public function participate(User $user, SupportCase $case): bool
    {
        return ! $case->isClosed() && (
            $this->isBusinessMember($user, $case) || $case->hasActiveExpert($user) || $user->can(Permission::CasesManage->value)
        );
    }

    /**
     * See confidential business data (contacts, registration, documents) — only after an expert accepts,
     * and for an expert abroad on a sensitive case only once legal & compliance cleared the data exchange.
     */
    public function viewConfidential(User $user, SupportCase $case): bool
    {
        if ($this->isBusinessMember($user, $case) || $user->can(Permission::CasesViewAll->value)) {
            return true;
        }
        if (! $case->hasActiveExpert($user)) {
            return false;
        }

        return ! $case->collaborationRequests()->whereIn('status', ['pending', 'rejected'])
            ->whereHas('servicePath', fn ($q) => $q->where('key', 'cross_border_data'))->exists();
    }

    public function confirmOutcome(User $user, SupportCase $case): bool
    {
        return $this->isBusinessManager($user, $case);
    }

    /** Business members within the reopen window, or case managers at any time. */
    public function reopen(User $user, SupportCase $case): bool
    {
        if (! in_array($case->status, [CaseStatus::Resolved, CaseStatus::Closed], true)) {
            return false;
        }
        if ($user->can(Permission::CasesManage->value)) {
            return true;
        }
        $closedAt = $case->closed_at ?? $case->resolved_at;

        return $this->isBusinessManager($user, $case) && (! $closedAt || $closedAt->gt(now()->subDays(ReopenCase::WINDOW_DAYS)));
    }

    public function requestCollaboration(User $user, SupportCase $case): bool
    {
        return $this->participate($user, $case);
    }

    public function leave(User $user, SupportCase $case): bool
    {
        return ! $case->isClosed() && $case->hasActiveExpert($user);
    }

    public function review(User $user, SupportCase $case): bool
    {
        return $user->can(Permission::CasesReview->value);
    }

    public function assign(User $user, SupportCase $case): bool
    {
        return $user->can(Permission::CasesAssign->value);
    }

    public function decideMatches(User $user, SupportCase $case): bool
    {
        return $this->isBusinessManager($user, $case);
    }

    /** Team members with role "member" follow and work on cases; the owner and admins take the decisions. */
    public function recordOutcome(User $user, SupportCase $case): bool
    {
        return ! $case->isClosed() && ($this->isBusinessManager($user, $case) || $case->hasActiveExpert($user) || $user->can(Permission::CasesManage->value));
    }

    /** Remove an expert (staff) or ask for a replacement (business owner/admin). */
    public function releaseExperts(User $user, SupportCase $case): bool
    {
        return ! $case->isClosed() && ($this->isBusinessManager($user, $case) || $user->can(Permission::CasesAssign->value));
    }

    public function close(User $user, SupportCase $case): bool
    {
        return $this->recordOutcome($user, $case);
    }

    public function rate(User $user, SupportCase $case): bool
    {
        return $this->isBusinessMember($user, $case)
            && (in_array($case->status, [CaseStatus::Resolved, CaseStatus::Closed], true) || $case->hasConfirmedOutcome());
    }

    public function viewInternalNotes(User $user, SupportCase $case): bool
    {
        return $user->isStaff() && $user->can(Permission::CasesViewAll->value);
    }

    private function isBusinessMember(User $user, SupportCase $case): bool
    {
        return (bool) $case->business?->hasMember($user);
    }

    private function isBusinessManager(User $user, SupportCase $case): bool
    {
        return in_array($case->business?->roleOf($user), ['owner', 'admin'], true);
    }
}
