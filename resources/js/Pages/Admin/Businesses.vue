<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Badge from '@/Components/ui/Badge.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ businesses: Object, filters: Object });
const { t, option, number, date } = useI18n();
const q = ref(props.filters.q ?? '');
let timer;
watch(q, (v) => { clearTimeout(timer); timer = setTimeout(() => router.get(route('admin.businesses.index'), v ? { q: v } : {}, { preserveState: true, replace: true }), 300); });
const columns = [{ key: 'name', label: t('table.name') }, { key: 'industry', label: t('fields.industry') }, { key: 'region', label: t('fields.province') }, { key: 'cases', label: t('table.cases') }, { key: 'onboarded', label: t('table.status') }, { key: 'created', label: t('table.joined') }];
</script>

<template>
    <AppLayout :title="$t('nav.businesses')" wide>
        <Card class="mb-6"><input v-model="q" type="search" class="input" :placeholder="$t('table.search')" /></Card>
        <Card :padded="false"><div class="p-2 sm:p-4">
            <DataTable :columns="columns" :rows="businesses.data">
                <template #cell-name="{ row }"><Link :href="route('admin.businesses.show', { business: row.id })" class="font-medium hover:underline">{{ row.name }}</Link><p class="text-xs text-gray-500">{{ row.owner?.name }}</p></template>
                <template #cell-industry="{ row }">{{ option('industries', row.industry) || '—' }}</template>
                <template #cell-region="{ row }">{{ option('provinces', row.province) || option('countries', row.country) }}</template>
                <template #cell-cases="{ row }">{{ number(row.cases) }}</template>
                <template #cell-onboarded="{ row }"><Badge :tone="row.onboarded ? 'green' : 'amber'">{{ row.onboarded ? $t('admin.onboarded') : $t('admin.onboarding') }}</Badge></template>
                <template #cell-created="{ row }"><span class="text-xs text-gray-500">{{ date(row.created_at) }}</span></template>
            </DataTable>
        </div></Card>
        <Pagination :meta="businesses" />
    </AppLayout>
</template>
