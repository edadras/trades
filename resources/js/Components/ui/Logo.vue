<script setup>
import { useI18n } from '@/i18n';
defineProps({ light: Boolean, compact: Boolean });
const { locale } = useI18n();
</script>

<template>
    <span class="inline-flex items-center gap-2.5">
        <span class="grid size-10 place-items-center rounded-full" :class="light ? 'bg-white text-navy-950' : 'bg-navy-950 text-white'">
            <svg viewBox="0 0 24 24" class="size-5" fill="none" aria-hidden="true">
                <path d="M7 8l10 0-5 9z" stroke="currentColor" stroke-width="1.6" opacity=".55" />
                <circle cx="7" cy="8" r="2.4" fill="currentColor" />
                <circle cx="17" cy="8" r="2.4" fill="currentColor" />
                <circle cx="12" cy="17" r="2.4" fill="#8fb4ff" />
            </svg>
        </span>
        <span v-if="!compact" class="text-xl font-semibold tracking-tight" :class="light ? 'text-white' : 'text-ink'">{{ locale === 'fa' ? 'همیار' : 'Hamyar' }}</span>
    </span>
</template>
