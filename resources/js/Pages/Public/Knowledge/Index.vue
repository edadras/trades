<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import { route } from '@/i18n';

const props = defineProps({ seo: Object, articles: Object, categories: Array, types: Array, filters: Object });
const q = ref(props.filters.q ?? '');
let timer;
watch(q, (v) => { clearTimeout(timer); timer = setTimeout(() => apply({ q: v || undefined }), 350); });
function apply(change) {
    router.get(route('knowledge.index'), { ...props.filters, ...change, page: undefined }, { preserveState: true, preserveScroll: true, replace: true });
}
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <section class="px-2 sm:px-3">
            <div class="section-gradient mx-auto mt-6 max-w-[1400px] rounded-[36px] px-5 pt-16 pb-24 text-center text-white sm:rounded-[48px]">
                <Badge tone="glass">{{ $t('home.blog_badge') }}</Badge>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl">{{ $t('knowledge.title') }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-white/85">{{ $t('knowledge.subtitle') }}</p>
                <label class="mx-auto mt-8 flex max-w-xl items-center gap-3 rounded-full bg-white px-5 py-3 text-ink shadow-[var(--shadow-lift)]">
                    <Icon name="search" class="text-gray-400" />
                    <input v-model="q" type="search" class="w-full bg-transparent text-[15px] outline-none" :placeholder="$t('knowledge.search')" />
                </label>
            </div>
        </section>
        <section class="mx-auto -mt-10 max-w-6xl px-5 sm:px-8">
            <div class="scrollbar-none flex gap-2 overflow-x-auto rounded-full bg-white p-1.5 shadow-[var(--shadow-soft)] ring-1 ring-[var(--border)]">
                <button type="button" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="!filters.category ? 'bg-navy-950 text-white' : 'text-gray-600 hover:bg-navy-50'" @click="apply({ category: undefined })">{{ $t('common.all') }}</button>
                <button v-for="c in categories" :key="c.slug" type="button" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="filters.category === c.slug ? 'bg-navy-950 text-white' : 'text-gray-600 hover:bg-navy-50'" @click="apply({ category: c.slug })">
                    {{ c.name }} <span class="opacity-60">{{ c.count }}</span>
                </button>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button v-for="tp in types" :key="tp" type="button" class="chip ring-1" :class="filters.type === tp ? 'bg-navy-100 text-navy-900 ring-navy-200' : 'bg-white text-gray-600 ring-[var(--border)]'" @click="apply({ type: filters.type === tp ? undefined : tp })">{{ $t(`content_type.${tp}`) }}</button>
            </div>
            <div v-if="articles.data.length" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <KnowledgeCard v-for="a in articles.data" :key="a.id" :article="a" />
            </div>
            <EmptyState v-else class="mt-8" icon="book" :title="$t('knowledge.empty')" />
            <Pagination :meta="articles" />
        </section>
    </PublicLayout>
</template>
