<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({ show: Boolean, title: String, width: { type: String, default: 'max-w-lg' } });
const emit = defineEmits(['close']);
const mounted = ref(false);
onMounted(() => (mounted.value = true));
const onKey = (e) => e.key === 'Escape' && emit('close');
watch(() => props.show, (v) => {
    if (typeof document === 'undefined') return;
    document.body.style.overflow = v ? 'hidden' : '';
    v ? document.addEventListener('keydown', onKey) : document.removeEventListener('keydown', onKey);
});
onBeforeUnmount(() => typeof document !== 'undefined' && (document.body.style.overflow = ''));
</script>

<template>
    <Teleport v-if="mounted" to="body">
        <Transition enter-from-class="opacity-0" enter-active-class="transition duration-200" leave-to-class="opacity-0" leave-active-class="transition duration-150">
            <div v-if="show" class="fixed inset-0 z-50 flex items-end justify-center bg-navy-950/40 p-0 backdrop-blur-sm sm:items-center sm:p-6" @click.self="emit('close')">
                <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-[28px] bg-white shadow-2xl sm:rounded-[28px]" :class="width" role="dialog" aria-modal="true">
                    <header class="sticky top-0 z-10 flex items-center justify-between gap-4 border-b border-[var(--border)] bg-white/95 px-6 py-4 backdrop-blur">
                        <h2 class="text-lg font-semibold text-ink">{{ title }}</h2>
                        <button type="button" class="grid size-9 place-items-center rounded-full text-gray-500 hover:bg-gray-100" :aria-label="$t('common.close')" @click="emit('close')"><Icon name="x" :size="18" /></button>
                    </header>
                    <div class="p-6"><slot /></div>
                    <footer v-if="$slots.footer" class="flex flex-wrap justify-end gap-2 border-t border-[var(--border)] px-6 py-4"><slot name="footer" /></footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
