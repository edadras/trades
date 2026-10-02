<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Cases\Models\SupportCase;
use App\Jobs\TranscribeCaseVoice;
use App\Models\Consent;
use App\Models\User;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Opens a draft case from text and/or a voice note, with optional attachments. */
class CreateCase
{
    public function __construct(private readonly SecureFileStorage $files, private readonly CaseTimeline $timeline) {}

    /**
     * @param  array<int, UploadedFile>  $attachments
     * @param  array{ai_processing?: bool, share_with_foreign_experts?: bool, anonymized_learning?: bool}  $consent  per-case data-use choices
     * @param  array{actions_taken?: string|null, partner_id?: int|null}  $extra
     */
    public function handle(User $user, Business $business, ?string $description, ?UploadedFile $voice = null, array $attachments = [], ?string $locale = null, array $consent = [], array $extra = []): SupportCase
    {
        $case = DB::transaction(function () use ($user, $business, $description, $voice, $attachments, $locale, $consent, $extra) {
            $partnerId = $extra['partner_id'] ?? $business->partner_id;
            $case = SupportCase::create([
                'business_id' => $business->id,
                'created_by' => $user->id,
                'title' => $description ? Str::limit(trim(strtok($description, "\n") ?: $description), 120) : null,
                'description' => $description,
                'input_mode' => $voice ? ($description ? 'mixed' : 'voice') : 'text',
                'locale' => $locale ?? app()->getLocale(),
                'actions_taken' => $extra['actions_taken'] ?? null,
                'partner_id' => $partnerId,
                'referral_source' => $partnerId ? 'partner' : 'self',
                'data_consent' => [
                    'ai_processing' => (bool) ($consent['ai_processing'] ?? true),
                    'share_with_foreign_experts' => (bool) ($consent['share_with_foreign_experts'] ?? true),
                    'anonymized_learning' => (bool) ($consent['anonymized_learning'] ?? false),
                ],
            ]);
            $this->recordConsents($user, $case);

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

    /** The per-case choices are also written to the consent log for an auditable trail. */
    private function recordConsents(User $user, SupportCase $case): void
    {
        foreach ($case->data_consent as $type => $granted) {
            Consent::create([
                'user_id' => $user->id, 'type' => 'case_'.$type, 'version' => $case->number, 'granted' => $granted,
                'ip_address' => request()?->ip(), 'user_agent' => substr((string) request()?->userAgent(), 0, 500),
            ]);
        }
    }
}
