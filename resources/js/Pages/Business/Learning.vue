<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import { route } from '@/i18n';
defineProps({ fromCases: Array, byIndustry: Array });
</script>

<template>
    <AppLayout :title="$t('nav.learning')" :subtitle="$t('learning.subtitle')">
        <section>
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ $t('learning.for_cases') }}</h2>
            <div v-if="fromCases.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div v-for="a in fromCases" :key="a.id" class="relative">
                    <KnowledgeCard :article="a" />
                    <Badge tone="dark" class="absolute end-4 top-4"><span dir="ltr">{{ a.case_number }}</span></Badge>
                </div>
            </div>
            <EmptyState v-else icon="book" :title="$t('learning.empty')" :text="$t('learning.empty_hint')"><Button :href="route('cases.create')" icon="plus">{{ $t('dashboard.new_problem') }}</Button></EmptyState>
        </section>
        <section v-if="byIndustry.length" class="mt-10">
            <h2 class="mb-4 text-lg font-semibold text-ink">{{ $t('learning.for_industry') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"><KnowledgeCard v-for="a in byIndustry" :key="a.id" :article="a" /></div>
        </section>
    </AppLayout>
</template>
