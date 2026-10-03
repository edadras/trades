<?php

namespace App\Policies;

use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Experts\Models\ExpertDocument;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Messaging\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Decides whether a user may download a private file. */
class FileAccess
{
    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'case' => CaseDocument::class,
        'business' => BusinessDocument::class,
        'expert' => ExpertDocument::class,
        'message' => MessageAttachment::class,
    ];

    public function allows(User $user, Model $file): bool
    {
        return match (true) {
            $file instanceof CaseDocument => $user->can('viewConfidential', $file->case),
            $file instanceof MessageAttachment => ((bool) $file->message?->conversation?->hasMember($user) && (! $file->message->conversation->case || $user->can('view', $file->message->conversation->case)))
                || $user->can(Permission::CasesViewAll->value),
            $file instanceof ExpertDocument => $file->expertProfile?->user_id === $user->id || $user->can(Permission::ExpertsVerify->value),
            $file instanceof BusinessDocument => $this->businessDocument($user, $file),
            default => false,
        };
    }

    private function businessDocument(User $user, BusinessDocument $file): bool
    {
        $business = $file->business;
        if ($business->hasMember($user) || $user->can(Permission::BusinessesView->value)) {
            return true;
        }
        if ($file->visibility === 'case_team') {
            return $business->cases()->whereHas('activeExperts', fn ($q) => $q->where('expert_profiles.user_id', $user->id))->exists();
        }

        return false;
    }
}
