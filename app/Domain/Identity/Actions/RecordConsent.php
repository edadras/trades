<?php

namespace App\Domain\Identity\Actions;

use App\Models\Consent;
use App\Models\User;

class RecordConsent
{
    public const VERSION = '2026-10';

    public function handle(User $user, string $type, bool $granted, ?string $version = null): Consent
    {
        return Consent::create([
            'user_id' => $user->id,
            'type' => $type,
            'version' => $version ?? self::VERSION,
            'granted' => $granted,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
        ]);
    }
}
