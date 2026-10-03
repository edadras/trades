<script setup>
// Fixed bottom navigation for phones and tablets (hidden from lg up). The last tab opens the hamburger drawer.
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import { isCurrentPage } from '@/i18n';

defineProps({
    items: { type: Array, required: true },
    moreLabel: { type: String, required: true },
    moreOpen: Boolean,
});
const emit = defineEmits(['more']);
const isActive = (item) => isCurrentPage(item.match, item.href);
</script>

<template>
    <nav class="fixed inset-x-3 bottom-3 z-30 rounded-[26px] bg-white/95 p-1.5 shadow-[var(--shadow-lift)] ring-1 ring-[var(--border)] backdrop-blur print:hidden lg:hidden" style="margin-bottom: env(safe-area-inset-bottom)" :aria-label="moreLabel">
        <ul class="flex">
            <li v-for="item in items" :key="item.href" class="min-w-0 flex-1">
                <Link
                    :href="item.href"
                    class="flex min-h-[52px] flex-col items-center justify-center gap-0.5 rounded-2xl px-1 py-1.5 text-[11px] font-medium transition"
                    :class="isActive(item) ? 'bg-navy-950 text-white' : item.cta ? 'text-navy-800' : 'text-gray-500 hover:text-ink'"
                    :aria-current="isActive(item) ? 'page' : undefined"
                >
                    <span class="relative" :class="item.cta && !isActive(item) ? 'grid size-7 place-items-center rounded-full bg-navy-950 text-white' : ''">
                        <Icon :name="item.icon" :size="item.cta && !isActive(item) ? 16 : 20" />
                        <span v-if="item.badge" class="absolute -end-2 -top-1 size-2 rounded-full bg-amber-400" />
                    </span>
                    <span class="max-w-full truncate">{{ item.short ?? item.label }}</span>
                </Link>
            </li>
            <li class="min-w-0 flex-1">
                <button type="button" class="flex min-h-[52px] w-full flex-col items-center justify-center gap-0.5 rounded-2xl px-1 py-1.5 text-[11px] font-medium transition" :class="moreOpen ? 'bg-navy-50 text-navy-900' : 'text-gray-500 hover:text-ink'" :aria-expanded="moreOpen" @click="emit('more')">
                    <Icon name="menu" :size="20" />
                    <span class="max-w-full truncate">{{ moreLabel }}</span>
                </button>
            </li>
        </ul>
    </nav>
</template>
