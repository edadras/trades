<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const page = usePage();
const toasts = ref([]);
let id = 0;
watch(() => page.props.flash, (flash) => {
    for (const type of ['success', 'error', 'warning', 'status']) {
        if (flash?.[type]) {
            const tid = ++id;
            toasts.value.push({ id: tid, type, text: flash[type] });
            setTimeout(() => (toasts.value = toasts.value.filter((t) => t.id !== tid)), 4500);
        }
    }
}, { immediate: true, deep: true });
const tone = { success: 'bg-navy-950 text-white', status: 'bg-navy-950 text-white', error: 'bg-rose-600 text-white', warning: 'bg-amber-500 text-white' };
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-24 z-[60] flex flex-col items-center gap-2 px-4 lg:bottom-6" aria-live="polite">
        <TransitionGroup enter-from-class="opacity-0 translate-y-3" enter-active-class="transition duration-300" leave-to-class="opacity-0" leave-active-class="transition duration-200">
            <div v-for="t in toasts" :key="t.id" class="pointer-events-auto flex max-w-md items-center gap-3 rounded-full py-2 ps-2 pe-5 text-sm shadow-xl" :class="tone[t.type]">
                <span class="grid size-8 place-items-center rounded-full bg-white/15"><Icon :name="t.type === 'error' ? 'alert' : t.type === 'warning' ? 'info' : 'check'" :size="16" /></span>
                {{ t.text }}
            </div>
        </TransitionGroup>
    </div>
</template>
