<?php

namespace App\Domain\AI\Models;

use App\Domain\Cases\Enums\VerificationState;
use Illuminate\Database\Eloquent\Model;

/** Every classification ever applied to a case, AI or human, for provenance and accuracy tracking. */
class AiClassification extends Model
{
    protected $fillable = ['case_id', 'ai_analysis_id', 'category_id', 'subcategory_id', 'urgency', 'confidence', 'source', 'created_by'];

    protected function casts(): array
    {
        return ['source' => VerificationState::class, 'confidence' => 'float'];
    }
}
