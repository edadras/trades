<?php

namespace App\Domain\AI\Data;

use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Models\CaseCategory;

final class Classification
{
    public function __construct(
        public readonly ?CaseCategory $category,
        public readonly ?CaseCategory $subcategory,
        public readonly Urgency $urgency,
        public readonly float $confidence,
        /** @var array<int, string> */
        public readonly array $requiredExpertises = [],
    ) {}
}
