<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Stepper from '@/Components/ui/Stepper.vue';
import Icon from '@/Components/ui/Icon.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Timeline from '@/Components/ui/Timeline.vue';
import Modal from '@/Components/ui/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import StatusBadge from '@/Components/domain/StatusBadge.vue';
import UrgencyBadge from '@/Components/domain/UrgencyBadge.vue';
import ProvenanceBadge from '@/Components/domain/ProvenanceBadge.vue';
import AnalysisPanel from '@/Components/case/AnalysisPanel.vue';
import MatchesPanel from '@/Components/case/MatchesPanel.vue';
import ChatPanel from '@/Components/case/ChatPanel.vue';
import WorkPanel from '@/Components/case/WorkPanel.vue';
import DocumentsPanel from '@/Components/case/DocumentsPanel.vue';
import NotesPanel from '@/Components/case/NotesPanel.vue';
import OutcomePanel from '@/Components/case/OutcomePanel.vue';
import ReviewPanel from '@/Components/case/ReviewPanel.vue';
import TeamPanel from '@/Components/case/TeamPanel.vue';
import CollaborationPanel from '@/Components/case/CollaborationPanel.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ case: Object, tab: String, categories: Array, servicePaths: { type: Array, default: () => [] } });
const item = computed(() => props.case);
const { t, relative, dateTime, option } = useI18n();
const active = ref(props.tab ?? 'overview');
watch(active, (v) => typeof window !== 'undefined' && window.history.replaceState(window.history.state, '', `${window.location.pathname}?tab=${v}`));

const tabs = computed(() => [
    { key: 'overview', label: t('case_tabs.overview') },
    { key: 'analysis', label: t('case_tabs.analysis') },
    { key: 'supporters', label: t('case_tabs.supporters'), count: item.value.matches.filter((m) => m.status === 'proposed').length || null },
    { key: 'workspace', label: t('case_tabs.workspace') },
    { key: 'documents', label: t('case_tabs.documents'), count: item.value.documents.length || null },
    { key: 'timeline', label: t('case_tabs.timeline') },
    { key: 'result', label: t('case_tabs.result') },
]);

const eventIcon = { case_created: 'plus', status_changed: 'refresh', ai_analysis_completed: 'sparkles', human_review_completed: 'shield-check', matching_completed: 'network', expert_selected: 'check', expert_rejected: 'x', expert_joined: 'users', expert_declined: 'x', document_uploaded: 'file', document_requested: 'file', task_created: 'task', task_done: 'check', appointment_scheduled: 'calendar', note_added: 'note', outcome_recorded: 'flag', satisfaction_submitted: 'star', voice_transcribed: 'mic', case_escalated: 'alert',
    outcome_confirmed: 'shield-check', outcome_auto_confirmed: 'shield-check', outcome_disputed: 'alert', case_reopened: 'refresh', details_updated: 'edit',
    collaboration_requested: 'scale', collaboration_pending: 'scale', collaboration_approved: 'check', collaboration_rejected: 'x',
    expert_left: 'logout', expert_removed: 'x', expert_replacement_requested: 'refresh', expert_proposed_manually: 'users', matching_no_candidates: 'alert' };
const reasonEvents = ['outcome_disputed', 'case_reopened', 'expert_left', 'expert_removed', 'expert_replacement_requested'];
const pathName = (key) => props.servicePaths.find((p) => p.key === key)?.name ?? key;
const timeline = computed(() => item.value.timeline.map((e) => ({
    id: e.id, at: e.created_at, icon: eventIcon[e.type] ?? 'dots-mark', tone: e.visibility,
    title: e.type === 'status_changed' ? t('timeline.status_changed', { to: t(`status.${e.data?.to}`) }) : t(`timeline.${e.type}`),
    text: [
        e.user,
        e.data?.title ?? e.data?.name ?? e.data?.what ?? e.data?.expert ?? (e.data?.outcome ? t(`outcome_type.${e.data.outcome}`) : null) ?? (e.data?.path ? pathName(e.data.path) : null),
        reasonEvents.includes(e.type) ? e.data?.reason : null,
    ].filter(Boolean).join(' · '),
})));
const back = computed(() => (item.value.context === 'staff' ? route('review.cases.index') : item.value.context === 'expert' ? route('expert.cases.index') : route('cases.index')));
const proposals = ref(null);
const scrollToProposals = () => proposals.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
const waiting = (on) => router.post(route('cases.waiting', { case: item.value.number }), { waiting: on }, { preserveScroll: true });
const bp = computed(() => item.value.business_profile);
const consentKeys = ['ai_processing', 'share_with_foreign_experts', 'anonymized_learning'];

const details = useForm({ actions_taken: props.case.actions_taken ?? '' });
const editingDetails = ref(false);
const openDetails = () => { details.actions_taken = item.value.actions_taken ?? ''; details.clearErrors(); editingDetails.value = true; };
const saveDetails = () => details.patch(route('cases.details.update', { case: item.value.number }), { preserveScroll: true, onSuccess: () => (editingDetails.value = false) });
</script>

<template>
    <AppLayout :title="item.title || $t('case.untitled')" :back="back" wide>
        <div class="grid gap-6" :class="item.context === 'staff' ? 'xl:grid-cols-[minmax(0,1fr)_340px]' : ''">
            <div class="min-w-0 space-y-6">
                <!-- Header -->
                <section class="card overflow-hidden">
                    <div class="soft-gradient p-5 sm:p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs text-gray-500" dir="ltr">{{ item.number }}</span>
                            <StatusBadge :status="item.status" />
                            <UrgencyBadge :urgency="item.urgency" />
                            <ProvenanceBadge :state="item.classification_source" />
                            <Badge v-if="item.is_sensitive" tone="red"><Icon name="shield" :size="12" />{{ $t('analysis.sensitive') }}</Badge>
                            <Badge v-if="item.is_priority" tone="amber"><Icon name="bolt" :size="12" />{{ $t('lifecycle.priority') }}</Badge>
                        </div>
                        <p v-if="item.category" class="mt-3 text-sm text-gray-600">{{ item.category.name }}<template v-if="item.subcategory"> · {{ item.subcategory.name }}</template></p>
                        <div class="mt-5 rounded-3xl bg-white/70 px-2 py-3 ring-1 ring-[var(--border)]"><Stepper :steps="item.stepper" /></div>
                    </div>
                    <div v-if="item.next_action || item.status === 'waiting'" class="flex flex-col gap-3 border-t border-[var(--border)] p-5 sm:flex-row sm:items-center sm:p-6">
                        <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="flag" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium text-navy-700">{{ $t('case.next_action') }}</p>
                            <p class="font-medium text-ink">{{ item.next_action ?? $t('status.waiting') }}</p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                <template v-if="item.next_action_owner">{{ $t('tasks.owner') }}: {{ $t(`roles_short.${item.next_action_owner}`) }}</template>
                                <template v-if="item.next_action_due_at"> · {{ $t('tasks.deadline') }}: {{ dateTime(item.next_action_due_at) }}</template>
                            </p>
                        </div>
                        <Button v-if="item.can.participate && item.status === 'in_progress' && item.context !== 'business'" size="sm" variant="light" icon="pause" @click="waiting(true)">{{ $t('case.set_waiting') }}</Button>
                        <Button v-if="item.can.participate && item.status === 'waiting' && item.experts.length" size="sm" icon="play" @click="waiting(false)">{{ $t('case.resume') }}</Button>
                    </div>
                </section>

                <div v-if="item.can.confirm_outcome" class="flex flex-col gap-3 rounded-3xl bg-emerald-50 p-4 text-emerald-900 ring-1 ring-emerald-200 sm:flex-row sm:items-center sm:p-5">
                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white text-emerald-700 ring-1 ring-emerald-200"><Icon name="target" :size="18" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ $t('lifecycle.confirm_banner_title') }}</p>
                        <p class="mt-1 text-sm leading-6">{{ $t('lifecycle.confirm_banner_text') }}</p>
                    </div>
                    <Button size="sm" icon="arrow-left" @click="active = 'result'">{{ $t('lifecycle.confirm_banner_action') }}</Button>
                </div>

                <div v-if="item.context === 'expert' && !item.confidential_access" class="flex items-start gap-3 rounded-3xl bg-amber-50 p-4 text-amber-900 ring-1 ring-amber-200 sm:p-5">
                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white text-amber-700 ring-1 ring-amber-200"><Icon name="lock" :size="18" /></span>
                    <div class="min-w-0">
                        <p class="font-medium">{{ $t('lifecycle.confidential_hidden_title') }}</p>
                        <p class="mt-1 text-sm leading-6">{{ $t('lifecycle.confidential_hidden_text') }}</p>
                    </div>
                </div>

                <Tabs v-model="active" :tabs="tabs" />

                <!-- Overview -->
                <div v-if="active === 'overview'" class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                    <div class="space-y-6">
                        <Card :title="$t('case.problem')">
                            <p v-if="item.description" class="whitespace-pre-line leading-8 text-gray-700">{{ item.description }}</p>
                            <div v-if="item.voice.has_voice" class="mt-4 rounded-2xl bg-[var(--surface-muted)] p-3">
                                <p class="mb-2 flex items-center gap-2 text-xs font-medium text-gray-600"><Icon name="mic" :size="14" />{{ $t('case.voice_note') }}</p>
                                <audio :src="route('cases.voice', { case: item.number })" controls class="h-10 w-full" />
                                <p v-if="item.voice.transcript" class="mt-2 text-sm text-gray-600">{{ item.voice.transcript }}</p>
                            </div>
                        </Card>
                        <Card v-if="item.actions_taken || item.can.edit_details" :title="$t('lifecycle.actions_taken')">
                            <template v-if="item.can.edit_details" #actions><Button size="sm" variant="light" icon="edit" @click="openDetails">{{ $t('lifecycle.edit_actions') }}</Button></template>
                            <p v-if="item.actions_taken" class="whitespace-pre-line leading-8 text-gray-700">{{ item.actions_taken }}</p>
                            <p v-else class="text-sm text-gray-500">{{ $t('lifecycle.actions_taken_empty') }}</p>
                        </Card>
                        <Card v-if="item.answers.length" :title="$t('case.intake_answers')">
                            <dl class="space-y-4">
                                <div v-for="a in item.answers" :key="a.key">
                                    <dt class="text-sm text-gray-500">{{ a.question }}</dt>
                                    <dd class="mt-1 text-ink">{{ a.answer && a.answer !== '—' ? a.answer : $t('intake.skipped') }}</dd>
                                </div>
                            </dl>
                        </Card>
                        <Card v-if="item.analysis" :title="$t('analysis.summary')">
                            <template #actions><ProvenanceBadge :state="item.analysis.verification_state" /></template>
                            <p class="leading-7 text-gray-700">{{ item.analysis.guidance?.situation || item.analysis.summary }}</p>
                            <ul v-if="item.analysis.suggested_actions?.length" class="mt-4 space-y-2">
                                <li v-for="(s, i) in item.analysis.suggested_actions.slice(0, 3)" :key="i" class="flex items-start gap-2 text-sm text-gray-700"><Icon name="check" :size="16" class="mt-1 text-emerald-600" />{{ s }}</li>
                            </ul>
                            <button type="button" class="mt-4 text-sm font-medium text-navy-700 hover:underline" @click="active = 'analysis'">{{ $t('analysis.view_full') }}</button>
                        </Card>
                    </div>
                    <div class="space-y-6">
                        <Card :title="$t('case.business')">
                            <dl class="space-y-3 text-sm">
                                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.trade_name') }}</dt><dd class="text-end font-medium">{{ bp.name }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.industry') }}</dt><dd class="text-end">{{ option('industries', bp.industry) }}</dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.size') }}</dt><dd class="text-end">{{ option('sizes', bp.size) }} · <span dir="ltr">{{ bp.employees_range }}</span></dd></div>
                                <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.province') }}</dt><dd class="text-end">{{ option('provinces', bp.province) || option('countries', bp.country) }}</dd></div>
                                <template v-for="f in ['contact_name', 'contact_email', 'contact_phone', 'website', 'legal_name', 'registration_number', 'address']" :key="f">
                                    <div v-if="bp[f] !== undefined && bp[f] !== null" class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t(`fields.${f}`) }}</dt><dd class="min-w-0 break-words text-end" :dir="['contact_email', 'contact_phone', 'website', 'registration_number'].includes(f) ? 'ltr' : undefined">{{ bp[f] }}</dd></div>
                                </template>
                            </dl>
                            <p v-if="item.referral?.partner" class="mt-3 flex items-center gap-2 text-sm text-gray-600"><Icon name="link" :size="14" />{{ $t('lifecycle.referral') }}: {{ item.referral.partner }}</p>
                            <p class="mt-4 flex items-start gap-2 text-xs text-gray-500"><Icon name="lock" :size="14" class="mt-0.5" />{{ $t('case.privacy_note') }}</p>
                        </Card>
                        <Card :title="$t('case.team')">
                            <ul class="space-y-3 text-sm">
                                <li v-for="e in item.experts" :key="e.id" class="flex items-center gap-2"><Icon name="user" :size="16" class="text-navy-700" />{{ e.name }} <Badge tone="green">{{ $t(`match.role_${e.role}`) }}</Badge></li>
                                <li v-if="item.case_manager" class="flex items-center gap-2"><Icon name="shield-check" :size="16" class="text-navy-700" />{{ item.case_manager.name }} <Badge tone="sky">{{ $t('roles_short.case_manager') }}</Badge></li>
                                <li v-if="!item.experts.length && !item.case_manager" class="text-gray-500">{{ $t('case.no_team') }}</li>
                            </ul>
                            <Button v-if="item.matches.some((m) => m.status === 'proposed') && item.can.decide_matches" class="mt-4" size="sm" icon="users" @click="active = 'supporters'">{{ $t('match.review_proposals') }}</Button>
                            <Button v-else-if="item.experts.length && (item.can.leave || item.can.release_experts)" class="mt-4" size="sm" variant="light" icon="users" @click="active = 'supporters'">{{ $t('team_case.manage') }}</Button>
                        </Card>
                        <Card v-if="item.data_consent" :title="$t('data_consent.summary_title')">
                            <ul class="space-y-2.5 text-sm">
                                <li v-for="k in consentKeys" :key="k" class="flex items-center justify-between gap-3">
                                    <span class="min-w-0 text-gray-600">{{ $t(`data_consent.${k}`) }}</span>
                                    <Badge :tone="item.data_consent[k] ? 'green' : 'gray'" class="shrink-0"><Icon :name="item.data_consent[k] ? 'check' : 'x'" :size="12" />{{ item.data_consent[k] ? $t('data_consent.on') : $t('data_consent.off') }}</Badge>
                                </li>
                            </ul>
                        </Card>
                        <Card :title="$t('case.dates')">
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between"><dt class="text-gray-500">{{ $t('case.submitted') }}</dt><dd>{{ dateTime(item.submitted_at) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">{{ $t('common.updated') }}</dt><dd>{{ relative(item.updated_at) }}</dd></div>
                            </dl>
                        </Card>
                    </div>
                </div>

                <AnalysisPanel v-else-if="active === 'analysis'" :analysis="item.analysis" :contents="item.recommended_contents" />
                <div v-else-if="active === 'supporters'" class="space-y-6">
                    <TeamPanel :item="item" @open-supporters="scrollToProposals" />
                    <div ref="proposals"><MatchesPanel :item="item" /></div>
                </div>
                <div v-else-if="active === 'workspace'" class="grid grid-cols-1 gap-6 2xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                    <ChatPanel :item="item" />
                    <div class="min-w-0 space-y-6"><WorkPanel :item="item" /><CollaborationPanel :item="item" :service-paths="servicePaths" /><NotesPanel :item="item" /></div>
                </div>
                <DocumentsPanel v-else-if="active === 'documents'" :item="item" />
                <Card v-else-if="active === 'timeline'" :title="$t('case_tabs.timeline')"><Timeline :items="timeline" /></Card>
                <OutcomePanel v-else-if="active === 'result'" :item="item" />
            </div>

            <aside v-if="item.context === 'staff' && item.can.review" class="xl:sticky xl:top-24 xl:self-start">
                <ReviewPanel :item="item" :categories="categories" />
            </aside>
        </div>
        <Modal :show="editingDetails" :title="$t('lifecycle.edit_actions_title')" @close="editingDetails = false">
            <Field v-model="details.actions_taken" as="textarea" :rows="6" :label="$t('lifecycle.actions_taken_label')" :hint="$t('lifecycle.actions_taken_hint')" :placeholder="$t('lifecycle.actions_taken_placeholder')" :error="details.errors.actions_taken" />
            <template #footer>
                <Button variant="ghost" no-icon @click="editingDetails = false">{{ $t('common.cancel') }}</Button>
                <Button icon="check" :loading="details.processing" @click="saveDetails">{{ $t('lifecycle.save') }}</Button>
            </template>
        </Modal>
    </AppLayout>
</template>
