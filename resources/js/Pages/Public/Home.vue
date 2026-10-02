<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import Accordion from '@/Components/ui/Accordion.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import NetworkVisual from '@/Components/domain/NetworkVisual.vue';
import Reveal from '@/Components/domain/Reveal.vue';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ seo: Object, categories: Array, experts: Array, articles: Array, knowledge_categories: Array, live: Object, stories: Array });
const { t, number, option } = useI18n();

const steps = ['submit', 'analysis', 'guidance', 'supporter', 'collaborate', 'follow_up', 'result'];
const stepIcons = ['mic', 'sparkles', 'book', 'users', 'chat', 'calendar', 'flag'];
const features = [
    { key: 'intake', icon: 'sparkles' }, { key: 'review', icon: 'shield-check' }, { key: 'matching', icon: 'network' },
    { key: 'workspace', icon: 'lock' }, { key: 'knowledge', icon: 'book' }, { key: 'outcomes', icon: 'target' },
];
const stats = computed(() => [
    { value: props.live.businesses, goal: 30, label: t('home.stat_businesses') },
    { value: props.live.experts, goal: 15, label: t('home.stat_experts') },
    { value: props.live.cases, goal: 50, label: t('home.stat_cases') },
    { value: props.live.review_hours, goal: 48, label: t('home.stat_review'), lower: true },
]);
const faqs = computed(() => ['what', 'cost', 'ai', 'privacy', 'experts', 'time'].map((k) => ({ title: t(`faq.${k}.q`), body: t(`faq.${k}.a`) })));
const storyIndex = ref(0);
const fallbackStories = computed(() => (props.stories?.length ? props.stories : [
    { category: t('home.story_fallback.0.category'), quote: t('home.story_fallback.0.quote'), rating: 5, industry: 'food' },
    { category: t('home.story_fallback.1.category'), quote: t('home.story_fallback.1.quote'), rating: 5, industry: 'agriculture' },
]));
</script>

<template>
    <PublicLayout>
        <SeoHead :seo="seo" />

        <!-- Hero -->
        <section class="-mt-[76px] px-2 sm:px-3">
            <div class="hero-gradient relative overflow-hidden rounded-b-[36px] px-5 pt-32 pb-0 text-center text-white sm:rounded-b-[48px] sm:pt-36">
                <div class="mx-auto max-w-3xl animate-fade-up">
                    <div class="inline-flex items-center gap-3 rounded-full bg-white/10 py-1.5 ps-1.5 pe-4 ring-1 ring-white/20 backdrop-blur">
                        <div class="flex -space-x-2 rtl:space-x-reverse">
                            <Avatar v-for="e in experts.slice(0, 3)" :key="e.id" :name="e.name" size="xs" />
                        </div>
                        <span class="text-xs text-white/90">{{ $t('home.joined', { n: number(Math.max(live.businesses, 1)) }) }}</span>
                    </div>
                    <h1 class="mt-6 text-[34px] font-semibold leading-[1.25] tracking-tight sm:text-5xl lg:text-6xl">{{ $t('home.hero_title') }}</h1>
                    <p class="mx-auto mt-5 max-w-2xl text-[15px] leading-8 text-white/85 sm:text-lg">{{ $t('home.hero_subtitle') }}</p>
                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <Button :href="route('register')" variant="light" size="lg" icon="plus">{{ $t('home.cta_submit') }}</Button>
                        <Button :href="route('how-it-works')" variant="glass" size="lg" icon="play">{{ $t('home.cta_how') }}</Button>
                    </div>
                </div>
                <div class="mx-auto mt-10 max-w-3xl pb-4 sm:mt-14"><NetworkVisual /></div>
            </div>
        </section>

        <!-- Supported domains -->
        <section class="mx-auto max-w-6xl px-5 pt-16 sm:px-8">
            <Reveal><h2 class="text-center text-xl font-medium text-ink">{{ $t('home.domains_title') }}</h2></Reveal>
            <div class="scrollbar-none mt-8 flex gap-3 overflow-x-auto pb-2 sm:flex-wrap sm:justify-center">
                <Reveal v-for="(c, i) in categories" :key="c.slug" :delay="i * 40">
                    <div class="flex shrink-0 items-center gap-2.5 rounded-full bg-white py-2 ps-2 pe-5 ring-1 ring-[var(--border)]">
                        <span class="grid size-9 place-items-center rounded-full bg-navy-950 text-white"><Icon :name="c.icon || 'sparkles'" :size="16" /></span>
                        <span class="text-sm font-medium text-ink">{{ c.name }}</span>
                    </div>
                </Reveal>
            </div>
        </section>

        <!-- How it works -->
        <section class="mx-auto max-w-6xl px-5 pt-20 sm:px-8" id="how">
            <Reveal class="mx-auto max-w-2xl text-center">
                <Badge tone="navy">{{ $t('home.how_badge') }}</Badge>
                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">{{ $t('home.how_title') }}</h2>
                <p class="mt-3 text-gray-500">{{ $t('home.how_subtitle') }}</p>
            </Reveal>
            <ol class="mt-12 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Reveal v-for="(s, i) in steps" :key="s" as="li" :delay="i * 60" :class="i === 0 ? 'lg:row-span-2' : ''">
                    <div class="card card-hover flex h-full flex-col p-5" :class="i === 0 ? 'bg-navy-950 text-white lg:justify-between' : ''">
                        <div class="flex items-center justify-between">
                            <span class="grid size-11 place-items-center rounded-2xl" :class="i === 0 ? 'bg-white text-navy-950' : 'bg-navy-50 text-navy-800'"><Icon :name="stepIcons[i]" /></span>
                            <span class="text-sm font-semibold tabular-nums" :class="i === 0 ? 'text-white/60' : 'text-gray-300'">{{ number(i + 1).padStart(2, number(0)) }}</span>
                        </div>
                        <div class="mt-6">
                            <h3 class="font-semibold" :class="i === 0 ? 'text-xl' : 'text-ink'">{{ $t(`how.${s}.title`) }}</h3>
                            <p class="mt-2 text-sm leading-6" :class="i === 0 ? 'text-white/75' : 'text-gray-500'">{{ $t(`how.${s}.text`) }}</p>
                        </div>
                        <Button v-if="i === 0" :href="route('register')" variant="light" class="mt-8 w-fit">{{ $t('home.cta_submit') }}</Button>
                    </div>
                </Reveal>
            </ol>
        </section>

        <!-- Features bento -->
        <section class="mx-auto max-w-6xl px-5 pt-20 sm:px-8">
            <Reveal class="text-center">
                <h2 class="text-3xl font-semibold tracking-tight text-ink sm:text-4xl">{{ $t('home.features_title') }}</h2>
                <p class="mx-auto mt-3 max-w-2xl text-gray-500">{{ $t('home.features_subtitle') }}</p>
            </Reveal>
            <div class="mt-10 grid gap-3 rounded-[32px] bg-[var(--surface-muted)] p-3 ring-1 ring-[var(--border)] sm:grid-cols-2 lg:grid-cols-3">
                <Reveal v-for="(f, i) in features" :key="f.key" :delay="i * 50">
                    <div class="flex h-full min-h-48 flex-col justify-between rounded-[24px] bg-white p-5 ring-1 ring-[var(--border)]">
                        <span class="grid size-12 place-items-center rounded-2xl bg-navy-900 text-white"><Icon :name="f.icon" /></span>
                        <div class="mt-8">
                            <h3 class="font-semibold text-ink">{{ $t(`features.${f.key}.title`) }}</h3>
                            <p class="mt-1.5 text-sm leading-6 text-gray-500">{{ $t(`features.${f.key}.text`) }}</p>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>

        <!-- Stats -->
        <section class="px-2 pt-20 sm:px-3">
            <div class="section-gradient mx-auto max-w-[1400px] rounded-[36px] px-5 py-16 text-center text-white sm:rounded-[48px] sm:py-20">
                <Reveal class="mx-auto max-w-3xl">
                    <h2 class="text-3xl font-semibold leading-tight tracking-tight sm:text-4xl">{{ $t('home.stats_title') }}</h2>
                    <p class="mt-4 text-white/80">{{ $t('home.stats_subtitle') }}</p>
                    <Button :href="route('register')" variant="light" class="mt-6">{{ $t('nav.get_started') }}</Button>
                </Reveal>
                <div class="mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-y-8 rounded-[28px] bg-white px-4 py-8 text-ink shadow-[var(--shadow-lift)] lg:grid-cols-4">
                    <div v-for="s in stats" :key="s.label" class="px-2">
                        <p class="text-sm text-gray-500">{{ s.label }}</p>
                        <p class="mt-2 text-3xl font-semibold tabular-nums">{{ s.lower ? (s.value === null ? '—' : number(s.value)) : '+' + number(s.value) }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ s.lower ? $t('home.goal_lower', { n: number(s.goal) }) : $t('home.goal', { n: number(s.goal) }) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Experts -->
        <section v-if="experts.length" class="mx-auto max-w-6xl px-5 pt-20 sm:px-8">
            <Reveal class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 class="text-3xl font-semibold tracking-tight text-ink">{{ $t('home.experts_title') }}</h2>
                    <p class="mt-2 max-w-xl text-gray-500">{{ $t('home.experts_subtitle') }}</p>
                </div>
                <Button :href="route('experts.directory')" variant="light" icon="users">{{ $t('common.see_all') }}</Button>
            </Reveal>
            <div class="scrollbar-none -mx-5 mt-8 flex snap-x gap-4 overflow-x-auto px-5 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 lg:grid-cols-3">
                <div v-for="e in experts" :key="e.id" class="w-[85%] shrink-0 snap-start sm:w-auto"><ExpertCard :expert="e" /></div>
            </div>
        </section>

        <!-- Learning centre -->
        <section v-if="articles.length" class="px-2 pt-20 sm:px-3">
            <div class="section-gradient mx-auto max-w-[1400px] rounded-[36px] px-5 py-16 sm:rounded-[48px]">
                <Reveal class="mx-auto max-w-2xl text-center text-white">
                    <Badge tone="glass">{{ $t('home.blog_badge') }}</Badge>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $t('home.blog_title') }}</h2>
                    <p class="mt-3 text-white/80">{{ $t('home.blog_subtitle') }}</p>
                </Reveal>
                <div class="mx-auto mt-10 grid max-w-4xl gap-3">
                    <KnowledgeCard :article="articles[0]" />
                    <KnowledgeCard v-for="a in articles.slice(1)" :key="a.id" :article="a" horizontal />
                </div>
                <div class="mt-8 text-center"><Button :href="route('knowledge.index')" variant="light">{{ $t('home.blog_cta') }}</Button></div>
            </div>
        </section>

        <!-- Success stories -->
        <section class="mx-auto max-w-6xl px-5 pt-20 sm:px-8">
            <Reveal><h2 class="text-3xl font-semibold tracking-tight text-ink">{{ $t('home.stories_title') }}</h2></Reveal>
            <div class="scrollbar-none mt-8 flex snap-x gap-4 overflow-x-auto pb-2" @scroll="storyIndex = Math.round(Math.abs($event.target.scrollLeft) / ($event.target.firstElementChild?.offsetWidth || 1))">
                <article v-for="(s, i) in fallbackStories" :key="i" class="card flex w-[88%] shrink-0 snap-start flex-col p-6 sm:w-[48%]">
                    <div class="flex gap-0.5 text-navy-900"><Icon v-for="n in 5" :key="n" name="star" :size="16" :class="n <= (s.rating || 5) ? 'fill-current' : 'opacity-25'" /></div>
                    <h3 class="mt-4 text-lg font-semibold text-ink">{{ s.category }}</h3>
                    <p class="mt-3 flex-1 leading-7 text-gray-600">«{{ s.quote }}»</p>
                    <div class="mt-6 inline-flex w-fit items-center gap-3 rounded-full bg-navy-50 py-1.5 ps-1.5 pe-4">
                        <Avatar :name="option('industries', s.industry)" size="sm" />
                        <span class="text-sm font-medium">{{ option('industries', s.industry) }}</span>
                    </div>
                </article>
            </div>
            <div class="mt-4 flex justify-center">
                <div class="flex gap-1.5 rounded-full bg-navy-950 px-3 py-2"><span v-for="(s, i) in fallbackStories" :key="i" class="size-1.5 rounded-full" :class="i === storyIndex ? 'bg-white' : 'bg-white/35'" /></div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="mx-auto max-w-3xl px-5 pt-20 sm:px-8">
            <Reveal>
                <h2 class="text-3xl font-semibold tracking-tight text-ink">{{ $t('home.faq_title') }}</h2>
                <p class="mt-2 mb-8 text-gray-500">{{ $t('home.faq_subtitle') }}</p>
                <Accordion :items="faqs" />
            </Reveal>
        </section>

        <!-- Final CTA -->
        <section class="mx-auto max-w-3xl px-5 pt-20 text-center sm:px-8">
            <Reveal>
                <h2 class="text-3xl font-semibold tracking-tight text-ink sm:text-4xl">{{ $t('home.final_title') }}</h2>
                <p class="mx-auto mt-3 max-w-md text-gray-500">{{ $t('home.final_subtitle') }}</p>
                <div class="mt-8 flex flex-wrap justify-center gap-3">
                    <Button :href="route('register')" size="lg" icon="plus">{{ $t('home.cta_submit') }}</Button>
                    <Button :href="route('register', { type: 'supporter' })" variant="light" size="lg" icon="users">{{ $t('nav.become_expert') }}</Button>
                </div>
            </Reveal>
        </section>
    </PublicLayout>
</template>
