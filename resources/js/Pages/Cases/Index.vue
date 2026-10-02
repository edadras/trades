<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Button from '@/Components/ui/Button.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ cases: Object, filters: Object });
const { t } = useI18n();
const tabs = ['all', 'open', 'draft', 'closed'].map((k) => ({ key: k, label: t(`cases_list.${k}`) }));
const setTab = (k) => router.get(route('cases.index'), { status: k }, { preserveState: true, replace: true });
</script>

<template>
    <AppLayout :title="$t('nav.cases')">
        <template #header-actions><span class="hidden sm:block"><Button :href="route('cases.create')" size="sm" icon="plus">{{ $t('dashboard.new_problem') }}</Button></span></template>
        <Tabs :tabs="tabs" :model-value="filters.status" @update:model-value="setTab" />
        <div v-if="cases.data.length" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <CaseCard v-for="c in cases.data" :key="c.id" :item="c" :href="c.status === 'draft' ? route('cases.create', { case: c.number }) : route('cases.show', { case: c.number })" />
        </div>
        <EmptyState v-else class="mt-6" icon="folder" :title="$t('dashboard.no_cases')" :text="$t('dashboard.no_cases_hint')">
            <Button :href="route('cases.create')" icon="plus">{{ $t('dashboard.new_problem') }}</Button>
        </EmptyState>
        <Pagination :meta="cases" />
    </AppLayout>
</template>
