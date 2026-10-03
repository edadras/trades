<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Icon from '@/Components/ui/Icon.vue';
import BarChart from '@/Components/charts/BarChart.vue';
import { route, useI18n } from '@/i18n';

const canManagePilot = computed(() => usePage().props.auth.user?.permissions?.includes('pilot.manage'));
const canOpenCases = computed(() => ['cases.review', 'cases.view_all'].some((p) => usePage().props.auth.user?.permissions?.includes(p)));

const props = defineProps({ report: Object, categoryNames: { type: Object, default: () => ({}) }, kpiNames: { type: Object, default: () => ({}) } });
const { t, number, date, dateTime } = useI18n();

const metrics = computed(() => props.report.metrics ?? {});
const errors = computed(() => props.report.errors ?? {});
const statCards = [
    { key: 'new_businesses', icon: 'briefcase', tone: 'navy' },
    { key: 'cases_submitted', icon: 'folder', tone: 'light' },
    { key: 'cases_resolved', icon: 'flag', tone: 'emerald' },
    { key: 'cases_closed', icon: 'check', tone: 'light' },
    { key: 'reviews_completed', icon: 'shield-check', tone: 'light' },
    { key: 'satisfaction_avg', icon: 'star', tone: 'amber', suffix: '/5' },
];

const toBars = (counts, label) => Object.entries(counts ?? {}).map(([key, value]) => ({ key, label: label(key), value: Number(value) })).sort((a, b) => b.value - a.value);
const byCategory = computed(() => toBars(metrics.value.cases_by_category, (slug) => (slug === 'unclassified' ? t('pilot_report.unclassified') : props.categoryNames[slug] ?? slug)));
const outcomes = computed(() => toBars(metrics.value.outcomes, (key) => t(`outcome_type.${key}`)));
const kpis = computed(() => metrics.value.kpis ?? []);
const kpiColumns = [
    { key: 'key', label: t('pilot_report.kpi') },
    { key: 'value', label: t('pilot_report.value') },
    { key: 'target', label: t('pilot_report.target') },
    { key: 'achieved', label: t('pilot_report.state') },
];

const corrections = computed(() => (errors.value.ai_corrections ?? []).map((row, index) => ({ ...row, id: index })));
const correctionColumns = [
    { key: 'case', label: t('pilot_report.case') },
    { key: 'ai', label: t('pilot_report.ai_category') },
    { key: 'final', label: t('pilot_report.final_category') },
    { key: 'ai_urgency', label: t('pilot_report.ai_urgency') },
    { key: 'final_urgency', label: t('pilot_report.final_urgency') },
];
const categoryLabel = (slug) => (slug ? props.categoryNames[slug] ?? slug : '—');
const urgencyLabel = (urgency) => (urgency ? t(`urgency.${urgency}`) : '—');
const incidents = computed(() => Object.entries(errors.value.ai_incidents ?? {}).map(([operation, count]) => ({ operation, count: Number(count) })));
const dissatisfaction = computed(() => toBars(errors.value.dissatisfaction, (key) => t(`pilot_report.dissatisfaction.${key}`)));
const slaBreaches = computed(() => errors.value.sla_breaches ?? []);
const counters = computed(() => [
    { key: 'failed_jobs', icon: 'alert', value: Number(errors.value.failed_jobs ?? 0) },
    { key: 'outcome_disputes', icon: 'scale', value: Number(errors.value.outcome_disputes ?? 0) },
    { key: 'complaints', icon: 'megaphone', value: Number(errors.value.complaints ?? 0) },
]);

const notesForm = useForm({ notes: props.report.notes ?? '' });
const saveNotes = () => notesForm.put(route('admin.pilot.reports.notes', { report: props.report.id }), { preserveScroll: true });
const printReport = () => window.print();

onMounted(() => document.body.classList.add('pilot-report-print'));
onBeforeUnmount(() => document.body.classList.remove('pilot-report-print'));
</script>

<template>
    <AppLayout :title="$t('pilot_report.title')" :subtitle="$t('pilot_report.range', { start: date(report.week_start), end: date(report.week_end) })" :back="route('admin.pilot.show')" wide>
        <template #header-actions>
            <Button size="sm" variant="light" icon="file" class="print:hidden" @click="printReport">{{ $t('pilot_report.print') }}</Button>
            <a :href="route('admin.pilot.reports.export', { report: report.id })" class="inline-flex h-9 items-center gap-2 rounded-full bg-navy-950 ps-1 pe-3.5 text-[13px] font-medium text-white ring-1 ring-navy-950 transition hover:bg-navy-900 print:hidden">
                <span class="grid size-7 place-items-center rounded-full bg-white text-navy-950"><Icon name="download" :size="15" /></span>{{ $t('pilot_report.export') }}
            </a>
        </template>

        <div class="space-y-8">
            <header class="card flex flex-wrap items-center justify-between gap-3 p-5 sm:p-6">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-ink">{{ $t('pilot_report.title') }}</h2>
                    <p class="text-sm text-gray-500">{{ $t('pilot_report.range', { start: date(report.week_start), end: date(report.week_end) }) }}</p>
                </div>
                <div class="text-xs text-gray-500 sm:text-end">
                    <p>{{ $t('pilot_report.generated', { date: dateTime(report.created_at) }) }}</p>
                    <p v-if="report.author">{{ $t('pilot_report.author', { name: report.author }) }}</p>
                </div>
            </header>

            <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6 print:grid-cols-3">
                <StatCard v-for="s in statCards" :key="s.key" :label="$t(`pilot_report.metrics.${s.key}`)" :value="metrics[s.key] ?? null" :icon="s.icon" :tone="s.tone" :suffix="s.suffix" />
            </section>

            <section class="grid grid-cols-1 gap-4 xl:grid-cols-2 print:grid-cols-2">
                <BarChart :title="$t('pilot_report.by_category')" :data="byCategory" />
                <BarChart :title="$t('pilot_report.outcomes')" :data="outcomes" />
            </section>

            <Card :title="$t('pilot_report.kpis')" :padded="false" class="break-inside-avoid">
                <p v-if="!kpis.length" class="px-5 pb-6 text-sm text-gray-500 sm:px-6">{{ $t('pilot_report.none') }}</p>
                <DataTable v-else :columns="kpiColumns" :rows="kpis" row-key="key">
                    <template #cell-key="{ row }"><span class="font-medium">{{ kpiNames[row.key] ?? row.key }}</span></template>
                    <template #cell-value="{ row }"><span class="tabular-nums">{{ number(row.value) }}</span></template>
                    <template #cell-target="{ row }"><span class="tabular-nums">{{ number(row.target) }}</span></template>
                    <template #cell-achieved="{ row }"><Badge :tone="row.achieved ? 'green' : 'amber'">{{ row.achieved ? $t('pilot_report.achieved') : $t('pilot_report.missed') }}</Badge></template>
                </DataTable>
            </Card>

            <section>
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-ink">{{ $t('pilot_report.errors_title') }}</h2>
                    <p class="text-sm text-gray-500">{{ $t('pilot_report.errors_subtitle') }}</p>
                </div>
                <div class="grid gap-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div v-for="c in counters" :key="c.key" class="card flex items-center gap-4 p-5">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl" :class="c.value ? 'bg-rose-50 text-rose-700' : 'bg-navy-50 text-navy-700'"><Icon :name="c.icon" :size="18" /></span>
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">{{ $t(`pilot_report.sections.${c.key}`) }}</p>
                                <p class="text-2xl font-semibold tabular-nums text-ink">{{ number(c.value) }}</p>
                            </div>
                        </div>
                    </div>

                    <Card :title="$t('pilot_report.sections.ai_corrections')" :padded="false" class="break-inside-avoid">
                        <p v-if="!corrections.length" class="flex items-center gap-2 px-5 pb-6 text-sm text-gray-500 sm:px-6"><Icon name="check" :size="16" />{{ $t('pilot_report.none') }}</p>
                        <DataTable v-else :columns="correctionColumns" :rows="corrections">
                            <template #cell-case="{ row }"><Link v-if="row.case && canOpenCases" :href="route('review.cases.show', { case: row.case })" class="font-medium hover:underline" dir="ltr">{{ row.case }}</Link><span v-else>—</span></template>
                            <template #cell-ai="{ row }">{{ categoryLabel(row.ai) }}</template>
                            <template #cell-final="{ row }"><span :class="row.ai !== row.final ? 'font-medium text-rose-700' : ''">{{ categoryLabel(row.final) }}</span></template>
                            <template #cell-ai_urgency="{ row }">{{ urgencyLabel(row.ai_urgency) }}</template>
                            <template #cell-final_urgency="{ row }"><span :class="row.ai_urgency !== row.final_urgency ? 'font-medium text-rose-700' : ''">{{ urgencyLabel(row.final_urgency) }}</span></template>
                        </DataTable>
                    </Card>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] print:grid-cols-2">
                        <Card :title="$t('pilot_report.sections.ai_incidents')" class="break-inside-avoid">
                            <p v-if="!incidents.length" class="flex items-center gap-2 text-sm text-gray-500"><Icon name="check" :size="16" />{{ $t('pilot_report.none') }}</p>
                            <ul v-else class="divide-y divide-[var(--border)] text-sm">
                                <li v-for="i in incidents" :key="i.operation" class="flex items-center justify-between gap-3 py-2.5">
                                    <span class="min-w-0 truncate" dir="ltr">{{ i.operation }}</span>
                                    <Badge tone="red">{{ number(i.count) }}</Badge>
                                </li>
                            </ul>
                        </Card>
                        <Card :title="$t('pilot_report.sections.sla_breaches')" class="break-inside-avoid">
                            <p v-if="!slaBreaches.length" class="flex items-center gap-2 text-sm text-gray-500"><Icon name="check" :size="16" />{{ $t('pilot_report.none') }}</p>
                            <ul v-else class="flex flex-wrap gap-2">
                                <li v-for="n in slaBreaches" :key="n">
                                    <Link v-if="canOpenCases" :href="route('review.cases.show', { case: n })" class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-200 hover:underline" dir="ltr">{{ n }}</Link>
                                    <span v-else class="chip bg-rose-50 text-rose-700 ring-1 ring-rose-200" dir="ltr">{{ n }}</span>
                                </li>
                            </ul>
                        </Card>
                    </div>

                    <BarChart :title="$t('pilot_report.sections.dissatisfaction')" :data="dissatisfaction" />
                </div>
            </section>

            <Card :title="$t('pilot_report.notes')" class="break-inside-avoid">
                <p v-if="!canManagePilot" class="whitespace-pre-line text-sm leading-7 text-gray-700">{{ report.notes || '—' }}</p>
                <form v-else class="space-y-4 print:hidden" @submit.prevent="saveNotes">
                    <Field v-model="notesForm.notes" as="textarea" :rows="6" :hint="$t('pilot_report.notes_hint')" :error="notesForm.errors.notes" maxlength="10000" />
                    <div class="flex justify-end"><Button type="submit" icon="check" :loading="notesForm.processing">{{ $t('pilot_report.save_notes') }}</Button></div>
                </form>
                <p class="hidden whitespace-pre-line text-sm leading-7 print:block">{{ report.notes || '—' }}</p>
            </Card>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    body.pilot-report-print aside,
    body.pilot-report-print header.sticky {
        display: none !important;
    }
    body.pilot-report-print .lg\:ps-72 {
        padding-inline-start: 0 !important;
    }
    body.pilot-report-print {
        background: #fff !important;
    }
    body.pilot-report-print .card {
        box-shadow: none !important;
        break-inside: avoid;
    }
}
</style>
