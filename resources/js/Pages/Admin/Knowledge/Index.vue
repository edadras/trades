<script setup>
import { reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ articles: Object, filters: Object, statuses: Array });
const { t, relative, number } = useI18n();
const f = reactive({ q: props.filters.q ?? '', status: props.filters.status ?? '' });
let timer;
watch(f, () => { clearTimeout(timer); timer = setTimeout(() => router.get(route('admin.knowledge.index'), Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, replace: true }), 300); });
const tone = { draft: 'gray', in_review: 'amber', approved: 'green', archived: 'gray' };
const columns = [{ key: 'title', label: t('table.title') }, { key: 'type', label: t('table.type') }, { key: 'status', label: t('table.status') }, { key: 'locales', label: t('table.languages') }, { key: 'views', label: t('table.views') }, { key: 'updated', label: t('table.updated') }];
</script>

<template>
    <AppLayout :title="$t('nav.knowledge_admin')" :subtitle="$t('knowledge_admin.subtitle')" wide>
        <template #header-actions><Button v-if="$page.props.auth.user?.permissions?.includes('knowledge.manage')" :href="route('admin.knowledge.create')" size="sm" icon="plus">{{ $t('knowledge_admin.new') }}</Button></template>
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <Tabs :tabs="[{ key: '', label: $t('common.all') }, ...statuses.map((s) => ({ key: s, label: $t(`content_status.${s}`) }))]" v-model="f.status" size="sm" />
            <input v-model="f.q" type="search" class="input max-w-xs py-2" :placeholder="$t('table.search')" />
        </div>
        <Card :padded="false"><div class="p-2 sm:p-4">
            <DataTable :columns="columns" :rows="articles.data">
                <template #cell-title="{ row }"><Link :href="route('admin.knowledge.edit', { article: row.id })" class="font-medium hover:underline">{{ row.title }}</Link><p class="text-xs text-gray-500">{{ row.category?.name }}</p></template>
                <template #cell-type="{ row }">{{ $t(`content_type.${row.type}`) }}</template>
                <template #cell-status="{ row }"><span class="flex gap-1"><Badge :tone="tone[row.status]">{{ $t(`content_status.${row.status}`) }}</Badge><Badge v-if="row.expired" tone="red">{{ $t('knowledge_admin.expired') }}</Badge></span></template>
                <template #cell-locales="{ row }"><span class="flex gap-1"><Badge v-for="l in row.locales" :key="l" tone="navy">{{ l.toUpperCase() }}</Badge></span></template>
                <template #cell-views="{ row }">{{ number(row.views) }}</template>
                <template #cell-updated="{ row }"><span class="text-xs text-gray-500">{{ relative(row.updated_at) }}</span></template>
            </DataTable>
        </div></Card>
        <Pagination :meta="articles" />
    </AppLayout>
</template>
