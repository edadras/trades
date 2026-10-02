<script setup>
// Part-to-whole for ≤3 categories (validated all-pairs slots 1–3), 2px surface gaps, legend with values.
import { computed, ref } from 'vue';
import ChartFrame from './ChartFrame.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ title: String, subtitle: String, data: { type: Array, default: () => [] }, center: String });
const { number, percent, t } = useI18n();
const total = computed(() => props.data.reduce((a, d) => a + d.value, 0));
const R = 70, C = 2 * Math.PI * R;
const arcs = computed(() => {
    let offset = 0;
    return props.data.map((d, i) => {
        const len = total.value ? (d.value / total.value) * C : 0;
        const arc = { ...d, i, dash: `${Math.max(0, len - 2)} ${C}`, offset: -offset };
        offset += len;
        return arc;
    });
});
const hover = ref(null);
</script>

<template>
    <ChartFrame :title="title" :subtitle="subtitle" :rows="total ? data : []" :columns="[{ key: 'label', label: t('charts.item') }, { key: 'value', label: t('charts.value') }]">
        <div class="flex flex-col items-center gap-6 sm:flex-row">
            <svg viewBox="0 0 180 180" class="size-44 shrink-0 -rotate-90" role="img" :aria-label="title">
                <circle cx="90" cy="90" :r="R" fill="none" stroke="var(--grid)" stroke-width="18" />
                <circle v-for="a in arcs" :key="a.label" cx="90" cy="90" :r="R" fill="none" :stroke="`var(--series-${a.i + 1})`" stroke-width="18" :stroke-dasharray="a.dash" :stroke-dashoffset="a.offset" :opacity="hover === null || hover === a.i ? 1 : 0.4" class="transition-opacity" @mouseenter="hover = a.i" @mouseleave="hover = null" />
                <text x="90" y="90" class="rotate-90" transform-origin="90 90" text-anchor="middle" dominant-baseline="central" font-size="22" font-weight="600" fill="var(--text-primary)">{{ center ?? number(total) }}</text>
            </svg>
            <ul class="w-full space-y-2.5">
                <li v-for="a in arcs" :key="a.label" class="flex items-center justify-between gap-3 rounded-xl px-2 py-1 text-sm" :class="hover === a.i ? 'bg-navy-50' : ''" @mouseenter="hover = a.i" @mouseleave="hover = null">
                    <span class="inline-flex items-center gap-2 text-gray-700"><span class="size-2.5 rounded-sm" :style="{ background: `var(--series-${a.i + 1})` }" />{{ a.label }}</span>
                    <span class="tabular-nums text-ink">{{ number(a.value) }} <span class="text-gray-400">({{ percent(total ? Math.round((a.value / total) * 100) : 0) }})</span></span>
                </li>
            </ul>
        </div>
    </ChartFrame>
</template>
