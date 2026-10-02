<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\UploadedFile;

class UploadCaseDocument
{
    public function __construct(private readonly SecureFileStorage $files, private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    public function handle(SupportCase $case, User $user, UploadedFile $file, ?string $title = null): CaseDocument
    {
        $doc = CaseDocument::create($this->files->store($file, "cases/{$case->id}/documents") + [
            'case_id' => $case->id,
            'uploaded_by' => $user->id,
            'title' => $title,
        ]);
        $this->files->scan($doc);
        $this->timeline->record($case, 'document_uploaded', ['name' => $doc->title ?? $doc->original_name], $user->id);
        $this->notifier->notifyParticipants($case, 'case_updated', ['status' => 'document_uploaded']);

        return $doc;
    }
}
