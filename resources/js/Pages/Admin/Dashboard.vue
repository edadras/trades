<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import KpiCard from '@/Components/domain/KpiCard.vue';
import BarChart from '@/Components/charts/BarChart.vue';
import DonutChart from '@/Components/charts/DonutChart.vue';
import FunnelChart from '@/Components/charts/FunnelChart.vue';
import LineChart from '@/Components/charts/LineChart.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ summary: Object, kpis: Array, charts: Object, experts: Array });
const { t, number, percent, locale } = useI18n();
const pct = computed(() => (locale.value === 'fa' ? '٪' : '%'));
const aiData = computed(() => [
    { label: t('admin.ai_agreed'), value: props.charts.aiVsHuman.agreed },
    { label: t('admin.ai_category_changed'), value: props.charts.aiVsHuman.category_changed },
    { label: t('admin.ai_urgency_changed'), value: props.charts.aiVsHuman.urgency_changed },
]);
const page = usePage();
const canViewPilot = computed(() => (page.props.auth?.user?.permissions ?? []).some((p) => p === 'pilot.manage' || p === 'reports.view'));
const expertCols = [{ key: 'name', label: t('table.expert') }, { key: 'cases', label: t('table.cases') }, { key: 'successful', label: t('table.successful') }, { key: 'rating', label: t('table.rating') }];
</script>

<template>
    <AppLayout :title="$t('nav.admin_dashboard')" wide>
        <template #header-actions>
            <span class="hidden sm:block"><Button v-if="canViewPilot" :href="route('admin.pilot.show')" size="sm" variant="light" icon="flag">{{ $t('dashboard2.pilot_link') }}</Button></span>
            <span class="hidden sm:block"><Button :href="route('admin.kpis.index')" size="sm" variant="light" icon="target">{{ $t('nav.kpis') }}</Button></span>
        </template>
        <section class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <StatCard :label="$t('admin.businesses')" :value="summary.businesses" icon="briefcase" />
            <StatCard :label="$t('admin.cases')" :value="summary.cases" icon="folder" tone="light" />
            <StatCard :label="$t('admin.open_cases')" :value="summary.open_cases" icon="inbox" tone="amber" />
            <StatCard :label="$t('admin.experts')" :value="summary.experts" icon="users" tone="emerald" :hint="summary.pending_experts ? $t('admin.pending_experts', { n: number(summary.pending_experts) }) : null" />
            <StatCard :label="$t('admin.review_queue')" :value="summary.review_queue" icon="shield-check" tone="rose" />
            <StatCard :label="$t('admin.ai_accuracy')" :value="summary.ai_accuracy" :suffix="pct" icon="sparkles" tone="light" />
            <StatCard :label="$t('admin.avg_response')" :value="summary.avg_response_hours" :suffix="$t('kpi.hours')" icon="clock" tone="light" />
            <StatCard :label="$t('admin.match_acceptance')" :value="summary.match_acceptance" :suffix="pct" icon="network" tone="light" />
            <StatCard :label="$t('admin.resolution_rate')" :value="summary.resolution_rate" :suffix="pct" icon="flag" tone="emerald" />
            <StatCard :label="$t('admin.satisfaction')" :value="summary.satisfaction" suffix="/5" icon="star" tone="amber" />
        </section>
        <section class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4">
            <StatCard :label="$t('dashboard2.experts_abroad')" :value="summary.experts_abroad" icon="globe" tone="light" />
            <StatCard :label="$t('dashboard2.pending_outcomes')" :value="summary.pending_outcomes" icon="flag" tone="amber" />
            <StatCard :label="$t('dashboard2.legal_queue')" :value="summary.legal_queue" icon="scale" tone="light" />
            <StatCard :label="$t('dashboard2.open_complaints')" :value="summary.open_complaints" icon="megaphone" tone="rose" />
        </section>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-ink">{{ $t('admin.kpi_title') }}</h2>
                <Link :href="route('admin.kpis.index')" class="text-sm font-medium text-navy-700 hover:underline">{{ $t('admin.manage_targets') }}</Link>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3"><KpiCard v-for="k in kpis" :key="k.id" :kpi="k" /></div>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-4 xl:grid-cols-2">
            <LineChart :title="$t('admin.chart_over_time')" :subtitle="$t('admin.chart_weeks')" :data="charts.overTime" :series="[{ key: 'submitted', label: $t('admin.submitted') }, { key: 'resolved', label: $t('admin.resolved') }]" />
            <FunnelChart :title="$t('admin.chart_funnel')" :data="charts.funnel" />
            <BarChart :title="$t('admin.chart_category')" :data="charts.byCategory" />
            <DonutChart :title="$t('admin.chart_ai_human')" :subtitle="$t('admin.chart_ai_human_hint')" :data="aiData" :center="summary.ai_accuracy !== null ? percent(summary.ai_accuracy) : '—'" />
            <BarChart :title="$t('admin.chart_region')" :data="charts.byRegion" />
            <BarChart :title="$t('admin.chart_industry')" :data="charts.byIndustry" />
            <BarChart :title="$t('dashboard2.chart_source')" :subtitle="$t('dashboard2.chart_source_hint')" :data="charts.bySource ?? []" />
            <BarChart :title="$t('dashboard2.chart_dissatisfaction')" :subtitle="$t('dashboard2.chart_dissatisfaction_hint')" :data="charts.dissatisfaction ?? []" />
        </section>

        <Card class="mt-8" :title="$t('admin.expert_performance')">
            <DataTable :columns="expertCols" :rows="experts">
                <template #cell-name="{ row }"><Link :href="route('admin.experts.show', { expert: row.id })" class="font-medium hover:underline">{{ row.name }}</Link></template>
                <template #cell-cases="{ row }">{{ number(row.cases) }}</template>
                <template #cell-successful="{ row }">{{ number(row.successful) }}</template>
                <template #cell-rating="{ row }">{{ row.rating ? number(row.rating) + ' / ' + number(5) : '—' }}</template>
            </DataTable>
        </Card>
    </AppLayout>
</template>
