<script setup>
import Icon from './Icon.vue';
defineProps({ icon: { type: String, default: 'inbox' }, title: String, text: String });
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 rounded-[var(--radius-card)] border border-dashed border-navy-200 bg-navy-50/40 px-6 py-12 text-center">
        <span class="grid size-14 place-items-center rounded-2xl bg-white text-navy-700 shadow-[var(--shadow-soft)]"><Icon :name="icon" :size="24" /></span>
        <p class="font-semibold text-ink">{{ title }}</p>
        <p v-if="text" class="max-w-sm text-sm text-gray-500">{{ text }}</p>
        <div v-if="$slots.default" class="mt-2"><slot /></div>
    </div>
</template>
