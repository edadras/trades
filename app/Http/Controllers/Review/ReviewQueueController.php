<?php

namespace App\Http\Controllers\Review;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReviewQueueController extends Controller
{
    public function index(Request $request): Response
    {
        $reviews = AiHumanReview::pending()->with(['case.business', 'case.category', 'aiCategory', 'analysis'])
            ->get()
            ->sortBy([fn ($a, $b) => (Urgency::tryFrom((string) $b->ai_urgency)?->weight() ?? 0) <=> (Urgency::tryFrom((string) $a->ai_urgency)?->weight() ?? 0), fn ($a, $b) => $a->created_at <=> $b->created_at])
            ->values()
            ->map(fn (AiHumanReview $r) => [
                'id' => $r->id,
                'reason' => $r->reason,
                'case_number' => $r->case->number,
                'title' => $r->case->title,
                'summary' => $r->analysis?->summary,
                'business' => $r->case->business->trade_name,
                'ai_category' => $r->aiCategory?->translate('name'),
                'ai_urgency' => $r->ai_urgency,
                'ai_confidence' => $r->ai_confidence,
                'is_sensitive' => $r->case->is_sensitive,
                'waiting_hours' => round($r->created_at->diffInMinutes(now()) / 60, 1),
                'sla_breached' => $r->created_at->diffInHours(now()) >= config('platform.initial_review_sla_hours'),
                'created_at' => $r->created_at->toIso8601String(),
            ]);

        return Inertia::render('Review/Queue', ['reviews' => $reviews, 'slaHours' => config('platform.initial_review_sla_hours')]);
    }

    public function cases(Request $request, CasePresenter $presenter): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:60'],
            'status' => ['nullable', Rule::in(CaseStatus::values())],
            'category' => ['nullable', 'integer'],
            'urgency' => ['nullable', Rule::in(Urgency::values())],
            'mine' => ['nullable', 'boolean'],
        ]);

        $cases = SupportCase::real()->with(['business', 'category', 'subcategory'])
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w->where('number', 'like', "%{$term}%")->orWhere('title', 'like', "%{$term}%")
                ->orWhereHas('business', fn ($b) => $b->where('trade_name', 'like', "%{$term}%"))))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['category'] ?? null, fn ($q, $c) => $q->where('category_id', $c))
            ->when($filters['urgency'] ?? null, fn ($q, $u) => $q->where('urgency', $u))
            ->when($filters['mine'] ?? false, fn ($q) => $q->where('case_manager_id', $request->user()->id))
            ->latest('updated_at')->paginate(20)->withQueryString()->through(fn ($c) => $presenter->card($c));

        return Inertia::render('Review/Cases', [
            'cases' => $cases,
            'filters' => $filters,
            'categories' => CaseCategory::roots()->orderBy('sort_order')->get()->map->toOption(),
        ]);
    }
}
