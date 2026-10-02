<?php

namespace App\Support;

use App\Models\PrivacySetting;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPrivacySettings
{
    public function privacySettings(): MorphMany
    {
        return $this->morphMany(PrivacySetting::class, 'owner');
    }

    /** @return array<string, string> field => visibility, defaults merged with the owner's choices */
    public function privacyMap(): array
    {
        $stored = $this->relationLoaded('privacySettings')
            ? $this->privacySettings
            : $this->privacySettings()->get();

        return array_merge(static::defaultPrivacy(), $stored->pluck('visibility', 'field')->all());
    }

    /** @param array<string, string> $map */
    public function syncPrivacy(array $map): void
    {
        foreach ($map as $field => $visibility) {
            if (! array_key_exists($field, static::defaultPrivacy()) || ! in_array($visibility, PrivacySetting::LEVELS, true)) {
                continue;
            }
            $this->privacySettings()->updateOrCreate(['field' => $field], ['visibility' => $visibility]);
        }
        $this->unsetRelation('privacySettings');
    }

    /**
     * Returns only the sensitive fields the viewer is allowed to see given their relation to the owner.
     *
     * @param  string  $relation  one of owner|staff|case_team|verified_expert|public
     * @return array<string, mixed>
     */
    public function visibleSensitiveFields(string $relation): array
    {
        $rank = ['public' => 0, 'verified_experts' => 1, 'case_team' => 2, 'private' => 3];
        $viewerRank = match ($relation) {
            'owner', 'staff' => 3,
            'case_team' => 2,
            'verified_expert' => 1,
            default => 0,
        };

        $out = [];
        foreach ($this->privacyMap() as $field => $visibility) {
            if ($rank[$visibility] <= $viewerRank) {
                $out[$field] = $this->getAttribute($field);
            }
        }

        return $out;
    }

    /** @return array<string, string> */
    abstract public static function defaultPrivacy(): array;
}
