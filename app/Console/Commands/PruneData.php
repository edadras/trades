<?php

namespace App\Console\Commands;

use App\Domain\AI\Models\AiMessage;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\AuditLogger;
use App\Models\AuditLog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('platform:prune-data {--dry-run}')]
#[Description('Apply the data-retention policy (files of long-closed cases, old AI transcripts, audit logs, soft-deleted rows)')]
class PruneData extends Command
{
    public function handle(AuditLogger $audit): int
    {
        $r = config('platform.retention');
        $dry = (bool) $this->option('dry-run');

        $docs = CaseDocument::withTrashed()->whereHas('case', fn ($q) => $q->withTrashed()->where('status', CaseStatus::Closed->value)
            ->where('closed_at', '<', now()->subDays($r['closed_case_files_days'])))->get();
        $softDocs = CaseDocument::onlyTrashed()->where('deleted_at', '<', now()->subDays($r['soft_deleted_days']))->get();
        $files = $docs->merge($softDocs)->unique('id');

        $aiMessages = AiMessage::where('created_at', '<', now()->subDays($r['ai_messages_days']));
        $audits = AuditLog::where('created_at', '<', now()->subDays($r['audit_log_days']));
        $cases = SupportCase::onlyTrashed()->where('deleted_at', '<', now()->subDays($r['soft_deleted_days']));

        $this->table(['Item', 'Count'], [
            ['Case files', $files->count()], ['AI messages', $aiMessages->count()], ['Audit logs', $audits->count()], ['Soft-deleted cases', $cases->count()],
        ]);

        if ($dry) {
            return self::SUCCESS;
        }

        foreach ($files as $doc) {
            Storage::disk($doc->disk)->delete($doc->path);
            $doc->forceDelete();
        }
        $aiMessages->delete();
        $audits->delete();
        $cases->each(fn ($c) => $c->forceDelete());
        $audit->log('retention.pruned', null, ['files' => $files->count()], null);

        return self::SUCCESS;
    }
}
