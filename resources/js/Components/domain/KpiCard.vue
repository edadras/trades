<script setup>
import Icon from '@/Components/ui/Icon.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { useI18n } from '@/i18n';
const props = defineProps({ kpi: { type: Object, required: true } });
const { number, percent } = useI18n();
const fmt = (v) => (v === null || v === undefined ? '—' : props.kpi.unit === 'percent' ? percent(v) : number(v));
</script>

<template>
    <div class="card flex flex-col gap-4 p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-ink">{{ kpi.name }}</p>
                <p class="mt-0.5 text-xs text-gray-500">{{ kpi.description }}</p>
            </div>
            <span v-if="kpi.value !== null" class="chip shrink-0" :class="kpi.achieved ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'">
                <Icon :name="kpi.achieved ? 'check' : 'clock'" :size="12" />{{ kpi.achieved ? $t('kpi.achieved') : $t('kpi.in_progress') }}
            </span>
            <span v-else class="chip bg-gray-100 text-gray-600">{{ $t('kpi.no_data') }}</span>
        </div>
        <div class="flex items-end justify-between gap-2">
            <p class="text-3xl font-semibold tracking-tight text-ink tabular-nums">{{ fmt(kpi.value) }}<span v-if="kpi.unit === 'hours' && kpi.value !== null" class="ms-1 text-base text-gray-500">{{ $t('kpi.hours') }}</span></p>
            <p class="text-sm text-gray-500">{{ $t('kpi.target') }}: <span class="font-medium text-gray-700" dir="ltr">{{ kpi.comparator === '<=' ? '≤' : '≥' }} {{ fmt(kpi.target) }}</span></p>
        </div>
        <ProgressBar :value="kpi.progress ?? 0" :tone="kpi.achieved ? 'green' : 'navy'" :label="kpi.name" />
    </div>
</template>
