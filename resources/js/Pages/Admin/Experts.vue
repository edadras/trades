<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ experts: Object, filters: Object });
const { t } = useI18n();
const tabs = ['', 'submitted', 'in_review', 'verified', 'rejected', 'suspended'].map((k) => ({ key: k, label: k ? t(`expert_status.${k}`) : t('common.all') }));
const setTab = (k) => router.get(route('admin.experts.index'), k ? { status: k } : {}, { preserveState: true, replace: true });
const tone = { draft: 'gray', submitted: 'amber', in_review: 'amber', verified: 'green', rejected: 'red', suspended: 'red' };
</script>

<template>
    <AppLayout :title="$t('nav.experts')" wide>
        <Tabs :tabs="tabs" :model-value="filters.status ?? ''" size="sm" @update:model-value="setTab" />
        <div v-if="experts.data.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <ExpertCard v-for="e in experts.data" :key="e.id" :expert="e">
                <div class="mt-4 flex items-center gap-2 border-t border-[var(--border)] pt-4">
                    <Badge :tone="tone[e.status]">{{ $t(`expert_status.${e.status}`) }}</Badge>
                    <span class="text-xs text-gray-500">{{ $t('admin.active_cases_n', { n: e.active_cases }) }}</span>
                    <Button :href="route('admin.experts.show', { expert: e.id })" size="sm" variant="light" icon="eye" class="ms-auto">{{ $t('common.view') }}</Button>
                </div>
            </ExpertCard>
        </div>
        <EmptyState v-else class="mt-6" icon="users" :title="$t('experts_page.empty')" />
        <Pagination :meta="experts" />
    </AppLayout>
</template>
