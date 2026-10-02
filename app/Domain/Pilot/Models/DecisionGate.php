<?php

namespace App\Domain\Pilot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Decision points of the pilot: week 6 (confirm/amend scope), week 16 (launch or postpone pilot),
 * week 24 (scale, revise or stop). A negative decision must name the root cause.
 */
class DecisionGate extends Model
{
    public const CRITERIA = [
        'scope' => ['audience_defined', 'priority_problems_selected', 'success_metrics_defined', 'support_model_defined', 'partner_coordination_agreed'],
        'launch' => ['case_flow_tested', 'learning_content_tested', 'matching_tested', 'human_review_tested', 'supporters_onboarded'],
        'scale' => ['case_outcomes_clear', 'collaboration_quality_clear', 'user_satisfaction_clear', 'quality_sustainable'],
    ];

    public const DECISIONS = [
        'scope' => ['approved', 'amended'],
        'launch' => ['launch', 'postpone'],
        'scale' => ['scale', 'revise', 'stop'],
    ];

    public const NEGATIVE = ['amended', 'postpone', 'revise', 'stop'];

    public const ROOT_CAUSES = ['audience_selection', 'supporter_shortage', 'handling_process', 'ai_guidance_quality', 'other'];

    protected $fillable = ['pilot_program_id', 'key', 'week', 'due_on', 'criteria', 'decision', 'root_cause', 'notes', 'evidence', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['due_on' => 'date', 'criteria' => 'array', 'evidence' => 'array', 'decided_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(PilotProgram::class, 'pilot_program_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function isOverdue(): bool
    {
        return $this->decision === 'pending' && $this->due_on?->isPast();
    }
}
