<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Logo from '@/Components/ui/Logo.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Drawer from '@/Components/ui/Drawer.vue';
import LocaleSwitch from '@/Components/ui/LocaleSwitch.vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import { useI18n, route } from '@/i18n';

defineProps({ announcement: { type: Boolean, default: true } });
const page = usePage();
const { t } = useI18n();
const user = computed(() => page.props.auth.user);
const menu = ref(false);
const scrolled = ref(false);
const bannerOpen = ref(true);
const onScroll = () => (scrolled.value = window.scrollY > 12);
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

const links = computed(() => [
    { label: t('nav.how'), href: route('how-it-works'), name: 'how-it-works' },
    { label: t('nav.knowledge'), href: route('knowledge.index'), name: 'knowledge.*' },
    { label: t('nav.experts'), href: route('experts.directory'), name: 'experts.directory' },
    { label: t('nav.about'), href: route('about'), name: 'about' },
]);
const active = (name) => route().current(name);
const year = new Date().getFullYear();
</script>

<template>
    <div class="min-h-screen bg-white">
        <div v-if="announcement && bannerOpen" class="relative bg-sky-200/70 px-4 py-2 text-center text-[13px] text-navy-950">
            {{ $t('home.banner') }}
            <Link :href="route('how-it-works')" class="font-medium underline underline-offset-2">{{ $t('home.banner_link') }}</Link>
            <button type="button" class="absolute end-3 top-1/2 -translate-y-1/2 p-1" :aria-label="$t('common.close')" @click="bannerOpen = false"><Icon name="x" :size="16" /></button>
        </div>

        <header class="sticky top-0 z-40 px-3 pt-3 sm:px-6">
            <nav class="mx-auto flex max-w-5xl items-center justify-between gap-3 rounded-[26px] bg-white/95 px-2.5 py-2 ring-1 ring-[var(--border)] backdrop-blur transition-shadow" :class="scrolled ? 'shadow-[var(--shadow-lift)]' : 'shadow-[var(--shadow-soft)]'">
                <Link :href="route('home')" class="ps-1" :aria-label="$t('app.name')"><Logo /></Link>
                <ul class="hidden items-center gap-1 lg:flex">
                    <li v-for="l in links" :key="l.name">
                        <Link :href="l.href" class="rounded-full px-4 py-2 text-sm font-medium transition" :class="active(l.name) ? 'bg-navy-50 text-navy-900' : 'text-gray-600 hover:text-ink'">{{ l.label }}</Link>
                    </li>
                </ul>
                <div class="flex items-center gap-1.5">
                    <span class="hidden sm:block"><LocaleSwitch /></span>
                    <Button v-if="user" :href="route(user.home)" size="md" icon="grid">{{ $t('nav.dashboard') }}</Button>
                    <template v-else>
                        <Link :href="route('login')" class="hidden rounded-full px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 sm:inline-flex">{{ $t('nav.login') }}</Link>
                        <Button :href="route('register')" class="ring-2 ring-navy-950">{{ $t('nav.get_started') }}</Button>
                    </template>
                    <button type="button" class="grid size-11 place-items-center rounded-full bg-gray-100 text-ink lg:hidden" :aria-label="$t('nav.menu')" @click="menu = true"><Icon name="menu" /></button>
                </div>
            </nav>
        </header>

        <Drawer :show="menu" @close="menu = false">
            <template #header><Logo /></template>
            <nav class="flex flex-col gap-1 p-4">
                <Link v-for="l in links" :key="l.name" :href="l.href" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50" @click="menu = false">{{ l.label }}</Link>
                <hr class="my-3 border-[var(--border)]" />
                <Link v-if="!user" :href="route('login')" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.login') }}</Link>
                <Link :href="route('register', { type: 'supporter' })" class="rounded-2xl px-4 py-3 text-[15px] font-medium hover:bg-navy-50">{{ $t('nav.become_expert') }}</Link>
                <LocaleSwitch class="mt-2 w-fit" />
            </nav>
        </Drawer>

        <main><slot /></main>

        <footer class="mt-20 bg-[#1b1c1f] text-white">
            <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8">
                <div class="grid grid-cols-1 gap-12 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)]">
                    <div>
                        <Logo light />
                        <p class="mt-4 max-w-sm text-sm leading-7 text-white/70">{{ $t('footer.about') }}</p>
                        <div class="mt-6 flex items-center gap-2 text-white/70">
                            <a href="mailto:hello@hamyar.example" class="grid size-9 place-items-center rounded-full ring-1 ring-white/15 hover:bg-white/10" aria-label="Email"><Icon name="mail" :size="16" /></a>
                            <a href="https://www.linkedin.com" class="grid size-9 place-items-center rounded-full ring-1 ring-white/15 hover:bg-white/10" aria-label="LinkedIn" rel="noopener"><Icon name="link" :size="16" /></a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-8 sm:grid-cols-3">
                        <div>
                            <p class="mb-4 font-medium">{{ $t('footer.platform') }}</p>
                            <ul class="space-y-3 text-sm text-white/70">
                                <li><Link :href="route('how-it-works')" class="hover:text-white">{{ $t('nav.how') }}</Link></li>
                                <li><Link :href="route('register')" class="hover:text-white">{{ $t('footer.submit_problem') }}</Link></li>
                                <li><Link :href="route('register', { type: 'supporter' })" class="hover:text-white">{{ $t('nav.become_expert') }}</Link></li>
                            </ul>
                        </div>
                        <div>
                            <p class="mb-4 font-medium">{{ $t('footer.resources') }}</p>
                            <ul class="space-y-3 text-sm text-white/70">
                                <li><Link :href="route('knowledge.index')" class="hover:text-white">{{ $t('nav.knowledge') }}</Link></li>
                                <li><Link :href="route('experts.directory')" class="hover:text-white">{{ $t('nav.experts') }}</Link></li>
                                <li><Link :href="route('about')" class="hover:text-white">{{ $t('nav.about') }}</Link></li>
                            </ul>
                        </div>
                        <div>
                            <p class="mb-4 font-medium">{{ $t('footer.legal') }}</p>
                            <ul class="space-y-3 text-sm text-white/70">
                                <li><Link :href="route('privacy')" class="hover:text-white">{{ $t('footer.privacy') }}</Link></li>
                                <li><Link :href="route('terms')" class="hover:text-white">{{ $t('footer.terms') }}</Link></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="mt-12 border-t border-white/10 pt-6 text-center text-sm text-white/60">© {{ year }} {{ $t('app.name') }} — {{ $t('app.tagline') }}</div>
            </div>
        </footer>
        <FlashToast />
    </div>
</template>
