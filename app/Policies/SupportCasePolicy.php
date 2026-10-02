<?php

namespace App\Policies;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;

class SupportCasePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('super_admin') ? true : null;
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CasesCreate->value) && $user->currentBusiness()?->isOnboarded();
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

    /** See confidential business data (contacts, registration) — only after an expert accepts. */
    public function viewConfidential(User $user, SupportCase $case): bool
    {
        return $this->isBusinessMember($user, $case) || $case->hasActiveExpert($user) || $user->can(Permission::CasesViewAll->value);
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
        return $this->isBusinessMember($user, $case);
    }

    public function recordOutcome(User $user, SupportCase $case): bool
    {
        return ! $case->isClosed() && ($this->isBusinessMember($user, $case) || $case->hasActiveExpert($user) || $user->can(Permission::CasesManage->value));
    }

    public function close(User $user, SupportCase $case): bool
    {
        return $this->recordOutcome($user, $case);
    }

    public function rate(User $user, SupportCase $case): bool
    {
        return $this->isBusinessMember($user, $case) && in_array($case->status, [CaseStatus::Resolved, CaseStatus::Closed], true);
    }

    public function viewInternalNotes(User $user, SupportCase $case): bool
    {
        return $user->isStaff() && $user->can(Permission::CasesViewAll->value);
    }

    private function isBusinessMember(User $user, SupportCase $case): bool
    {
        return (bool) $case->business?->hasMember($user);
    }
}
