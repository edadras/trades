<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import Badge from '@/Components/ui/Badge.vue';
import { computed } from 'vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ business: Object, pendingOutcomes: { type: Array, default: () => [] }, stats: Object, cases: Array, drafts: Number, tasks: Array, appointments: Array, recommended: Array, conversations: Array });
const { t, dateTime, relative } = useI18n();

const restricted = computed(() => ['ineligible', 'waitlisted'].includes(props.business.eligibility));
const canOpenCases = computed(() => props.business.can_open_cases !== false);
const knownReasons = ['region', 'industry', 'size', 'capacity'];
/** Eligibility reasons are stored as comma-separated keys by the evaluator, or as free text when staff override. */
const eligibilityReasons = computed(() =>
    (props.business.eligibility_reason ?? '')
        .split(',')
        .map((r) => r.trim())
        .filter(Boolean)
        .map((r) => (knownReasons.includes(r) ? t(`eligibility_banner.reasons.${r}`) : r)),
);
</script>

<template>
    <AppLayout :title="$t('nav.dashboard')">
        <!-- Greeting + primary CTA -->
        <section class="hero-gradient relative overflow-hidden rounded-[32px] p-6 text-white sm:p-10">
            <div class="relative z-10 max-w-2xl">
                <p class="flex flex-wrap items-center gap-2 text-sm text-white/75">
                    {{ business.name }}
                    <Badge v-if="business.role" tone="glass">{{ $t('eligibility_banner.your_role', { role: $t(`team.roles.${business.role}`) }) }}</Badge>
                </p>
                <h2 class="mt-2 text-2xl font-semibold leading-snug sm:text-3xl">{{ $t('dashboard.greeting') }}</h2>
                <div class="mt-6 flex flex-wrap gap-3">
                    <Button v-if="canOpenCases" :href="route('cases.create')" variant="light" size="lg" icon="plus">{{ $t('dashboard.new_problem') }}</Button>
                    <Button v-if="drafts" :href="route('cases.index', { status: 'draft' })" variant="glass" icon="edit">{{ $t('dashboard.drafts', { n: drafts }) }}</Button>
                </div>
            </div>
            <div class="pointer-events-none absolute -end-16 -top-16 size-72 rounded-full bg-white/10 blur-2xl" />
        </section>

        <section v-if="restricted" class="mt-6 flex flex-col gap-4 rounded-[var(--radius-card)] bg-amber-50 p-5 ring-1 ring-amber-200 sm:flex-row sm:items-start">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white text-amber-700"><Icon name="alert" /></span>
            <div class="min-w-0 flex-1 text-sm leading-6 text-amber-900">
                <p class="text-base font-semibold">{{ $t(`eligibility_banner.${business.eligibility}_title`) }}</p>
                <p class="mt-1">{{ $t(`eligibility_banner.${business.eligibility}_text`) }}</p>
                <div v-if="eligibilityReasons.length" class="mt-2">
                    <span class="font-medium">{{ $t('eligibility_banner.reasons_title') }}</span>
                    <ul class="list-inside list-disc">
                        <li v-for="(r, i) in eligibilityReasons" :key="i">{{ r }}</li>
                    </ul>
                </div>
                <p class="mt-2 font-medium">{{ $t('eligibility_banner.no_new_cases') }}</p>
            </div>
            <Button :href="route('support.index')" variant="light" size="sm" icon="chat" class="shrink-0">{{ $t('eligibility_banner.contact') }}</Button>
        </section>

        <section v-if="pendingOutcomes.length" class="mt-6">
            <Card :title="$t('eligibility_banner.pending_outcomes')" :subtitle="$t('eligibility_banner.pending_outcomes_hint')">
                <ul class="space-y-2">
                    <li v-for="o in pendingOutcomes" :key="o.number">
                        <Link :href="route('cases.show', { case: o.number })" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-navy-50">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700"><Icon name="target" :size="16" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">{{ o.title }}</p>
                                <p class="text-xs text-gray-500" dir="ltr">{{ o.number }}</p>
                            </div>
                            <span class="hidden text-sm font-medium text-navy-700 sm:inline">{{ $t('eligibility_banner.review_outcome') }}</span>
                            <Icon name="chevron" :size="16" class="text-gray-400" />
                        </Link>
                    </li>
                </ul>
            </Card>
        </section>

        <section class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatCard :label="$t('dashboard.active_cases')" :value="stats.active_cases" icon="folder" />
            <StatCard :label="$t('dashboard.waiting_actions')" :value="stats.waiting_actions" icon="flag" tone="amber" />
            <StatCard :label="$t('dashboard.expert_sessions')" :value="stats.expert_sessions" icon="calendar" tone="light" />
            <StatCard :label="$t('dashboard.recommendations')" :value="stats.recommendations" icon="book" tone="emerald" />
        </section>

        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-ink">{{ $t('dashboard.my_cases') }}</h2>
                    <Link :href="route('cases.index')" class="text-sm font-medium text-navy-700 hover:underline">{{ $t('common.see_all') }}</Link>
                </div>
                <div v-if="cases.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <CaseCard v-for="c in cases" :key="c.id" :item="c" :href="c.status === 'draft' ? route('cases.create', { case: c.number }) : route('cases.show', { case: c.number })" />
                </div>
                <EmptyState v-else icon="folder" :title="$t('dashboard.no_cases')" :text="$t('dashboard.no_cases_hint')">
                    <Button v-if="canOpenCases" :href="route('cases.create')" icon="plus">{{ $t('dashboard.new_problem') }}</Button>
                </EmptyState>
            </section>

            <aside class="space-y-6">
                <Card :title="$t('dashboard.tasks')">
                    <ul v-if="tasks.length" class="space-y-3">
                        <li v-for="task in tasks" :key="task.id">
                            <Link :href="route('cases.show', { case: task.case_number, tab: 'workspace' })" class="flex items-start gap-3 rounded-2xl p-2 hover:bg-navy-50">
                                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-xl" :class="task.overdue ? 'bg-rose-50 text-rose-600' : 'bg-navy-50 text-navy-700'"><Icon name="task" :size="16" /></span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ task.title }}</p>
                                    <p class="text-xs text-gray-500"><span dir="ltr">{{ task.case_number }}</span><template v-if="task.due_at"> · {{ task.overdue ? $t('tasks.overdue') : $t('tasks.due') }} {{ relative(task.due_at) }}</template></p>
                                </div>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('dashboard.no_tasks') }}</p>
                </Card>

                <Card :title="$t('dashboard.appointments')">
                    <ul v-if="appointments.length" class="space-y-3">
                        <li v-for="a in appointments" :key="a.id" class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="calendar" :size="18" /></span>
                            <div class="min-w-0 text-sm">
                                <p class="font-medium text-ink">{{ a.title }}</p>
                                <p class="text-gray-500">{{ dateTime(a.starts_at) }}</p>
                                <a v-if="a.meeting_url" :href="a.meeting_url" target="_blank" rel="noopener" class="text-navy-700 hover:underline">{{ $t('appointments.join') }}</a>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('dashboard.no_appointments') }}</p>
                </Card>

                <Card :title="$t('dashboard.messages')">
                    <ul v-if="conversations.length" class="space-y-2">
                        <li v-for="c in conversations" :key="c.id">
                            <Link :href="route('cases.show', { case: c.case_number, tab: 'workspace' })" class="flex items-center gap-3 rounded-2xl p-2 hover:bg-navy-50">
                                <Icon name="chat" class="text-navy-700" />
                                <div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ c.case_title }}</p><p class="text-xs text-gray-500">{{ relative(c.last_message_at) }}</p></div>
                                <span v-if="c.unread" class="rounded-full bg-navy-950 px-2 text-xs text-white">{{ c.unread }}</span>
                            </Link>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('dashboard.no_messages') }}</p>
                </Card>
            </aside>
        </div>

        <section v-if="recommended.length" class="mt-10">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-ink">{{ $t('dashboard.recommended') }}</h2>
                <Link :href="route('learning')" class="text-sm font-medium text-navy-700 hover:underline">{{ $t('common.see_all') }}</Link>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4"><KnowledgeCard v-for="a in recommended" :key="a.id" :article="a" /></div>
        </section>
    </AppLayout>
</template>
