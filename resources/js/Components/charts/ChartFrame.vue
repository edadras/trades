<script setup>
// Card wrapper for every chart: title, optional legend slot, and an accessible data-table view.
import { ref } from 'vue';
defineProps({ title: String, subtitle: String, rows: { type: Array, default: () => [] }, columns: { type: Array, default: () => [] } });
const table = ref(false);
</script>

<template>
    <section class="card p-5 sm:p-6">
        <header class="mb-4 flex flex-wrap items-start justify-between gap-2">
            <div>
                <h3 class="font-semibold text-ink">{{ title }}</h3>
                <p v-if="subtitle" class="text-xs text-gray-500">{{ subtitle }}</p>
            </div>
            <button v-if="rows.length" type="button" class="text-xs font-medium text-navy-700 hover:underline" @click="table = !table">{{ table ? $t('charts.show_chart') : $t('charts.show_table') }}</button>
        </header>
        <slot name="legend" />
        <div v-if="!rows.length" class="grid h-40 place-items-center text-sm text-gray-400">{{ $t('charts.no_data') }}</div>
        <table v-else-if="table" class="w-full text-sm">
            <thead><tr class="text-xs text-gray-500"><th v-for="c in columns" :key="c.key" class="py-2 text-start font-medium">{{ c.label }}</th></tr></thead>
            <tbody><tr v-for="(r, i) in rows" :key="i" class="border-t border-[var(--border)]"><td v-for="c in columns" :key="c.key" class="py-2 tabular-nums">{{ r[c.key] }}</td></tr></tbody>
        </table>
        <slot v-else />
    </section>
</template>
