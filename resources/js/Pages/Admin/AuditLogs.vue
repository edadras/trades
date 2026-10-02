<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Badge from '@/Components/ui/Badge.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ logs: Object, filters: Object });
const { t, dateTime } = useI18n();
const action = ref(props.filters.action ?? '');
let timer;
watch(action, (v) => { clearTimeout(timer); timer = setTimeout(() => router.get(route('admin.audit.index'), v ? { action: v } : {}, { preserveState: true, replace: true }), 300); });
const columns = [{ key: 'when', label: t('table.when') }, { key: 'action', label: t('table.action') }, { key: 'user', label: t('table.user') }, { key: 'subject', label: t('table.subject') }, { key: 'ip', label: 'IP' }];
</script>

<template>
    <AppLayout :title="$t('nav.audit')" :subtitle="$t('audit.subtitle')" wide>
        <Card class="mb-6"><input v-model="action" class="input" dir="ltr" :placeholder="$t('audit.filter')" /></Card>
        <Card :padded="false"><div class="p-2 sm:p-4">
            <DataTable :columns="columns" :rows="logs.data">
                <template #cell-when="{ row }"><span class="text-xs">{{ dateTime(row.created_at) }}</span></template>
                <template #cell-action="{ row }"><Badge :tone="row.action.startsWith('file') ? 'sky' : row.action.startsWith('auth') ? 'gray' : 'navy'"><span dir="ltr">{{ row.action }}</span></Badge></template>
                <template #cell-user="{ row }">{{ row.user?.name ?? $t('audit.system') }}</template>
                <template #cell-subject="{ row }"><span class="font-mono text-xs" dir="ltr">{{ row.subject ?? '—' }}</span></template>
                <template #cell-ip="{ row }"><span class="font-mono text-xs" dir="ltr">{{ row.ip ?? '—' }}</span></template>
            </DataTable>
        </div></Card>
        <Pagination :meta="logs" />
    </AppLayout>
</template>
