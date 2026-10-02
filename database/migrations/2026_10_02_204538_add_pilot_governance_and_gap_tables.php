<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pilot scope, eligibility and first-month decisions.
        Schema::create('pilot_programs', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('status', 20)->default('draft');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('regions')->nullable();
            $table->json('value_chains')->nullable();
            $table->json('industries')->nullable();
            $table->json('business_sizes')->nullable();
            $table->unsignedTinyInteger('max_groups')->default(2);
            $table->json('priority_category_ids')->nullable();
            $table->json('eligibility_notes')->nullable();
            $table->json('success_definition')->nullable();
            $table->json('support_model_policy')->nullable();
            $table->json('partner_coordination')->nullable();
            $table->unsignedInteger('max_businesses')->nullable();
            $table->timestamps();
        });

        Schema::create('decision_gates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilot_program_id')->constrained()->cascadeOnDelete();
            $table->string('key', 20);
            $table->unsignedTinyInteger('week');
            $table->date('due_on')->nullable();
            $table->json('criteria')->nullable();
            $table->string('decision', 20)->default('pending');
            $table->string('root_cause', 40)->nullable();
            $table->text('notes')->nullable();
            $table->json('evidence')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['pilot_program_id', 'key']);
        });

        Schema::create('pilot_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilot_program_id')->nullable()->constrained()->nullOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->json('metrics');
            $table->json('errors');
            $table->text('notes')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['week_start']);
        });

        Schema::create('ai_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('operation', 40);
            $table->text('message');
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        // Partners (referral sources).
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('type', 30)->default('association');
            $table->string('referral_code', 40)->unique();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('referral_method')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
            $table->foreignId('pilot_program_id')->nullable()->after('partner_id')->constrained()->nullOnDelete();
            $table->string('eligibility_status', 20)->nullable()->after('status');
            $table->text('eligibility_reason')->nullable()->after('eligibility_status');
        });

        Schema::table('cases', function (Blueprint $table) {
            $table->foreignId('partner_id')->nullable()->after('business_id')->constrained()->nullOnDelete();
            $table->string('referral_source', 20)->default('self')->after('partner_id');
            $table->text('actions_taken')->nullable()->after('description');
            $table->json('data_consent')->nullable()->after('locale');
            $table->boolean('is_priority')->default(false)->after('is_sensitive');
        });

        // Outcome confirmation by the business, history kept on reopen.
        Schema::table('case_outcomes', function (Blueprint $table) {
            $table->dropUnique(['case_id']);
            $table->string('confirmation_status', 20)->default('pending')->after('metrics');
            $table->foreignId('confirmed_by')->nullable()->after('confirmation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->text('dispute_reason')->nullable()->after('confirmed_at');
            $table->timestamp('superseded_at')->nullable()->after('dispute_reason');
            $table->index(['case_id', 'superseded_at']);
        });

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->string('dissatisfaction_reason', 40)->nullable()->after('comment');
        });

        // Experts: organisations, support models, privacy.
        Schema::table('expert_profiles', function (Blueprint $table) {
            $table->string('supporter_type', 20)->default('individual')->after('user_id');
            $table->string('organization_name')->nullable()->after('supporter_type');
            $table->json('support_models')->nullable()->after('collaboration_types');
        });

        Schema::table('case_experts', function (Blueprint $table) {
            $table->string('engagement_model', 20)->nullable()->after('role');
            $table->text('engagement_terms')->nullable()->after('engagement_model');
            $table->text('leave_reason')->nullable()->after('left_at');
        });

        Schema::table('expert_matches', function (Blueprint $table) {
            $table->string('engagement_model', 20)->nullable()->after('source');
            $table->text('engagement_terms')->nullable()->after('engagement_model');
        });

        // Service paths that need legal/security review before activation.
        Schema::create('service_paths', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('mode', 20)->default('allowed');
            $table->json('required_documents')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('collaboration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->cascadeOnDelete();
            $table->foreignId('service_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('conditions')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        // Team invitations for a business.
        Schema::create('business_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('member');
            $table->string('token_hash', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        // Data subject requests and complaints.
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->string('file_path')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('case_id')->nullable()->constrained('cases')->nullOnDelete();
            $table->string('category', 30);
            $table->string('subject');
            $table->text('body');
            $table->string('status', 20)->default('open');
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['complaints', 'data_requests', 'business_invitations', 'collaboration_requests', 'service_paths'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('expert_matches', fn (Blueprint $t) => $t->dropColumn(['engagement_model', 'engagement_terms']));
        Schema::table('case_experts', fn (Blueprint $t) => $t->dropColumn(['engagement_model', 'engagement_terms', 'leave_reason']));
        Schema::table('expert_profiles', fn (Blueprint $t) => $t->dropColumn(['supporter_type', 'organization_name', 'support_models']));
        Schema::table('satisfaction_surveys', fn (Blueprint $t) => $t->dropColumn('dissatisfaction_reason'));
        Schema::table('case_outcomes', function (Blueprint $t) {
            $t->dropIndex(['case_id', 'superseded_at']);
            $t->dropConstrainedForeignId('confirmed_by');
            $t->dropColumn(['confirmation_status', 'confirmed_at', 'dispute_reason', 'superseded_at']);
            $t->unique('case_id');
        });
        Schema::table('cases', function (Blueprint $t) {
            $t->dropConstrainedForeignId('partner_id');
            $t->dropColumn(['referral_source', 'actions_taken', 'data_consent', 'is_priority']);
        });
        Schema::table('businesses', function (Blueprint $t) {
            $t->dropConstrainedForeignId('partner_id');
            $t->dropConstrainedForeignId('pilot_program_id');
            $t->dropColumn(['eligibility_status', 'eligibility_reason']);
        });
        foreach (['partners', 'ai_incidents', 'pilot_reports', 'decision_gates', 'pilot_programs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
