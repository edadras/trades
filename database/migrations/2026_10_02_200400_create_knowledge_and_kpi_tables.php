<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('knowledge_categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('publisher')->nullable();
            $table->string('url')->nullable();
            $table->string('type', 30)->default('internal');
            $table->string('reliability', 20)->default('high');
            $table->timestamps();
        });

        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->timestamps();
        });

        Schema::create('knowledge_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('type', 20)->default('article');
            $table->foreignId('knowledge_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('knowledge_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('country', 2)->nullable();
            $table->json('industries')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('video_url')->nullable();
            $table->string('attachment_path')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->default(3);
            $table->string('verification_status', 20)->default('draft');
            $table->date('valid_until')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['verification_status', 'published_at']);
        });

        Schema::create('knowledge_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->json('checklist')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();
            $table->unique(['knowledge_article_id', 'locale']);
        });

        Schema::create('knowledge_article_tag', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('knowledge_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'knowledge_tag_id']);
        });

        Schema::create('knowledge_article_case_category', function (Blueprint $table) {
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('case_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['knowledge_article_id', 'case_category_id']);
        });

        Schema::create('case_recommended_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('knowledge_article_id')->constrained()->cascadeOnDelete();
            $table->decimal('relevance', 5, 3)->default(0);
            $table->string('source', 20)->default('ai');
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            $table->unique(['case_id', 'knowledge_article_id']);
        });

        Schema::create('kpis', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('metric', 60);
            $table->string('unit', 20)->default('count');
            $table->string('comparator', 4)->default('>=');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->decimal('target_value', 12, 2);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_id')->constrained()->cascadeOnDelete();
            $table->decimal('value', 12, 2);
            $table->decimal('target_value', 12, 2)->nullable();
            $table->boolean('achieved')->default(false);
            $table->date('captured_on');
            $table->timestamps();
            $table->unique(['kpi_id', 'captured_on']);
        });
    }

    public function down(): void
    {
        foreach (['kpi_snapshots', 'kpi_targets', 'kpis', 'case_recommended_contents', 'knowledge_article_case_category', 'knowledge_article_tag', 'knowledge_translations', 'knowledge_articles', 'knowledge_tags', 'knowledge_sources', 'knowledge_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
