<script setup>
import { Link } from '@inertiajs/vue3';
import Logo from '@/Components/ui/Logo.vue';
import LocaleSwitch from '@/Components/ui/LocaleSwitch.vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route } from '@/i18n';
defineProps({ title: String, subtitle: String, wide: Boolean });
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
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
        <main class="flex flex-col px-5 py-6 sm:px-10">
            <div class="flex items-center justify-between">
                <Link :href="route('home')" class="lg:invisible"><Logo /></Link>
                <LocaleSwitch />
            </div>
            <div class="mx-auto flex w-full flex-1 flex-col justify-center py-10" :class="wide ? 'max-w-2xl' : 'max-w-md'">
                <h1 class="text-3xl font-semibold tracking-tight text-ink">{{ title }}</h1>
                <p v-if="subtitle" class="mt-2 text-gray-500">{{ subtitle }}</p>
                <div class="mt-8"><slot /></div>
            </div>
        </main>
        <FlashToast />
    </div>
</template>
