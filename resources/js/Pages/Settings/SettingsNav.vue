<script setup>
import { Link } from '@inertiajs/vue3';
import { route } from '@/i18n';
defineProps({ current: String });
const items = [['profile', 'settings.profile'], ['security', 'settings.security'], ['notifications', 'settings.notifications'], ['privacy', 'settings.privacy']];
</script>

<template>
    <nav class="scrollbar-none mb-6 flex gap-1 overflow-x-auto rounded-full bg-white p-1 ring-1 ring-[var(--border)] sm:w-fit">
        <Link v-for="[key] in items" :key="key" :href="route(`settings.${key}`)" class="shrink-0 rounded-full px-4 py-2 text-sm font-medium" :class="current === key ? 'bg-navy-950 text-white' : 'text-gray-600 hover:bg-navy-50'">{{ $t(`settings.tab_${key}`) }}</Link>
    </nav>
</template>
