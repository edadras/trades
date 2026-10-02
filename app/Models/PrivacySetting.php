<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Visibility of a single profile field.
 * private: owner + platform staff with need-to-know; case_team: experts actively working a case;
 * verified_experts: any verified supporter; public: everyone.
 */
class PrivacySetting extends Model
{
    public const LEVELS = ['private', 'case_team', 'verified_experts', 'public'];

    protected $fillable = ['owner_type', 'owner_id', 'field', 'visibility'];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
