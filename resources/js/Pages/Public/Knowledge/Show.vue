<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Markdown from '@/Components/domain/Markdown.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ seo: Object, article: Object, related: Array });
const { date, number, locale } = useI18n();
const sections = [['actions', 'check', 'text-emerald-600'], ['causes', 'info', 'text-navy-600'], ['documents', 'file', 'text-sky-600'], ['warnings', 'alert', 'text-amber-600']];
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <article class="mx-auto max-w-3xl px-5 pt-12 sm:px-8">
            <nav class="flex items-center gap-1.5 text-sm text-gray-500" :aria-label="$t('common.breadcrumb')">
                <Link :href="route('knowledge.index')" class="hover:text-ink">{{ $t('nav.knowledge') }}</Link>
                <Icon name="chevron" :size="14" />
                <Link v-if="article.category" :href="route('knowledge.index', { category: article.category.slug })" class="hover:text-ink">{{ article.category.name }}</Link>
            </nav>
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <Badge tone="navy">{{ $t(`content_type.${article.type}`) }}</Badge>
                <Badge tone="green"><Icon name="shield-check" :size="12" />{{ $t('knowledge.approved') }}</Badge>
            </div>
            <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight text-ink sm:text-4xl">{{ article.title }}</h1>
            <p class="mt-4 text-lg leading-8 text-gray-600">{{ article.summary }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-4 border-y border-[var(--border)] py-4 text-sm text-gray-500">
                <span class="inline-flex items-center gap-1.5"><Icon name="calendar" :size="16" />{{ date(article.published_at) }}</span>
                <span class="inline-flex items-center gap-1.5"><Icon name="clock" :size="16" />{{ $t('knowledge.minutes', { n: number(article.reading_minutes) }) }}</span>
                <span v-if="article.valid_until" class="inline-flex items-center gap-1.5"><Icon name="refresh" :size="16" />{{ $t('knowledge.valid_until', { date: date(article.valid_until) }) }}</span>
            </div>
            <p v-if="article.locale !== locale" class="mt-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-800">{{ $t('knowledge.not_translated') }}</p>
            <div v-if="article.video_url" class="mt-6 overflow-hidden rounded-3xl"><a :href="article.video_url" target="_blank" rel="noopener" class="flex items-center gap-3 bg-navy-950 p-5 text-white"><Icon name="video" />{{ $t('knowledge.watch_video') }}</a></div>
            <Markdown :source="article.body" class="mt-6" :dir="article.locale === 'fa' ? 'rtl' : 'ltr'" />

            <div v-if="article.checklist" class="mt-10 grid gap-3 sm:grid-cols-2">
                <template v-for="[key, icon, tone] in sections" :key="key">
                    <div v-if="article.checklist[key]?.length" class="card p-5">
                        <h2 class="font-semibold text-ink">{{ $t(`guidance.${key}`) }}</h2>
                        <ul class="mt-3 space-y-2">
                            <li v-for="(line, i) in article.checklist[key]" :key="i" class="flex items-start gap-2 text-sm leading-6 text-gray-700"><Icon :name="icon" :size="16" class="mt-1" :class="tone" />{{ line }}</li>
                        </ul>
                    </div>
                </template>
            </div>

            <footer class="mt-10 flex flex-wrap items-center gap-2 text-sm text-gray-500">
                <span v-if="article.source">{{ $t('knowledge.source') }}: {{ article.source.name }}</span>
                <Badge v-for="tag in article.tags" :key="tag" tone="gray">#{{ tag }}</Badge>
            </footer>
            <div class="mt-10 rounded-[28px] bg-navy-950 p-6 text-white sm:p-8">
                <h2 class="text-xl font-semibold">{{ $t('knowledge.cta_title') }}</h2>
                <p class="mt-2 text-white/75">{{ $t('knowledge.cta_text') }}</p>
                <Button :href="route('register')" variant="light" class="mt-5">{{ $t('home.cta_submit') }}</Button>
            </div>
        </article>
        <section v-if="related.length" class="mx-auto max-w-6xl px-5 pt-16 sm:px-8">
            <h2 class="text-2xl font-semibold text-ink">{{ $t('knowledge.related') }}</h2>
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><KnowledgeCard v-for="a in related" :key="a.id" :article="a" /></div>
        </section>
    </PublicLayout>
</template>
