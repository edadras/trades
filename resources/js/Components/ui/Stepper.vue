<script setup>
// The 7-step case progress indicator. Horizontal on wide screens, compact vertical-ish scroll on mobile.
import { onMounted, ref } from 'vue';
import Icon from './Icon.vue';
defineProps({ steps: { type: Array, required: true }, compact: Boolean });
const list = ref(null);
// On narrow screens keep the current step in view.
onMounted(() => {
    const current = list.value?.querySelector('[aria-current="step"]');
    if (current && list.value.scrollWidth > list.value.clientWidth) {
        list.value.scrollLeft += current.getBoundingClientRect().left - list.value.getBoundingClientRect().left - list.value.clientWidth / 2 + current.clientWidth / 2;
    }
});
</script>

<template>
    <ol ref="list" class="scrollbar-none flex items-start gap-0 overflow-x-auto" :class="compact ? 'py-1' : 'py-2'">
        <li v-for="(step, i) in steps" :key="step.key" class="flex min-w-[78px] flex-1 flex-col items-center text-center" :aria-current="step.state === 'current' ? 'step' : undefined">
            <div class="flex w-full items-center">
                <span class="h-0.5 flex-1 rounded" :class="i === 0 ? 'opacity-0' : step.state === 'upcoming' ? 'bg-gray-200' : 'bg-navy-600'" />
                <span
                    class="grid shrink-0 place-items-center rounded-full font-semibold transition"
                    :class="[
                        compact ? 'size-6 text-[10px]' : 'size-9 text-xs',
                        step.state === 'done' ? 'bg-navy-950 text-white' : step.state === 'current' ? 'bg-white text-navy-900 ring-2 ring-navy-600 shadow-[0_0_0_6px_rgba(63,104,204,.15)]' : step.state === 'skipped' ? 'bg-gray-100 text-gray-400' : 'bg-gray-100 text-gray-500',
                    ]"
                >
                    <Icon v-if="step.state === 'done'" name="check" :size="compact ? 12 : 16" />
                    <Icon v-else-if="step.state === 'skipped'" name="arrow" :size="compact ? 12 : 14" />
                    <template v-else>{{ i + 1 }}</template>
                </span>
                <span class="h-0.5 flex-1 rounded" :class="i === steps.length - 1 ? 'opacity-0' : steps[i + 1].state === 'upcoming' ? 'bg-gray-200' : 'bg-navy-600'" />
            </div>
            <span v-if="!compact" class="mt-2 px-1 text-[11px] leading-4 sm:text-xs" :class="step.state === 'current' ? 'font-semibold text-navy-900' : step.state === 'upcoming' ? 'text-gray-400' : 'text-gray-600'">{{ $t(`stepper.${step.key}`) }}</span>
        </li>
    </ol>
</template>
