<script setup>
defineProps({ value: { type: Number, default: 0 }, tone: { type: String, default: 'navy' }, label: String });
const tones = { navy: 'bg-navy-600', green: 'bg-emerald-500', amber: 'bg-amber-500' };
</script>

<template>
    <div class="h-2 w-full overflow-hidden rounded-full bg-navy-100" role="progressbar" :aria-valuenow="value" aria-valuemin="0" aria-valuemax="100" :aria-label="label">
        <div class="h-full rounded-full transition-[width] duration-700 ease-out" :class="tones[tone]" :style="{ width: `${Math.max(2, Math.min(100, value))}%` }" />
    </div>
</template>
