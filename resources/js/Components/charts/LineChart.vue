<script setup>
// Two-series line chart over time (series-1 blue, series-2 orange), 2px lines, legend + end labels, crosshair tooltip.
import { computed, ref } from 'vue';
import ChartFrame from './ChartFrame.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ title: String, subtitle: String, data: { type: Array, default: () => [] }, series: { type: Array, required: true } });
const { number, isRtl } = useI18n();
const W = 600, H = 220, P = { t: 16, r: 40, b: 26, l: 28 };
const max = computed(() => Math.max(1, ...props.data.flatMap((d) => props.series.map((s) => d[s.key] ?? 0))));
const x = (i) => P.l + (i * (W - P.l - P.r)) / Math.max(1, props.data.length - 1);
const y = (v) => H - P.b - (v / max.value) * (H - P.t - P.b);
const path = (key) => props.data.map((d, i) => `${i ? 'L' : 'M'}${x(i)},${y(d[key] ?? 0)}`).join(' ');
const ticks = computed(() => [0, Math.round(max.value / 2), max.value]);
const hover = ref(null);
function move(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    let px = ((e.clientX - rect.left) / rect.width) * W;
    if (isRtl.value) px = W - px;
    const i = Math.round(((px - P.l) / (W - P.l - P.r)) * (props.data.length - 1));
    hover.value = Math.max(0, Math.min(props.data.length - 1, i));
}
const total = computed(() => props.data.reduce((a, d) => a + props.series.reduce((b, s) => b + (d[s.key] ?? 0), 0), 0));
</script>

<template>
    <ChartFrame :title="title" :subtitle="subtitle" :rows="total ? data : []" :columns="[{ key: 'label', label: '' }, ...series.map((s) => ({ key: s.key, label: s.label }))]">
        <template #legend>
            <div class="mb-3 flex flex-wrap gap-4 text-xs text-gray-600">
                <span v-for="(s, i) in series" :key="s.key" class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 rounded" :style="{ background: `var(--series-${i + 1})` }" />{{ s.label }}</span>
            </div>
        </template>
        <div class="relative">
            <svg :viewBox="`0 0 ${W} ${H}`" class="w-full" :style="{ transform: isRtl ? 'scaleX(-1)' : '' }" role="img" :aria-label="title" @mousemove="move" @mouseleave="hover = null">
                <g v-for="tk in ticks" :key="tk">
                    <line :x1="P.l" :x2="W - P.r" :y1="y(tk)" :y2="y(tk)" stroke="var(--grid)" />
                </g>
                <line v-if="hover !== null" :x1="x(hover)" :x2="x(hover)" :y1="P.t" :y2="H - P.b" stroke="#9ca3af" stroke-dasharray="3 3" />
                <path v-for="(s, i) in series" :key="s.key" :d="path(s.key)" fill="none" :stroke="`var(--series-${i + 1})`" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />
                <template v-if="hover !== null">
                    <circle v-for="(s, i) in series" :key="s.key" :cx="x(hover)" :cy="y(data[hover][s.key] ?? 0)" r="4.5" :fill="`var(--series-${i + 1})`" stroke="#fff" stroke-width="2" />
                </template>
            </svg>
            <div class="mt-1 flex justify-between px-1 text-[10px] text-gray-400" dir="ltr" :style="{ flexDirection: isRtl ? 'row-reverse' : 'row' }">
                <span v-for="(d, i) in data" v-show="i % 3 === 0 || i === data.length - 1" :key="i">{{ d.label }}</span>
            </div>
            <div v-if="hover !== null" class="pointer-events-none absolute top-0 start-1/2 -translate-x-1/2 rounded-xl bg-navy-950 px-3 py-2 text-xs text-white shadow-lg rtl:translate-x-1/2">
                <p class="mb-1 opacity-70" dir="ltr">{{ data[hover].label }}</p>
                <p v-for="(s, i) in series" :key="s.key" class="flex items-center gap-1.5"><span class="size-2 rounded-full" :style="{ background: `var(--series-${i + 1})` }" />{{ s.label }}: {{ number(data[hover][s.key] ?? 0) }}</p>
            </div>
        </div>
    </ChartFrame>
</template>
