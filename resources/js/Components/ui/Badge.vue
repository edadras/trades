<script setup>
defineProps({ tone: { type: String, default: 'gray' }, dot: Boolean });
const tones = {
    gray: 'bg-gray-100 text-gray-700',
    navy: 'bg-navy-100 text-navy-800',
    dark: 'bg-navy-950 text-white',
    green: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
    red: 'bg-rose-50 text-rose-700 ring-1 ring-rose-200',
    violet: 'bg-violet-50 text-violet-700 ring-1 ring-violet-200',
    sky: 'bg-sky-50 text-sky-700 ring-1 ring-sky-200',
    glass: 'bg-white/15 text-white ring-1 ring-white/25',
};
</script>

<template>
    <span class="chip" :class="tones[tone] ?? tones.gray">
        <span v-if="dot" class="size-1.5 rounded-full bg-current" />
        <slot />
    </span>
</template>
