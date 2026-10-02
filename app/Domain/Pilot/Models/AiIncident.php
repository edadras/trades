<?php

namespace App\Domain\Pilot\Models;

use Illuminate\Database\Eloquent\Model;

/** An AI provider failure that forced a fallback; counted in the weekly error report. */
class AiIncident extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['provider', 'operation', 'message', 'case_id'];

    public static function record(string $provider, string $operation, string $message, ?int $caseId = null): void
    {
        static::create(['provider' => $provider, 'operation' => $operation, 'message' => mb_substr($message, 0, 1000), 'case_id' => $caseId]);
    }
}
