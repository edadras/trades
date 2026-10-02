<script setup>
// Labeled input/select/textarea with error + hint. Use `as="select"` with options [{value,label}].
import { computed, useAttrs, useId } from 'vue';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: [String, Number, null],
    label: String,
    hint: String,
    error: String,
    as: { type: String, default: 'input' },
    type: { type: String, default: 'text' },
    options: { type: Array, default: () => [] },
    placeholder: String,
    required: Boolean,
    rows: { type: Number, default: 4 },
    dir: String,
    autocomplete: String,
    disabled: Boolean,
});
const emit = defineEmits(['update:modelValue']);
const id = useId();
const attrs = useAttrs();
const controlAttrs = computed(() => { const { class: _c, style: _s, ...rest } = attrs; return rest; });
const value = computed({ get: () => props.modelValue ?? '', set: (v) => emit('update:modelValue', v) });
</script>

<template>
    <div :class="attrs.class" :style="attrs.style">
        <label v-if="label" :for="id" class="label">{{ label }}<span v-if="required" class="text-rose-500"> *</span></label>
        <select v-if="as === 'select'" v-bind="controlAttrs" :id="id" v-model="value" class="input appearance-none" :required="required" :disabled="disabled" :aria-invalid="!!error">
            <option value="">{{ placeholder ?? '—' }}</option>
            <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>
        <textarea v-else-if="as === 'textarea'" v-bind="controlAttrs" :id="id" v-model="value" class="input leading-7" :rows="rows" :placeholder="placeholder" :required="required" :dir="dir" :disabled="disabled" :aria-invalid="!!error" />
        <input v-else v-bind="controlAttrs" :id="id" v-model="value" :type="type" class="input" :placeholder="placeholder" :required="required" :dir="dir" :autocomplete="autocomplete" :disabled="disabled" :aria-invalid="!!error" />
        <p v-if="error" class="mt-1.5 text-sm text-rose-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>
