<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ meta: { type: Object, required: true } });
const label = (l) => l.replace(/&laquo;|&raquo;/g, '').trim();
</script>

<template>
    <nav v-if="meta.last_page > 1" class="mt-6 flex flex-wrap items-center justify-center gap-1.5" aria-label="Pagination">
        <template v-for="(link, i) in meta.links" :key="i">
            <Link v-if="link.url" :href="link.url" preserve-scroll class="grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm transition" :class="link.active ? 'bg-navy-950 text-white' : 'bg-white text-gray-700 ring-1 ring-[var(--border)] hover:bg-navy-50'">
                <span v-if="i === 0" class="flip-rtl">‹</span><span v-else-if="i === meta.links.length - 1" class="flip-rtl">›</span><span v-else>{{ label(link.label) }}</span>
            </Link>
        </template>
    </nav>
</template>
