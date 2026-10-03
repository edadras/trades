<?php

namespace App\Domain\Pilot\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Pilot\Models\PilotProgram;

/**
 * Checks a business against the active pilot's entry conditions (region, value chain / industry,
 * size, capacity). Outside scope → ineligible; over capacity → waitlisted. Staff can override.
 */
class EvaluateEligibility
{
    public function handle(Business $business): Business
    {
        $program = PilotProgram::active();
        if (! $program) {
            $business->update(['eligibility_status' => 'eligible', 'eligibility_reason' => null]);

            return $business;
        }

        $reasons = [];
        if ($program->regions && ! in_array($business->province, $program->regions, true) && ! in_array($business->country, $program->regions, true)) {
            $reasons[] = 'region';
        }
        $industries = $program->coveredIndustries();
        if ($industries && ! in_array($business->industry, $industries, true)) {
            $reasons[] = 'industry';
        }
        if ($program->business_sizes && ! in_array($business->size, $program->business_sizes, true)) {
            $reasons[] = 'size';
        }

        $status = $reasons ? 'ineligible' : 'eligible';
        if (! $reasons && $program->max_businesses) {
            $admitted = Business::where('pilot_program_id', $program->id)->where('eligibility_status', 'eligible')->whereKeyNot($business->id)->count();
            if ($admitted >= $program->max_businesses) {
                $status = 'waitlisted';
                $reasons[] = 'capacity';
            }
        }

        $business->update([
            'pilot_program_id' => $program->id,
            'eligibility_status' => $status,
            'eligibility_reason' => $reasons ? implode(',', $reasons) : null,
        ]);

        return $business;
    }

    /**
     * Re-applies the current pilot scope to every onboarded business, oldest first so capacity goes to
     * those who joined earlier (waitlisted businesses move up when places free). Staff overrides are kept.
     */
    public function reevaluateAll(): int
    {
        $businesses = Business::whereNotNull('onboarding_completed_at')
            ->where(fn ($q) => $q->whereNull('eligibility_reason')->orWhere('eligibility_reason', 'not like', 'override%'))
            ->orderBy('onboarding_completed_at')->orderBy('id')->get();
        Business::whereIn('id', $businesses->pluck('id'))->update(['eligibility_status' => null]);
        $businesses->each(fn (Business $business) => $this->handle($business->fresh()));

        return $businesses->count();
    }

    /** Whether a staff member decided this business's eligibility by hand. */
    public static function isOverridden(Business $business): bool
    {
        return str_starts_with((string) $business->eligibility_reason, 'override');
    }
}
