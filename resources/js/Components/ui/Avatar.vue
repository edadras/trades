<script setup>
import { computed } from 'vue';
const props = defineProps({ name: String, src: String, size: { type: String, default: 'md' } });
const initials = computed(() => (props.name || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase());
const palette = ['bg-navy-100 text-navy-800', 'bg-sky-100 text-sky-800', 'bg-violet-100 text-violet-800', 'bg-emerald-100 text-emerald-800', 'bg-amber-100 text-amber-800'];
const color = computed(() => palette[[...(props.name || '')].reduce((a, c) => a + c.charCodeAt(0), 0) % palette.length]);
const sizes = { xs: 'size-7 text-[11px]', sm: 'size-9 text-xs', md: 'size-11 text-sm', lg: 'size-14 text-base' };
</script>

<template>
    <img v-if="src" :src="src" :alt="name" class="shrink-0 rounded-full object-cover ring-2 ring-white" :class="sizes[size]" />
    <span v-else class="grid shrink-0 place-items-center rounded-full font-semibold ring-2 ring-white" :class="[sizes[size], color]" :title="name">{{ initials }}</span>
</template>
