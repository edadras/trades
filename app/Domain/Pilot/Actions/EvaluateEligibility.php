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
}
