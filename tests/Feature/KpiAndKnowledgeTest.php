<?php

namespace Tests\Feature;

use App\Domain\Analytics\KpiService;
use App\Domain\Analytics\Models\Kpi;
use App\Domain\Identity\Enums\Role;
use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KpiAndKnowledgeTest extends TestCase
{
    public function test_pilot_kpis_are_seeded_and_evaluated(): void
    {
        $this->businessUser();
        $rows = app(KpiService::class)->evaluate()->keyBy('key');

        $this->assertSame(30.0, $rows['active_businesses']['target']);
        $this->assertSame(1.0, $rows['active_businesses']['value']);
        $this->assertSame(80.0, $rows['initial_review']['target'], 'share of cases reviewed within 48h');
        $this->assertSame('>=', $rows['initial_review']['comparator']);
        $this->assertSame(48.0, $rows['initial_review_time']['target']);
        $this->assertSame('<=', $rows['initial_review_time']['comparator']);
        $this->assertFalse($rows['active_businesses']['achieved']);
    }

    public function test_admin_changes_a_kpi_target_without_code(): void
    {
        $admin = $this->staff(Role::SuperAdmin);
        $kpi = Kpi::where('key', 'active_businesses')->first();

        $this->actingAs($admin)->put("/fa/admin/kpis/{$kpi->id}", [
            'key' => 'active_businesses', 'name' => ['fa' => 'کسب‌وکار', 'en' => 'Businesses'], 'metric' => 'active_businesses',
            'unit' => 'count', 'comparator' => '>=', 'is_active' => true, 'sort_order' => 0, 'target_value' => 1,
        ])->assertSessionHasNoErrors();

        $this->businessUser();
        $row = app(KpiService::class)->evaluate()->firstWhere('key', 'active_businesses');
        $this->assertSame(1.0, $row['target']);
        $this->assertTrue($row['achieved']);
        $this->assertSame(1, app(KpiService::class)->snapshot() > 0 ? 1 : 0);
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->actingAs($this->staff(Role::SuperAdmin))->get('/en/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')->has('kpis', 14)->has('charts.funnel', 7));
    }

    public function test_editing_approved_content_sends_it_back_to_review(): void
    {
        $cm = $this->staff(Role::ContentManager);
        $article = KnowledgeArticle::first();

        $this->actingAs($cm)->put("/fa/admin/knowledge/{$article->id}", [
            'type' => 'guide', 'knowledge_category_id' => $article->knowledge_category_id, 'reading_minutes' => 9,
            'translations' => ['fa' => ['title' => 'عنوان تازه', 'body' => 'متن'], 'en' => ['title' => 'New title', 'body' => 'Body']],
        ])->assertSessionHasNoErrors();

        $article->refresh();
        $this->assertSame(ContentStatus::InReview, $article->verification_status);
        $this->assertFalse($article->isUsableByAi());

        $this->actingAs($cm)->post("/fa/admin/knowledge/{$article->id}/status", ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->assertTrue($article->fresh()->isUsableByAi());
    }

    public function test_expired_content_is_not_used(): void
    {
        $article = KnowledgeArticle::first();
        $article->update(['valid_until' => now()->subDay()]);

        $this->assertFalse($article->fresh()->isUsableByAi());
        $this->assertFalse(KnowledgeArticle::approved()->whereKey($article->id)->exists());
    }
}
