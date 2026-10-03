<?php

namespace App\Jobs;

use App\Domain\AI\AIManager;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\SupportCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class TranscribeCaseVoice implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $caseId)
    {
        $this->onQueue('ai');
    }

    public function handle(AIManager $ai, CaseTimeline $timeline): void
    {
        $case = SupportCase::find($this->caseId);
        // Without consent to AI processing the recording never leaves the platform; reviewers listen to it instead.
        if (! $case?->voice_path || ! $case->consents('ai_processing')) {
            return;
        }

        $disk = Storage::disk(config('platform.uploads.disk'));
        $local = tempnam(sys_get_temp_dir(), 'voice').'.'.pathinfo($case->voice_path, PATHINFO_EXTENSION);
        file_put_contents($local, $disk->get($case->voice_path));

        try {
            $text = $ai->provider()->transcribe($local, $case->locale);
        } finally {
            @unlink($local);
        }

        if ($text) {
            $case->update(['voice_transcript' => $text, 'title' => $case->title ?: str($text)->limit(120)->toString()]);
            $timeline->record($case, 'voice_transcribed', [], null);
        }
    }
}
