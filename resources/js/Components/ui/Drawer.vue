<script setup>
import { onMounted, ref } from 'vue';
import Icon from './Icon.vue';
defineProps({ show: Boolean, title: String });
const emit = defineEmits(['close']);
const mounted = ref(false);
onMounted(() => (mounted.value = true));
</script>

<template>
    <Teleport v-if="mounted" to="body">
        <Transition enter-from-class="opacity-0" enter-active-class="transition duration-200" leave-to-class="opacity-0" leave-active-class="transition duration-150">
            <div v-if="show" class="fixed inset-0 z-50 bg-navy-950/40 backdrop-blur-sm" @click="emit('close')" />
        </Transition>
        <Transition enter-from-class="ltr:-translate-x-full rtl:translate-x-full" enter-active-class="transition duration-300 ease-out" leave-to-class="ltr:-translate-x-full rtl:translate-x-full" leave-active-class="transition duration-200 ease-in">
            <aside v-if="show" class="fixed inset-y-0 start-0 z-50 flex w-[86vw] max-w-sm flex-col bg-white shadow-2xl" role="dialog" aria-modal="true">
                <header class="flex items-center justify-between border-b border-[var(--border)] px-5 py-4">
                    <slot name="header"><h2 class="text-lg font-semibold">{{ title }}</h2></slot>
                    <button type="button" class="grid size-9 place-items-center rounded-full hover:bg-gray-100" :aria-label="$t('common.close')" @click="emit('close')"><Icon name="x" :size="18" /></button>
                </header>
                <div class="flex-1 overflow-y-auto"><slot /></div>
            </aside>
        </Transition>
    </Teleport>
</template>
