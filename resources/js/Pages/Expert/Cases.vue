<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route } from '@/i18n';
defineProps({ cases: Object });
</script>

<template>
    <AppLayout :title="$t('nav.my_cases')">
        <div v-if="cases.data.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3"><CaseCard v-for="c in cases.data" :key="c.id" :item="c" :href="route('expert.cases.show', { case: c.number })" /></div>
        <EmptyState v-else icon="folder" :title="$t('expert_dash.no_cases')" />
        <Pagination :meta="cases" />
    </AppLayout>
</template>
