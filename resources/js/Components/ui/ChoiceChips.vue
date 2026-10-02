<script setup>
// Multi/single select rendered as selectable pill chips (mobile-friendly alternative to checkboxes).
const props = defineProps({ modelValue: [Array, String, null], options: { type: Array, required: true }, multiple: Boolean });
const emit = defineEmits(['update:modelValue']);
const isOn = (v) => (props.multiple ? (props.modelValue ?? []).includes(v) : props.modelValue === v);
function toggle(v) {
    if (!props.multiple) return emit('update:modelValue', v);
    const cur = props.modelValue ?? [];
    emit('update:modelValue', cur.includes(v) ? cur.filter((x) => x !== v) : [...cur, v]);
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <button v-for="o in options" :key="o.value" type="button" class="rounded-full px-4 py-2 text-sm font-medium transition" :class="isOn(o.value) ? 'bg-navy-950 text-white shadow' : 'bg-white text-gray-700 ring-1 ring-[var(--border)] hover:bg-navy-50'" :aria-pressed="isOn(o.value)" @click="toggle(o.value)">
            {{ o.label }}
        </button>
    </div>
</template>
