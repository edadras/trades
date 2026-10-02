<?php

namespace App\Domain\Analytics\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kpi extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'description'];

    protected $fillable = ['key', 'name', 'description', 'metric', 'unit', 'comparator', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class)->latest('id');
    }

    /** The target in force today (dated targets win over open-ended ones). */
    public function currentTarget(): HasOne
    {
        return $this->hasOne(KpiTarget::class)->ofMany(['id' => 'max'], function ($q) {
            $today = now()->toDateString();
            $q->where(fn ($w) => $w->whereNull('period_start')->orWhere('period_start', '<=', $today))
                ->where(fn ($w) => $w->whereNull('period_end')->orWhere('period_end', '>=', $today));
        });
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(KpiSnapshot::class)->orderBy('captured_on');
    }

    public function isAchieved(?float $value, ?float $target): bool
    {
        if (is_null($value) || is_null($target)) {
            return false;
        }

        return $this->comparator === '<=' ? $value <= $target : $value >= $target;
    }
}
