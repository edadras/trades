<script setup>
import { computed } from 'vue';
const props = defineProps({ modelValue: [Boolean, Array], value: [String, Number], label: String, description: String, error: String });
const emit = defineEmits(['update:modelValue']);
const checked = computed({
    get: () => (Array.isArray(props.modelValue) ? props.modelValue.includes(props.value) : !!props.modelValue),
    set: (v) => {
        if (!Array.isArray(props.modelValue)) return emit('update:modelValue', v);
        emit('update:modelValue', v ? [...props.modelValue, props.value] : props.modelValue.filter((x) => x !== props.value));
    },
});
</script>

<template>
    <label class="flex cursor-pointer items-start gap-3 rounded-2xl p-1">
        <input v-model="checked" type="checkbox" class="mt-0.5 size-5 shrink-0 rounded-md border-gray-300 text-navy-900 accent-navy-900 focus:ring-navy-300" />
        <span class="text-sm leading-6">
            <span class="font-medium text-ink"><slot>{{ label }}</slot></span>
            <span v-if="description" class="block text-gray-500">{{ description }}</span>
            <span v-if="error" class="block text-rose-600">{{ error }}</span>
        </span>
    </label>
</template>
