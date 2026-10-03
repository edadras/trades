<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Logo from '@/Components/ui/Logo.vue';
import LocaleSwitch from '@/Components/ui/LocaleSwitch.vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Icon from '@/Components/ui/Icon.vue';
import Drawer from '@/Components/ui/Drawer.vue';
import MobileTabBar from '@/Components/ui/MobileTabBar.vue';
import { route, useI18n } from '@/i18n';
defineProps({ title: String, subtitle: String, wide: Boolean });

const { t } = useI18n();
const menu = ref(false);
const links = computed(() => [
    { label: t('nav.home'), href: route('home'), name: 'home' },
    { label: t('nav.how'), href: route('how-it-works'), name: 'how-it-works' },
    { label: t('nav.knowledge'), href: route('knowledge.index'), name: 'knowledge.*' },
    { label: t('nav.experts'), href: route('experts.directory'), name: 'experts.directory' },
    { label: t('nav.about'), href: route('about'), name: 'about' },
]);
const user = computed(() => usePage().props.auth?.user);
const tabs = computed(() => [
    { label: t('nav.home'), icon: 'home', href: route('home'), match: 'home' },
    { label: t('nav.knowledge'), icon: 'book', href: route('knowledge.index'), match: 'knowledge.*' },
    ...(user.value
        ? [{ label: t('nav.dashboard'), icon: 'grid', href: route(user.value.home), cta: true }]
        : [
            { label: t('nav.login'), icon: 'user', href: route('login'), match: 'login*' },
            { label: t('nav.get_started'), icon: 'plus', href: route('register'), match: 'register', cta: true },
        ]),
]);
</script>

<template>
    <div class="grid min-h-screen grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <aside class="hero-gradient relative hidden overflow-hidden p-10 text-white lg:flex lg:flex-col lg:justify-between">
            <Link :href="route('home')"><Logo light /></Link>
            <div class="relative z-10 max-w-md">
                <h2 class="text-4xl font-semibold leading-tight">{{ $t('auth.side_title') }}</h2>
                <ul class="mt-8 space-y-4 text-white/85">
                    <li v-for="k in ['one', 'two', 'three']" :key="k" class="flex items-start gap-3"><span class="grid size-7 shrink-0 place-items-center rounded-full bg-white/15"><Icon name="check" :size="14" /></span>{{ $t(`auth.side_${k}`) }}</li>
                </ul>
            </div>
            <p class="text-sm text-white/60">{{ $t('app.tagline') }}</p>
            <div class="pointer-events-none absolute -bottom-24 -end-24 size-96 rounded-full bg-white/10 blur-3xl" />
        </aside>
        <main class="flex min-w-0 flex-col px-4 pb-28 pt-4 sm:px-10 sm:pt-6 lg:pb-6">
            <div class="flex items-center justify-between gap-2">
                <Link :href="route('home')" class="lg:invisible"><Logo /></Link>
                <div class="flex items-center gap-1.5">
                    <LocaleSwitch />
                    <button type="button" class="grid size-11 place-items-center rounded-full bg-gray-100 text-ink lg:hidden" :aria-label="$t('nav.menu')" :aria-expanded="menu" @click="menu = true"><Icon name="menu" /></button>
                </div>
            </div>
            <div class="mx-auto flex w-full min-w-0 flex-1 flex-col justify-center py-8 sm:py-10" :class="wide ? 'max-w-2xl' : 'max-w-md'">
                <h1 class="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ title }}</h1>
                <p v-if="subtitle" class="mt-2 text-gray-500">{{ subtitle }}</p>
                <div class="mt-8"><slot /></div>
            </div>
        </main>

        <Drawer :show="menu" @close="menu = false">
            <template #header><Logo /></template>
            <nav class="flex flex-col gap-1 p-4">
                <Link v-for="l in links" :key="l.name" :href="l.href" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50" :class="route().current(l.name) ? 'bg-navy-50 text-navy-900' : ''">{{ l.label }}</Link>
                <hr class="my-3 border-[var(--border)]" />
                <Link v-if="user" :href="route(user.home)" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.dashboard') }}</Link>
                <Link v-if="!user" :href="route('login')" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.login') }}</Link>
                <Link v-if="!user" :href="route('register')" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.get_started') }}</Link>
                <Link v-if="!user" :href="route('register', { type: 'supporter' })" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.become_expert') }}</Link>
            </nav>
        </Drawer>
        <MobileTabBar :items="tabs" :more-label="$t('nav.menu')" :more-open="menu" @more="menu = true" />
        <FlashToast />
    </div>
</template>
