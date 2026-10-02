<script setup>
defineProps({ lines: { type: Number, default: 3 }, card: Boolean });
</script>

<template>
    <div :class="card ? 'card p-6' : ''" aria-busy="true">
        <div class="skeleton mb-4 h-5 w-1/3" />
        <div v-for="i in lines" :key="i" class="skeleton mb-2.5 h-3.5" :style="{ width: `${90 - i * 12}%` }" />
    </div>
</template>
