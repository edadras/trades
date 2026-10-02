<script setup>
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Button from '@/Components/ui/Button.vue';
import { route } from '@/i18n';
const props = defineProps({ seo: Object, experts: Object, categories: Array, filters: Object });
const apply = (change) => router.get(route('experts.directory'), { ...props.filters, ...change }, { preserveState: true, preserveScroll: true, replace: true });
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <section class="mx-auto max-w-6xl px-5 pt-14 sm:px-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-semibold tracking-tight text-ink">{{ $t('experts_page.title') }}</h1>
                    <p class="mt-3 max-w-2xl text-gray-500">{{ $t('experts_page.subtitle') }}</p>
                </div>
                <Button :href="route('register', { type: 'supporter' })" icon="users">{{ $t('nav.become_expert') }}</Button>
            </div>
            <div class="scrollbar-none mt-8 flex gap-2 overflow-x-auto">
                <button type="button" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium ring-1" :class="!filters.category ? 'bg-navy-950 text-white ring-navy-950' : 'bg-white ring-[var(--border)]'" @click="apply({ category: undefined })">{{ $t('common.all') }}</button>
                <button v-for="c in categories" :key="c.slug" type="button" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium ring-1" :class="filters.category === c.slug ? 'bg-navy-950 text-white ring-navy-950' : 'bg-white ring-[var(--border)]'" @click="apply({ category: c.slug })">{{ c.name }}</button>
            </div>
            <div class="mt-3 flex gap-2">
                <button v-for="l in ['fa', 'en']" :key="l" type="button" class="chip ring-1" :class="filters.language === l ? 'bg-navy-100 text-navy-900 ring-navy-200' : 'bg-white ring-[var(--border)]'" @click="apply({ language: filters.language === l ? undefined : l })">{{ $t(`languages.${l}`) }}</button>
            </div>
            <div v-if="experts.data.length" class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"><ExpertCard v-for="e in experts.data" :key="e.id" :expert="e" /></div>
            <EmptyState v-else class="mt-8" icon="users" :title="$t('experts_page.empty')" />
            <Pagination :meta="experts" />
        </section>
    </PublicLayout>
</template>
