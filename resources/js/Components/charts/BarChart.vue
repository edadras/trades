<script setup>
// Horizontal single-series bar chart: one hue (series-1), thin bars, 4px rounded data end, direct value labels, hover tooltip.
import { computed, ref } from 'vue';
import ChartFrame from './ChartFrame.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ title: String, subtitle: String, data: { type: Array, default: () => [] } });
const { number, t } = useI18n();
const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
const hover = ref(null);
</script>

<template>
    <ChartFrame :title="title" :subtitle="subtitle" :rows="data" :columns="[{ key: 'label', label: t('charts.item') }, { key: 'value', label: t('charts.value') }]">
        <ul class="space-y-3" role="list">
            <li v-for="(d, i) in data" :key="d.label" class="relative" @mouseenter="hover = i" @mouseleave="hover = null" @focusin="hover = i" @focusout="hover = null" tabindex="0">
                <div class="mb-1 flex items-baseline justify-between gap-2 text-sm">
                    <span class="truncate text-gray-700">{{ d.label }}</span>
                    <span class="tabular-nums font-medium text-ink">{{ number(d.value) }}</span>
                </div>
                <div class="h-2.5 w-full rounded-e-[4px] bg-[var(--grid)]/50">
                    <div class="h-full rounded-e-[4px] transition-[width,opacity] duration-700" :style="{ width: `${(d.value / max) * 100}%`, background: 'var(--series-1)', opacity: hover === null || hover === i ? 1 : 0.45 }" />
                </div>
                <div v-if="hover === i" class="pointer-events-none absolute -top-9 end-0 z-10 rounded-xl bg-navy-950 px-3 py-1.5 text-xs text-white shadow-lg">{{ d.label }}: {{ number(d.value) }}</div>
            </li>
        </ul>
    </ChartFrame>
</template>
