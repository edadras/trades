<?php

namespace App\Domain\Experts\Models;

use App\Domain\Cases\Models\CaseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertSkill extends Model
{
    protected $fillable = ['expert_profile_id', 'case_category_id', 'level', 'years'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'case_category_id');
    }
}
