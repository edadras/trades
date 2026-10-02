<script setup>
// Drag-and-drop multi-file picker. Emits File objects; the parent submits them with its form.
import { ref } from 'vue';
import Icon from './Icon.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ modelValue: { type: Array, default: () => [] }, accept: String, maxKb: { type: Number, default: 20480 }, multiple: { type: Boolean, default: true }, error: String, compact: Boolean });
const emit = defineEmits(['update:modelValue']);
const { t, number } = useI18n();
const over = ref(false);
const local = ref('');
const input = ref(null);

function add(list) {
    local.value = '';
    const files = [...list].filter((f) => {
        if (f.size / 1024 > props.maxKb) { local.value = t('upload.too_large', { name: f.name, max: Math.round(props.maxKb / 1024) }); return false; }
        return true;
    });
    emit('update:modelValue', props.multiple ? [...props.modelValue, ...files].slice(0, 10) : files.slice(0, 1));
}
const remove = (i) => emit('update:modelValue', props.modelValue.filter((_, idx) => idx !== i));
const size = (b) => (b > 1048576 ? `${number(Math.round(b / 104857.6) / 10)} MB` : `${number(Math.round(b / 1024))} KB`);
</script>

<template>
    <div>
        <div
            class="flex cursor-pointer items-center gap-4 rounded-3xl border-2 border-dashed transition"
            :class="[over ? 'border-navy-500 bg-navy-50' : 'border-navy-100 bg-[var(--surface-muted)] hover:border-navy-300', compact ? 'p-3' : 'p-5']"
            role="button" tabindex="0"
            @click="input.click()" @keydown.enter="input.click()"
            @dragover.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="over = false; add($event.dataTransfer.files)"
        >
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-white text-navy-700 shadow-[var(--shadow-soft)]"><Icon name="upload" /></span>
            <div class="text-sm">
                <p class="font-medium text-ink">{{ $t('upload.drop') }}</p>
                <p class="text-gray-500">{{ $t('upload.formats', { max: Math.round(maxKb / 1024) }) }}</p>
            </div>
            <input ref="input" type="file" class="hidden" :accept="accept" :multiple="multiple" @change="add($event.target.files)" />
        </div>
        <p v-if="error || local" class="mt-1.5 text-sm text-rose-600">{{ error || local }}</p>
        <ul v-if="modelValue.length" class="mt-3 space-y-2">
            <li v-for="(f, i) in modelValue" :key="i" class="flex items-center gap-3 rounded-2xl bg-white px-3 py-2 ring-1 ring-[var(--border)]">
                <Icon name="file" class="text-navy-600" />
                <span class="min-w-0 flex-1 truncate text-sm">{{ f.name }}</span>
                <span class="text-xs text-gray-400">{{ size(f.size) }}</span>
                <button type="button" class="grid size-7 place-items-center rounded-full text-gray-400 hover:bg-rose-50 hover:text-rose-600" :aria-label="$t('common.remove')" @click="remove(i)"><Icon name="x" :size="14" /></button>
            </li>
        </ul>
    </div>
</template>
