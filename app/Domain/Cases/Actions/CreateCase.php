<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Cases\Models\SupportCase;
use App\Jobs\TranscribeCaseVoice;
use App\Models\User;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Opens a draft case from text and/or a voice note, with optional attachments. */
class CreateCase
{
    public function __construct(private readonly SecureFileStorage $files, private readonly CaseTimeline $timeline) {}

    /** @param array<int, UploadedFile> $attachments */
    public function handle(User $user, Business $business, ?string $description, ?UploadedFile $voice = null, array $attachments = [], ?string $locale = null): SupportCase
    {
        $case = DB::transaction(function () use ($user, $business, $description, $voice, $attachments, $locale) {
            $case = SupportCase::create([
                'business_id' => $business->id,
                'created_by' => $user->id,
                'title' => $description ? Str::limit(trim(strtok($description, "\n") ?: $description), 120) : null,
                'description' => $description,
                'input_mode' => $voice ? ($description ? 'mixed' : 'voice') : 'text',
                'locale' => $locale ?? app()->getLocale(),
            ]);

            if ($voice) {
                $stored = $this->files->store($voice, "cases/{$case->id}/voice");
                $case->update(['voice_path' => $stored['path']]);
            }

            foreach ($attachments as $file) {
                $doc = CaseDocument::create($this->files->store($file, "cases/{$case->id}/documents") + ['case_id' => $case->id, 'uploaded_by' => $user->id]);
                $this->files->scan($doc);
            }

            $this->timeline->record($case, 'case_created', ['input_mode' => $case->input_mode], $user->id);

            return $case;
        });

        if ($case->voice_path) {
            TranscribeCaseVoice::dispatch($case->id);
        }

        return $case->fresh();
    }
}
