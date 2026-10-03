<?php

namespace App\Domain\Compliance\Actions;

use App\Domain\Business\Models\BusinessInvitation;
use App\Domain\Cases\Models\SatisfactionSurvey;
use App\Domain\Compliance\Models\Complaint;
use App\Domain\Compliance\Models\DataRequest;
use App\Domain\Identity\AuditLogger;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Messaging\Models\Message;
use App\Jobs\ExportUserData;
use App\Models\Consent;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Data-subject requests. Exports are produced automatically; deletions are reviewed by legal & compliance
 * because some records (cases, audit trail) must be retained — the account is anonymised instead.
 */
class ProcessDataRequest
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function open(User $user, string $type, ?string $reason = null): DataRequest
    {
        $request = DataRequest::create(['user_id' => $user->id, 'type' => $type, 'reason' => $reason]);
        $this->audit->log('privacy.request.'.$type, $user, [], $user->id);

        if ($type === 'export') {
            ExportUserData::dispatch($request->id);
        } else {
            foreach (User::permission(Permission::DataRequestsManage->value)->get() as $staff) {
                $staff->notify(new PlatformNotification('data_request_received', ['type' => $type], route('admin.compliance.index', ['locale' => $staff->locale ?: 'fa', 'tab' => 'data'])));
            }
        }

        return $request;
    }

    public function export(DataRequest $request): DataRequest
    {
        $user = $request->user;
        $payload = [
            'generated_at' => now()->toIso8601String(),
            'account' => $user->only(['id', 'name', 'email', 'locale', 'timezone', 'created_at', 'last_login_at']) + ['phone' => $user->phone],
            'roles' => $user->getRoleNames(),
            'consents' => Consent::where('user_id', $user->id)->get(['type', 'version', 'granted', 'created_at']),
            'businesses' => $user->businesses()->get()->map(fn ($b) => $b->only(['trade_name', 'legal_name', 'industry', 'size', 'country', 'province', 'city', 'website']) + [
                'contact_email' => $b->contact_email, 'contact_phone' => $b->contact_phone,
                'cases' => $b->cases()->get()->map(fn ($c) => [
                    'number' => $c->number, 'status' => $c->status->value, 'description' => $c->description, 'actions_taken' => $c->actions_taken,
                    'answers' => $c->answers->map->only(['question', 'answer']), 'created_at' => $c->created_at,
                    'data_consent' => $c->data_consent,
                    'outcomes' => $c->outcomes()->get()->map(fn ($o) => ['outcome' => $o->outcome->value, 'reason' => $o->reason, 'confirmation' => $o->confirmation_status, 'dispute_reason' => $o->dispute_reason, 'at' => $o->created_at]),
                ]),
                'team_role' => $b->roleOf($user),
            ]),
            'expert_profile' => $user->expertProfile?->only(['headline', 'bio', 'country', 'city', 'years_experience', 'industries', 'certifications']),
            'satisfaction_surveys' => SatisfactionSurvey::where('user_id', $user->id)->get(['case_id', 'rating', 'comment', 'dissatisfaction_reason', 'created_at']),
            'complaints' => Complaint::where('user_id', $user->id)->get()->map(fn ($c) => ['category' => $c->category, 'subject' => $c->subject, 'body' => $c->body, 'status' => $c->status, 'resolution' => $c->resolution, 'at' => $c->created_at]),
            'data_requests' => DataRequest::where('user_id', $user->id)->get(['type', 'status', 'reason', 'created_at']),
            'messages' => Message::where('user_id', $user->id)->latest()->limit(5000)->get()->map(fn ($m) => ['conversation' => $m->conversation_id, 'body' => $m->body, 'at' => $m->created_at]),
        ];

        $path = 'exports/'.$user->id.'/'.Str::uuid().'.json';
        Storage::disk(config('platform.uploads.disk'))->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $request->update(['status' => 'completed', 'file_path' => $path, 'completed_at' => now()]);
        $user->notify(new PlatformNotification('data_request_ready', ['type' => 'export'], route('settings.privacy', ['locale' => $user->locale ?: 'fa'])));

        return $request;
    }

    public function decideDeletion(DataRequest $request, User $staff, bool $approve, ?string $resolution): DataRequest
    {
        if ($approve) {
            $this->anonymize($request->user);
        }
        $request->update(['status' => $approve ? 'completed' : 'rejected', 'handled_by' => $staff->id, 'resolution' => $resolution, 'completed_at' => now()]);
        $this->audit->log('privacy.delete.'.($approve ? 'approved' : 'rejected'), $request->user, ['resolution' => $resolution]);
        if (! $approve) {
            $request->user->notify(new PlatformNotification('data_request_decided', ['type' => 'delete'], route('settings.privacy', ['locale' => $request->user->locale ?: 'fa'])));
        }

        return $request;
    }

    /** Removes personal identifiers while keeping case records and the audit trail intact. */
    public function anonymize(User $user): void
    {
        $originalEmail = strtolower($user->email);
        DB::transaction(function () use ($user, $originalEmail) {
            $user->forceFill([
                'name' => __('privacy.deleted_user'),
                'email' => 'deleted-'.$user->id.'@deleted.invalid',
                'phone' => null,
                'password' => Hash::make(Str::random(40)),
                'avatar_path' => null,
                'status' => 'suspended',
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'remember_token' => null,
            ])->save();
            $user->tokens()->delete();
            // Free text written by the user may identify them; keep the record for the audit trail but drop the text.
            Complaint::where('user_id', $user->id)->each(fn (Complaint $c) => $c->update(['body' => __('privacy.deleted_user'), 'subject' => '—']));
            BusinessInvitation::where('email', $originalEmail)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->expertProfile?->update(['bio' => null, 'linkedin_url' => null, 'is_available' => false, 'certifications' => null]);
            $user->delete();
        });
    }
}
