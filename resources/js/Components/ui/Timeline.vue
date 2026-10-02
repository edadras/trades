<script setup>
import Icon from './Icon.vue';
import { useI18n } from '@/i18n';
defineProps({ items: { type: Array, required: true } });
const { relative, dateTime } = useI18n();
</script>

<template>
    <ol class="relative space-y-5 ps-7 before:absolute before:inset-y-1 before:start-[13px] before:w-px before:bg-navy-100">
        <li v-for="item in items" :key="item.id" class="relative">
            <span class="absolute -start-7 top-0.5 grid size-[27px] place-items-center rounded-full bg-white ring-1 ring-navy-200" :class="item.tone === 'internal' ? 'text-amber-600' : 'text-navy-700'"><Icon :name="item.icon ?? 'dots-mark'" :size="14" /></span>
            <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                <p class="text-sm font-medium text-ink">{{ item.title }}</p>
                <time class="text-xs text-gray-400" :datetime="item.at" :title="dateTime(item.at)">{{ relative(item.at) }}</time>
            </div>
            <p v-if="item.text" class="mt-0.5 text-sm text-gray-500">{{ item.text }}</p>
        </li>
    </ol>
</template>
