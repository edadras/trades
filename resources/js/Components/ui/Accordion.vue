<script setup>
import { ref } from 'vue';
import Icon from './Icon.vue';
const props = defineProps({ items: { type: Array, required: true }, initial: { type: Number, default: 0 } });
const open = ref(props.initial);
</script>

<template>
    <div class="space-y-2 rounded-[var(--radius-card)] border border-[var(--border)] bg-[var(--surface-muted)] p-2">
        <div v-for="(item, i) in items" :key="i" class="overflow-hidden rounded-[22px] bg-white ring-1 ring-[var(--border)]">
            <button type="button" class="flex w-full items-center justify-between gap-4 px-5 py-5 text-start sm:px-6" :aria-expanded="open === i" @click="open = open === i ? -1 : i">
                <span class="text-[15px] font-medium text-ink">{{ item.title }}</span>
                <span class="grid size-8 shrink-0 place-items-center rounded-full transition" :class="open === i ? 'bg-navy-950 text-white' : 'bg-gray-100 text-gray-600'"><Icon :name="open === i ? 'chevron-up' : 'chevron-down'" :size="16" /></span>
            </button>
            <div class="grid transition-[grid-template-rows] duration-300 ease-out" :class="open === i ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                <div class="overflow-hidden">
                    <div class="px-5 pb-6 text-[15px] leading-8 text-gray-600 sm:px-6"><slot name="item" :item="item">{{ item.body }}</slot></div>
                </div>
            </div>
        </div>
    </div>
</template>
