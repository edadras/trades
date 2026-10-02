<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import DateTimeField from '@/Components/ui/DateTimeField.vue';
import Badge from '@/Components/ui/Badge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({
    program: Object,
    gates: Array,
    reports: Array,
    categories: Array,
    valueChains: Object,
    rootCauses: Array,
    negative: Array,
});
const { t, number, date, dateTime, option } = useI18n();
const page = usePage();
const canManage = computed(() => (page.props.auth?.user?.permissions ?? []).includes('pilot.manage'));
const TOTAL_WEEKS = 24;
const gateIcons = { scope: 'target', launch: 'bolt', scale: 'trending-up' };

const weekProgress = computed(() => (props.program?.current_week ? Math.min(100, (Math.min(props.program.current_week, TOTAL_WEEKS) / TOTAL_WEEKS) * 100) : 0));

const translatable = (value) => ({ fa: value?.fa ?? '', en: value?.en ?? '' });
const initial = props.program;
const form = useForm({
    name: translatable(initial?.name),
    status: initial?.status ?? 'draft',
    starts_on: initial?.starts_on ?? '',
    ends_on: initial?.ends_on ?? '',
    regions: [...(initial?.regions ?? [])],
    value_chains: [...(initial?.value_chains ?? [])],
    industries: [...(initial?.industries ?? [])],
    business_sizes: [...(initial?.business_sizes ?? [])],
    max_groups: initial?.max_groups ?? 2,
    max_businesses: initial?.max_businesses ?? '',
    priority_category_ids: [...(initial?.priority_category_ids ?? [])],
    eligibility_notes: translatable(initial?.eligibility_notes),
    success_definition: translatable(initial?.success_definition),
    support_model_policy: translatable(initial?.support_model_policy),
    partner_coordination: translatable(initial?.partner_coordination),
});
const firstMonthFields = ['eligibility_notes', 'success_definition', 'support_model_policy', 'partner_coordination'];

const statusOptions = ['draft', 'active', 'closed'].map((s) => ({ value: s, label: t(`pilot.status.${s}`) }));
const provinceOptions = computed(() => page.props.options?.provinces ?? []);
const industryOptions = computed(() => page.props.options?.industries ?? []);
const sizeOptions = computed(() => page.props.options?.sizes ?? []);
const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: c.name })));
const chainKeys = computed(() => Object.keys(props.valueChains ?? {}));
const maxGroups = computed(() => Number(form.max_groups) || 1);

function toggleChain(key) {
    if (form.value_chains.includes(key)) {
        form.value_chains = form.value_chains.filter((c) => c !== key);
    } else if (form.value_chains.length < maxGroups.value) {
        form.value_chains = [...form.value_chains, key];
    }
}
const priorityCategories = computed({
    get: () => form.priority_category_ids,
    set: (ids) => {
        if (ids.length <= 3) {
            form.priority_category_ids = ids;
        }
    },
});
const fieldError = (key) => form.errors[key] ?? Object.entries(form.errors).find(([k]) => k.startsWith(`${key}.`))?.[1];

function saveProgram() {
    form.transform((data) => ({ ...data, max_businesses: data.max_businesses === '' ? null : data.max_businesses, starts_on: data.starts_on || null, ends_on: data.ends_on || null }))
        .put(route('admin.pilot.update'), { preserveScroll: true });
}

const gateForms = reactive({});
watch(() => props.gates, (gates) => {
    for (const g of gates) {
        if (!gateForms[g.id]) {
            gateForms[g.id] = useForm({
                criteria: { ...(g.criteria ?? {}) },
                decision: !g.decision || g.decision === 'pending' ? '' : g.decision,
                root_cause: g.root_cause ?? '',
                notes: g.notes ?? '',
            });
        }
    }
}, { immediate: true });
const isNegative = (decision) => props.negative.includes(decision);
const decisionOptions = (gate) => gate.options.map((d) => ({ value: d, label: t(`gates.decisions.${d}`) }));
const rootCauseOptions = computed(() => props.rootCauses.map((r) => ({ value: r, label: t(`gates.root_causes.${r}`) })));
const criteriaDone = (gate) => Object.values(gateForms[gate.id].criteria).filter(Boolean).length;
const decisionTone = (decision) => (decision === 'pending' ? 'gray' : isNegative(decision) ? 'amber' : 'green');

function decide(gate) {
    gateForms[gate.id]
        .transform((data) => ({ ...data, decision: data.decision || 'pending', root_cause: isNegative(data.decision) ? data.root_cause || null : null, notes: data.notes || null }))
        .post(route('admin.pilot.gates.decide', { gate: gate.id }), { preserveScroll: true });
}

const reportForm = useForm({ week_start: '' });
const generateReport = () => reportForm.transform((data) => (data.week_start ? data : {})).post(route('admin.pilot.reports.generate'));
const reportColumns = [
    { key: 'week', label: t('pilot.reports.week') },
    { key: 'cases', label: t('pilot.reports.cases') },
    { key: 'corrections', label: t('pilot.reports.corrections') },
    { key: 'sla_breaches', label: t('pilot.reports.sla_breaches') },
    { key: 'actions', label: '', class: 'text-end' },
];
</script>

<template>
    <AppLayout :title="$t('pilot.title')" :subtitle="$t('pilot.subtitle')" wide>
        <div class="space-y-8">
            <Card v-if="program" :padded="false" class="overflow-hidden">
                <div class="grid grid-cols-1 gap-6 bg-navy-950 p-5 text-white sm:p-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                    <div class="min-w-0 space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge tone="glass" dot>{{ $t(`pilot.status.${program.status}`) }}</Badge>
                            <span v-if="program.starts_on" class="text-xs text-white/70">{{ $t('pilot.period') }}: {{ date(program.starts_on) }} – {{ program.ends_on ? date(program.ends_on) : '…' }}</span>
                        </div>
                        <h2 class="text-2xl font-semibold tracking-tight break-words">{{ program.name[$page.props.app.locale] || program.name.fa || program.name.en }}</h2>
                        <div class="max-w-md space-y-2">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span>{{ program.current_week ? $t('pilot.week_progress', { n: number(Math.min(program.current_week, TOTAL_WEEKS)), total: number(TOTAL_WEEKS) }) : $t('pilot.not_started') }}</span>
                                <span class="tabular-nums text-white/70">{{ number(Math.round(weekProgress)) }}{{ $page.props.app.locale === 'fa' ? '٪' : '%' }}</span>
                            </div>
                            <div class="rounded-full bg-white/10 p-0.5"><ProgressBar :value="weekProgress" tone="amber" :label="$t('pilot.title')" /></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 self-end">
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs text-white/70">{{ $t('pilot.admitted') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number(program.admitted) }}</p>
                            <p v-if="program.max_businesses" class="mt-1 text-[11px] text-white/60">{{ $t('pilot.capacity', { n: number(program.max_businesses) }) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs text-white/70">{{ $t('pilot.waitlisted') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number(program.waitlisted) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 p-4">
                            <p class="text-xs text-white/70">{{ $t('pilot.ineligible') }}</p>
                            <p class="mt-2 text-2xl font-semibold tabular-nums">{{ number(program.ineligible) }}</p>
                        </div>
                    </div>
                </div>
            </Card>
            <EmptyState v-else icon="flag" :title="$t('pilot.no_program')" :text="canManage ? $t('pilot.no_program_hint') : null" />

            <section v-if="gates.length">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-ink">{{ $t('pilot.gates_title') }}</h2>
                    <p class="text-sm text-gray-500">{{ $t('pilot.gates_subtitle') }}</p>
                </div>
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <Card v-for="gate in gates.filter((g) => gateForms[g.id])" :key="gate.id" :padded="false" class="flex flex-col">
                        <form class="flex h-full flex-col gap-5 p-5 sm:p-6" @submit.prevent="decide(gate)">
                            <header class="flex items-start gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white"><Icon :name="gateIcons[gate.key] ?? 'flag'" :size="18" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-navy-700">{{ $t('gates.week', { n: number(gate.week) }) }}</p>
                                    <h3 class="font-semibold text-ink">{{ $t(`gates.names.${gate.key}`) }}</h3>
                                    <p class="mt-1 text-xs leading-5 text-gray-500">{{ $t(`gates.descriptions.${gate.key}`) }}</p>
                                </div>
                            </header>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 text-gray-600"><Icon name="calendar" :size="14" />{{ gate.due_on ? `${$t('gates.due')}: ${date(gate.due_on)}` : $t('gates.no_due') }}</span>
                                <Badge v-if="gate.overdue" tone="red" dot>{{ $t('gates.overdue') }}</Badge>
                                <Badge :tone="decisionTone(gate.decision)">{{ $t(`gates.decisions.${gate.decision ?? 'pending'}`) }}</Badge>
                            </div>

                            <fieldset :disabled="!canManage" class="min-w-0 flex flex-1 flex-col gap-4">
                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-ink">{{ $t('gates.criteria_title') }}</p>
                                        <span class="text-xs text-gray-500">{{ $t('gates.criteria_progress', { done: number(criteriaDone(gate)), total: number(Object.keys(gateForms[gate.id].criteria).length) }) }}</span>
                                    </div>
                                    <div class="space-y-1 rounded-2xl bg-navy-50/50 p-2">
                                        <Checkbox v-for="(_, key) in gateForms[gate.id].criteria" :key="key" v-model="gateForms[gate.id].criteria[key]" :label="$t(`gates.criteria.${key}`)" />
                                    </div>
                                    <p v-if="gateForms[gate.id].errors.criteria" class="mt-1.5 text-sm text-rose-600">{{ gateForms[gate.id].errors.criteria }}</p>
                                </div>
                                <Field v-model="gateForms[gate.id].decision" as="select" :label="$t('gates.decision')" :options="decisionOptions(gate)" :placeholder="$t('gates.decisions.pending')" :error="gateForms[gate.id].errors.decision" />
                                <Field v-if="isNegative(gateForms[gate.id].decision)" v-model="gateForms[gate.id].root_cause" as="select" :label="$t('gates.root_cause')" :options="rootCauseOptions" :hint="$t('gates.root_cause_hint')" :error="gateForms[gate.id].errors.root_cause" required />
                                <Field v-model="gateForms[gate.id].notes" as="textarea" :rows="3" :label="$t('gates.notes')" :error="gateForms[gate.id].errors.notes" />
                            </fieldset>

                            <div v-if="gate.evidence?.kpis?.length" class="rounded-2xl ring-1 ring-[var(--border)]">
                                <p class="border-b border-[var(--border)] px-4 py-2.5 text-xs font-medium text-gray-600">{{ $t('gates.evidence') }} · {{ $t('gates.evidence_captured', { date: dateTime(gate.evidence.captured_at) }) }}</p>
                                <ul class="divide-y divide-[var(--border)] text-xs">
                                    <li v-for="k in gate.evidence.kpis" :key="k.key" class="flex items-center justify-between gap-3 px-4 py-2">
                                        <span class="min-w-0 truncate text-gray-700" dir="ltr">{{ k.key }}</span>
                                        <span class="shrink-0 tabular-nums">{{ number(k.value) }} / {{ number(k.target) }}</span>
                                        <Badge :tone="k.achieved ? 'green' : 'amber'">{{ k.achieved ? $t('gates.achieved') : $t('gates.missed') }}</Badge>
                                    </li>
                                </ul>
                            </div>

                            <footer class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-[var(--border)] pt-4">
                                <p class="min-w-0 text-xs text-gray-500">
                                    <template v-if="gate.decided_at">{{ $t('gates.decided_by', { name: gate.decided_by ?? '—', date: dateTime(gate.decided_at) }) }}</template>
                                    <template v-else>{{ $t('gates.pending') }}</template>
                                </p>
                                <Button v-if="canManage" type="submit" size="sm" icon="check" :loading="gateForms[gate.id].processing">{{ $t('gates.save') }}</Button>
                            </footer>
                        </form>
                    </Card>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <Card :title="$t('pilot.form.title')" :subtitle="$t('pilot.form.subtitle')">
                    <p v-if="!canManage" class="mb-5 flex items-center gap-2 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800"><Icon name="lock" :size="16" />{{ $t('pilot.read_only') }}</p>
                    <form @submit.prevent="saveProgram">
                        <fieldset :disabled="!canManage" class="grid min-w-0 gap-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field v-model="form.name.fa" :label="$t('pilot.form.name_fa')" required :error="form.errors['name.fa']" />
                                <Field v-model="form.name.en" :label="$t('pilot.form.name_en')" dir="ltr" required :error="form.errors['name.en']" />
                                <Field v-model="form.status" as="select" :label="$t('pilot.form.status')" :options="statusOptions" required :error="form.errors.status" />
                                <div class="hidden sm:block" />
                                <DateTimeField v-model="form.starts_on" mode="date" :label="$t('pilot.form.starts_on')" :error="form.errors.starts_on" />
                                <DateTimeField v-model="form.ends_on" mode="date" :label="$t('pilot.form.ends_on')" :error="form.errors.ends_on" />
                            </div>

                            <div>
                                <p class="label">{{ $t('pilot.form.regions') }}</p>
                                <ChoiceChips v-model="form.regions" multiple :options="provinceOptions" />
                                <p v-if="fieldError('regions')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('regions') }}</p>
                                <p v-else class="mt-1.5 text-xs text-gray-500">{{ $t('pilot.form.regions_hint') }}</p>
                            </div>

                            <div>
                                <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
                                    <p class="label mb-0">{{ $t('pilot.form.value_chains') }}</p>
                                    <span class="text-xs text-gray-500">{{ $t('pilot.form.value_chains_hint', { n: number(maxGroups) }) }} ({{ number(form.value_chains.length) }}/{{ number(maxGroups) }})</span>
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <button
                                        v-for="key in chainKeys"
                                        :key="key"
                                        type="button"
                                        class="rounded-2xl p-4 text-start transition disabled:cursor-not-allowed"
                                        :class="form.value_chains.includes(key) ? 'bg-navy-950 text-white shadow' : 'bg-white ring-1 ring-[var(--border)] hover:bg-navy-50'"
                                        :disabled="!form.value_chains.includes(key) && form.value_chains.length >= maxGroups"
                                        :aria-pressed="form.value_chains.includes(key)"
                                        @click="toggleChain(key)"
                                    >
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="font-medium">{{ $t(`pilot.chains.${key}`) }}</span>
                                            <Icon v-if="form.value_chains.includes(key)" name="check" :size="16" />
                                        </span>
                                        <span class="mt-1 block text-xs leading-5" :class="form.value_chains.includes(key) ? 'text-white/70' : 'text-gray-500'">{{ $t('pilot.form.chain_industries') }}: {{ valueChains[key].map((i) => option('industries', i)).join('، ') }}</span>
                                    </button>
                                </div>
                                <p v-if="fieldError('value_chains')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('value_chains') }}</p>
                            </div>

                            <div>
                                <p class="label">{{ $t('pilot.form.extra_industries') }}</p>
                                <ChoiceChips v-model="form.industries" multiple :options="industryOptions" />
                                <p v-if="fieldError('industries')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('industries') }}</p>
                                <p v-else class="mt-1.5 text-xs text-gray-500">{{ $t('pilot.form.extra_industries_hint') }}</p>
                            </div>

                            <div>
                                <p class="label">{{ $t('pilot.form.business_sizes') }}</p>
                                <ChoiceChips v-model="form.business_sizes" multiple :options="sizeOptions" />
                                <p v-if="fieldError('business_sizes')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('business_sizes') }}</p>
                                <p v-else class="mt-1.5 text-xs text-gray-500">{{ $t('pilot.form.business_sizes_hint') }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Field v-model="form.max_groups" type="number" min="1" max="10" dir="ltr" :label="$t('pilot.form.max_groups')" required :error="form.errors.max_groups" />
                                <Field v-model="form.max_businesses" type="number" min="1" dir="ltr" :label="$t('pilot.form.max_businesses')" :hint="$t('pilot.form.max_businesses_hint')" :error="form.errors.max_businesses" />
                            </div>

                            <div>
                                <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
                                    <p class="label mb-0">{{ $t('pilot.form.priority_categories') }}</p>
                                    <span class="text-xs text-gray-500">{{ number(form.priority_category_ids.length) }}/{{ number(3) }}</span>
                                </div>
                                <ChoiceChips v-model="priorityCategories" multiple :options="categoryOptions" />
                                <p v-if="fieldError('priority_category_ids')" class="mt-1.5 text-sm text-rose-600">{{ fieldError('priority_category_ids') }}</p>
                                <p v-else class="mt-1.5 text-xs text-gray-500">{{ $t('pilot.form.priority_categories_hint') }}</p>
                            </div>

                            <div class="space-y-4 rounded-[var(--radius-card)] bg-navy-50/50 p-4 sm:p-5">
                                <div>
                                    <h4 class="font-semibold text-ink">{{ $t('pilot.form.first_month') }}</h4>
                                    <p class="text-xs text-gray-500">{{ $t('pilot.form.first_month_hint') }}</p>
                                </div>
                                <div v-for="key in firstMonthFields" :key="key" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <Field v-model="form[key].fa" as="textarea" :rows="3" :label="`${$t(`pilot.form.${key}`)} (${$t('pilot.form.in_fa')})`" :error="form.errors[`${key}.fa`]" />
                                    <Field v-model="form[key].en" as="textarea" :rows="3" dir="ltr" :label="`${$t(`pilot.form.${key}`)} (${$t('pilot.form.in_en')})`" :error="form.errors[`${key}.en`]" />
                                </div>
                            </div>

                            <div v-if="canManage" class="flex justify-end">
                                <Button type="submit" icon="check" :loading="form.processing">{{ $t('pilot.form.save') }}</Button>
                            </div>
                        </fieldset>
                    </form>
                </Card>

                <div class="space-y-6">
                    <Card :title="$t('pilot.reports.title')" :subtitle="$t('pilot.reports.subtitle')">
                        <form class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end xl:grid-cols-1 2xl:grid-cols-[minmax(0,1fr)_auto]" @submit.prevent="generateReport">
                            <DateTimeField v-model="reportForm.week_start" mode="date" :label="$t('pilot.reports.week_start')" :hint="$t('pilot.reports.week_start_hint')" :error="reportForm.errors.week_start" />
                            <Button type="submit" icon="refresh" :loading="reportForm.processing" class="sm:mb-6 xl:mb-0 2xl:mb-6">{{ $t('pilot.reports.generate') }}</Button>
                        </form>
                    </Card>
                    <Card :padded="false">
                        <div v-if="!reports.length" class="p-5 sm:p-6"><EmptyState icon="chart" :title="$t('pilot.reports.empty')" :text="$t('pilot.reports.empty_hint')" /></div>
                        <DataTable v-else :columns="reportColumns" :rows="reports">
                            <template #cell-week="{ row }"><span class="whitespace-nowrap">{{ date(row.week_start) }} – {{ date(row.week_end) }}</span></template>
                            <template #cell-cases="{ row }">{{ number(row.cases) }}</template>
                            <template #cell-corrections="{ row }">{{ number(row.corrections) }}</template>
                            <template #cell-sla_breaches="{ row }"><Badge :tone="row.sla_breaches ? 'red' : 'gray'">{{ number(row.sla_breaches) }}</Badge></template>
                            <template #cell-actions="{ row }"><Link :href="route('admin.pilot.reports.show', { report: row.id })" class="font-medium text-navy-700 hover:underline">{{ $t('pilot.reports.view') }}</Link></template>
                        </DataTable>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
