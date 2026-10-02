<script setup>
// Ordinal funnel: one blue ramp from step 700 (first stage) down to 250 (last), labels in text ink.
import { computed, ref } from 'vue';
import ChartFrame from './ChartFrame.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ title: String, subtitle: String, data: { type: Array, default: () => [] } });
const { number, percent, t } = useI18n();
const ramp = ['#0d366b', '#104281', '#184f95', '#1c5cab', '#2a78d6', '#5598e7', '#86b6ef'];
const max = computed(() => Math.max(1, ...props.data.map((d) => d.value)));
const hover = ref(null);
const rows = computed(() => props.data.map((d) => ({ label: t(`funnel.${d.key}`), value: d.value })));
</script>

<template>
    <ChartFrame :title="title" :subtitle="subtitle" :rows="max > 1 || data.some((d) => d.value) ? rows : []" :columns="[{ key: 'label', label: t('charts.stage') }, { key: 'value', label: t('charts.value') }]">
        <ul class="space-y-2">
            <li v-for="(d, i) in data" :key="d.key" class="flex items-center gap-3" @mouseenter="hover = i" @mouseleave="hover = null">
                <span class="w-24 shrink-0 text-xs text-gray-600 sm:w-28">{{ $t(`funnel.${d.key}`) }}</span>
                <div class="relative h-7 flex-1">
                    <div class="mx-auto h-full rounded-[4px] transition-all duration-700" :style="{ width: `${Math.max(3, (d.value / max) * 100)}%`, background: ramp[i] ?? ramp[ramp.length - 1], opacity: hover === null || hover === i ? 1 : 0.5 }" />
                </div>
                <span class="w-20 shrink-0 text-end text-xs tabular-nums text-ink">{{ number(d.value) }}<span v-if="i > 0 && data[0].value" class="text-gray-400"> · {{ percent(Math.round((d.value / data[0].value) * 100)) }}</span></span>
            </li>
        </ul>
    </ChartFrame>
</template>
