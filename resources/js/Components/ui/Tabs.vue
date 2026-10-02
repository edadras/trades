<script setup>
// Pill tab bar (as in the reference "Payments / Analytics / ..." control); horizontally scrollable on mobile.
defineProps({ tabs: { type: Array, required: true }, modelValue: String, size: { type: String, default: 'md' } });
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="scrollbar-none -mx-1 overflow-x-auto px-1">
        <div class="inline-flex min-w-full gap-1 rounded-full border border-[var(--border)] bg-white p-1 sm:min-w-0" role="tablist">
            <button
                v-for="tab in tabs" :key="tab.key" type="button" role="tab" :aria-selected="modelValue === tab.key"
                class="relative flex-1 whitespace-nowrap rounded-full px-4 font-medium transition duration-200 sm:flex-none"
                :class="[size === 'sm' ? 'py-1.5 text-[13px]' : 'py-2.5 text-sm', modelValue === tab.key ? 'bg-navy-950 text-white shadow' : 'text-gray-600 hover:bg-navy-50']"
                @click="emit('update:modelValue', tab.key)"
            >
                {{ tab.label }}
                <span v-if="tab.count" class="ms-1.5 rounded-full px-1.5 text-[11px]" :class="modelValue === tab.key ? 'bg-white/20' : 'bg-navy-100 text-navy-800'">{{ tab.count }}</span>
            </button>
        </div>
    </div>
</template>
