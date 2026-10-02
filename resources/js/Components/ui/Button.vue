<script setup>
// Pill button with a small icon container, the signature control of the design language.
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    href: String,
    external: Boolean,
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' }, // primary | light | glass | outline | ghost | danger | success
    size: { type: String, default: 'md' }, // sm | md | lg
    icon: { type: String, default: 'dots-mark' },
    iconOnly: Boolean,
    noIcon: Boolean,
    loading: Boolean,
    disabled: Boolean,
    block: Boolean,
    method: String,
});

const styles = {
    primary: ['bg-navy-950 text-white hover:bg-navy-900 ring-1 ring-navy-950', 'bg-white text-navy-950'],
    light: ['bg-white text-ink hover:bg-navy-50 ring-1 ring-[var(--border)]', 'bg-navy-950 text-white'],
    glass: ['bg-white/15 text-white hover:bg-white/25 ring-1 ring-white/25 backdrop-blur', 'bg-navy-950 text-white'],
    outline: ['bg-transparent text-navy-900 hover:bg-navy-50 ring-1 ring-navy-200', 'bg-navy-100 text-navy-900'],
    ghost: ['bg-transparent text-gray-700 hover:bg-gray-100', 'bg-gray-100 text-gray-700'],
    danger: ['bg-rose-600 text-white hover:bg-rose-700 ring-1 ring-rose-600', 'bg-white text-rose-700'],
    success: ['bg-emerald-600 text-white hover:bg-emerald-700 ring-1 ring-emerald-600', 'bg-white text-emerald-700'],
};
const sizes = { sm: ['h-9 text-[13px] ps-1 pe-3.5 gap-2', 'size-7 rounded-full'], md: ['h-11 text-[14px] ps-1.5 pe-5 gap-2.5', 'size-8 rounded-full'], lg: ['h-13 text-[15px] ps-1.5 pe-6 gap-3', 'size-10 rounded-full'] };

const cls = computed(() => [
    'group inline-flex select-none items-center justify-center rounded-full font-medium whitespace-nowrap transition duration-200 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-navy-200 active:scale-[.98] disabled:pointer-events-none disabled:opacity-50',
    styles[props.variant][0],
    props.iconOnly ? (props.size === 'sm' ? 'size-9' : 'size-11') : sizes[props.size][0],
    props.noIcon && !props.iconOnly ? (props.size === 'sm' ? 'px-4' : 'px-6') : '',
    props.block ? 'w-full' : '',
]);
const boxCls = computed(() => ['grid place-items-center transition-transform duration-300 group-hover:scale-105', styles[props.variant][1], sizes[props.size][1]]);
</script>

<template>
    <component
        :is="href ? (external ? 'a' : Link) : 'button'"
        :href="href"
        :method="href && method ? method : undefined"
        :as="href && method ? 'button' : undefined"
        :type="href ? undefined : type"
        :disabled="disabled || loading"
        :class="cls"
    >
        <template v-if="iconOnly">
            <Icon :name="icon" :size="size === 'sm' ? 16 : 18" />
        </template>
        <template v-else>
            <span v-if="!noIcon" :class="boxCls">
                <svg v-if="loading" class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" opacity=".25" /><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" /></svg>
                <Icon v-else :name="icon" :size="size === 'lg' ? 18 : 15" />
            </span>
            <span><slot /></span>
        </template>
    </component>
</template>
