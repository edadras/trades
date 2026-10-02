<script setup>
// Responsive table: real table on md+, stacked cards on mobile. Columns: [{key,label,class}]; use #cell-<key> slots.
defineProps({ columns: { type: Array, required: true }, rows: { type: Array, required: true }, rowKey: { type: String, default: 'id' } });
</script>

<template>
    <div>
        <div class="hidden overflow-x-auto md:block">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-[var(--border)] text-start text-xs uppercase tracking-wide text-gray-500">
                        <th v-for="c in columns" :key="c.key" class="px-4 py-3 text-start font-medium" :class="c.class">{{ c.label }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row[rowKey]" class="border-b border-[var(--border)] last:border-0 hover:bg-navy-50/40">
                        <td v-for="c in columns" :key="c.key" class="px-4 py-3.5 align-middle" :class="c.class">
                            <slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] ?? '—' }}</slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ul class="space-y-3 md:hidden">
            <li v-for="row in rows" :key="row[rowKey]" class="rounded-2xl bg-white p-4 ring-1 ring-[var(--border)]">
                <dl class="space-y-2">
                    <div v-for="c in columns" :key="c.key" class="flex items-start justify-between gap-4 text-sm">
                        <dt class="shrink-0 text-gray-500">{{ c.label }}</dt>
                        <dd class="min-w-0 text-end"><slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] ?? '—' }}</slot></dd>
                    </div>
                </dl>
            </li>
        </ul>
        <slot v-if="!rows.length" name="empty" />
    </div>
</template>
