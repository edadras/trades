<script setup>
defineProps({ title: String, subtitle: String, padded: { type: Boolean, default: true }, hover: Boolean, as: { type: String, default: 'section' } });
</script>

<template>
    <component :is="as" class="card" :class="[{ 'card-hover': hover }]">
        <header v-if="title || $slots.actions" class="flex flex-wrap items-start justify-between gap-3 px-5 pt-5 sm:px-6 sm:pt-6">
            <div class="min-w-0">
                <h3 v-if="title" class="text-[17px] font-semibold text-ink">{{ title }}</h3>
                <p v-if="subtitle" class="mt-1 text-sm text-gray-500">{{ subtitle }}</p>
            </div>
            <div class="flex items-center gap-2"><slot name="actions" /></div>
        </header>
        <div :class="padded ? 'p-5 sm:p-6' : ''"><slot /></div>
    </component>
</template>
