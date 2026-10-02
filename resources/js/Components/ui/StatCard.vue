<script setup>
import { onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ label: String, value: [Number, String], suffix: String, icon: String, hint: String, tone: { type: String, default: 'navy' }, href: String });
const { number } = useI18n();
const shown = ref(typeof props.value === 'number' ? 0 : props.value);

// Count-up animation for numeric values.
function animate(to) {
    if (typeof to !== 'number' || typeof window === 'undefined') return (shown.value = to);
    const start = performance.now();
    const step = (now) => {
        const p = Math.min(1, (now - start) / 700);
        shown.value = Math.round(to * (1 - Math.pow(1 - p, 3)) * 10) / 10;
        if (p < 1) requestAnimationFrame(step);
        else shown.value = to;
    };
    requestAnimationFrame(step);
}
onMounted(() => animate(props.value));
watch(() => props.value, animate);
const tones = { navy: 'bg-navy-950 text-white', light: 'bg-navy-50 text-navy-800', amber: 'bg-amber-50 text-amber-700', emerald: 'bg-emerald-50 text-emerald-700', rose: 'bg-rose-50 text-rose-700' };
</script>

<template>
    <component :is="href ? 'a' : 'div'" :href="href" class="card card-hover flex flex-col justify-between gap-6 p-5">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm text-gray-500">{{ label }}</p>
            <span v-if="icon" class="grid size-10 place-items-center rounded-2xl" :class="tones[tone]"><Icon :name="icon" :size="18" /></span>
        </div>
        <div>
            <p class="text-3xl font-semibold tracking-tight text-ink tabular-nums">
                <template v-if="value === null || value === undefined">—</template>
                <template v-else>{{ typeof shown === 'number' ? number(shown) : shown }}</template><span v-if="suffix && value !== null && value !== undefined" class="ms-1 text-lg text-gray-500">{{ suffix }}</span>
            </p>
            <p v-if="hint" class="mt-1 text-xs text-gray-500">{{ hint }}</p>
        </div>
    </component>
</template>
