<script setup>
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import StatusBadge from '@/Components/domain/StatusBadge.vue';
import UrgencyBadge from '@/Components/domain/UrgencyBadge.vue';
import ProvenanceBadge from '@/Components/domain/ProvenanceBadge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ cases: Object, filters: Object, categories: Array });
const { t, relative } = useI18n();
const f = reactive({ q: props.filters.q ?? '', status: props.filters.status ?? '', category: props.filters.category ?? '', urgency: props.filters.urgency ?? '', mine: !!props.filters.mine });
let timer;
watch(f, () => { clearTimeout(timer); timer = setTimeout(() => router.get(route('review.cases.index'), Object.fromEntries(Object.entries({ ...f, mine: f.mine ? 1 : '' }).filter(([, v]) => v !== '' && v !== null)), { preserveState: true, replace: true }), 300); });
const statuses = ['submitted', 'ai_processing', 'human_review', 'ready', 'matching', 'expert_proposed', 'accepted', 'in_progress', 'waiting', 'resolved', 'closed'];
const columns = [
    { key: 'number', label: t('table.number') }, { key: 'title', label: t('table.title') }, { key: 'business', label: t('table.business') },
    { key: 'category', label: t('table.category') }, { key: 'status', label: t('table.status') }, { key: 'updated', label: t('table.updated') },
];
</script>

<template>
    <AppLayout :title="$t('nav.all_cases')" wide>
        <Card class="mb-6">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <input v-model="f.q" type="search" class="input" :placeholder="$t('table.search')" />
                <select v-model="f.status" class="input"><option value="">{{ $t('table.any_status') }}</option><option v-for="s in statuses" :key="s" :value="s">{{ $t(`status.${s}`) }}</option></select>
                <select v-model="f.category" class="input"><option value="">{{ $t('table.any_category') }}</option><option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option></select>
                <select v-model="f.urgency" class="input"><option value="">{{ $t('table.any_urgency') }}</option><option v-for="u in ['low', 'medium', 'high', 'critical']" :key="u" :value="u">{{ $t(`urgency.${u}`) }}</option></select>
                <Checkbox v-model="f.mine" :label="$t('table.mine')" class="self-center" />
            </div>
        </Card>
        <Card :padded="false">
            <div class="p-2 sm:p-4">
                <DataTable :columns="columns" :rows="cases.data">
                    <template #cell-number="{ row }"><Link :href="route('review.cases.show', { case: row.number })" class="font-mono text-xs text-navy-700 hover:underline" dir="ltr">{{ row.number }}</Link></template>
                    <template #cell-title="{ row }"><Link :href="route('review.cases.show', { case: row.number })" class="line-clamp-1 font-medium hover:underline">{{ row.title }}</Link></template>
                    <template #cell-business="{ row }">{{ row.business?.name }}</template>
                    <template #cell-category="{ row }"><span class="flex flex-wrap items-center gap-1">{{ row.category?.name ?? '—' }}<ProvenanceBadge :state="row.classification_source" /></span></template>
                    <template #cell-status="{ row }"><span class="flex flex-wrap gap-1"><StatusBadge :status="row.status" /><UrgencyBadge :urgency="row.urgency" /></span></template>
                    <template #cell-updated="{ row }"><span class="text-xs text-gray-500">{{ relative(row.updated_at) }}</span></template>
                </DataTable>
            </div>
        </Card>
        <Pagination :meta="cases" />
    </AppLayout>
</template>
