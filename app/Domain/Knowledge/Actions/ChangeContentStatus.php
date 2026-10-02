<?php

namespace App\Domain\Knowledge\Actions;

use App\Domain\Identity\AuditLogger;
use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ChangeContentStatus
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(KnowledgeArticle $article, User $user, ContentStatus $status): KnowledgeArticle
    {
        if ($status === ContentStatus::Approved) {
            if (! $user->can('knowledge.approve')) {
                throw ValidationException::withMessages(['status' => __('knowledge.errors.cannot_approve')]);
            }
            if ($article->translations()->count() === 0) {
                throw ValidationException::withMessages(['status' => __('knowledge.errors.no_translation')]);
            }
            $article->forceFill(['approved_by' => $user->id, 'approved_at' => now(), 'published_at' => $article->published_at ?? now()]);
        }
        $article->verification_status = $status;
        $article->save();
        $this->audit->log('knowledge.status.'.$status->value, $article);

        return $article;
    }
}
