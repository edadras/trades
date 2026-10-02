<?php

namespace App\Domain\Cases;

use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;

class CaseNotifier
{
    /** @return Collection<int, User> */
    public function participants(SupportCase $case, bool $includeExperts = true): Collection
    {
        $case->loadMissing(['business.owner', 'business.members', 'caseManager']);
        $users = collect([$case->business?->owner])->merge($case->business?->members ?? [])->push($case->caseManager);
        if ($includeExperts) {
            $users = $users->merge($case->activeExperts()->with('user')->get()->pluck('user'));
        }

        return $users->filter()->unique('id')->values();
    }

    /** @param array<string, mixed> $params */
    public function notifyParticipants(SupportCase $case, string $event, array $params = [], ?int $exceptUserId = null, bool $includeExperts = true): void
    {
        $exceptUserId ??= auth()->id();
        foreach ($this->participants($case, $includeExperts) as $user) {
            if ($user->id !== $exceptUserId) {
                $user->notify(new PlatformNotification($event, $params + ['case' => $case->number], $this->caseUrlFor($user, $case)));
            }
        }
    }

    public function notifyUser(User $user, SupportCase $case, string $event, array $params = [], ?string $url = null): void
    {
        $user->notify(new PlatformNotification($event, $params + ['case' => $case->number], $url ?? $this->caseUrlFor($user, $case)));
    }

    public function caseUrlFor(User $user, SupportCase $case): string
    {
        $locale = $user->locale ?: config('app.locale');

        return match (true) {
            $user->isStaff() => route('review.cases.show', ['locale' => $locale, 'case' => $case->number]),
            $user->expertProfile !== null && ! $case->business?->hasMember($user) => route('expert.cases.show', ['locale' => $locale, 'case' => $case->number]),
            default => route('cases.show', ['locale' => $locale, 'case' => $case->number]),
        };
    }
}
