<?php

namespace App\Domain\Pilot\Models;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\CaseCategory;
use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The pilot's scope and first-month decisions: target audience (regions, value chains, sizes),
 * priority problems, success definition, support model and partner coordination.
 */
class PilotProgram extends Model
{
    use HasTranslations;

    public const GATES = [
        'scope' => 6,
        'launch' => 16,
        'scale' => 24,
    ];

    protected array $translatable = ['name', 'eligibility_notes', 'success_definition', 'support_model_policy', 'partner_coordination'];

    protected $fillable = [
        'name', 'status', 'starts_on', 'ends_on', 'regions', 'value_chains', 'industries', 'business_sizes', 'max_groups', 'priority_category_ids',
        'eligibility_notes', 'success_definition', 'support_model_policy', 'partner_coordination', 'max_businesses',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'regions' => 'array',
            'value_chains' => 'array',
            'industries' => 'array',
            'business_sizes' => 'array',
            'priority_category_ids' => 'array',
        ];
    }

    public static function active(): ?self
    {
        return static::query()->where('status', 'active')->latest('id')->first();
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function gates(): HasMany
    {
        return $this->hasMany(DecisionGate::class)->orderBy('week');
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(PilotReport::class)->latest('week_start');
    }

    /** Industries covered by the pilot: the selected value chains plus any explicitly added industries. */
    public function coveredIndustries(): array
    {
        $chains = config('platform.value_chains');

        return collect($this->value_chains ?? [])->flatMap(fn ($chain) => $chains[$chain] ?? [])
            ->merge($this->industries ?? [])->unique()->values()->all();
    }

    /** @return array<int, CaseCategory> */
    public function priorityCategories()
    {
        return CaseCategory::whereIn('id', $this->priority_category_ids ?? [])->get();
    }

    /** Creates the three decision gates (end of weeks 6, 16 and 24) if they do not exist yet. */
    public function ensureGates(): void
    {
        foreach (self::GATES as $key => $week) {
            $this->gates()->firstOrCreate(['key' => $key], [
                'week' => $week,
                'due_on' => $this->starts_on?->copy()->addWeeks($week)->toDateString(),
                'criteria' => collect(DecisionGate::CRITERIA[$key])->mapWithKeys(fn ($c) => [$c => false])->all(),
            ]);
        }
    }

    /** Week number of the programme today (1-based), or null before start. */
    public function currentWeek(): ?int
    {
        if (! $this->starts_on || $this->starts_on->isFuture()) {
            return null;
        }

        return (int) floor($this->starts_on->diffInDays(now()) / 7) + 1;
    }
}
