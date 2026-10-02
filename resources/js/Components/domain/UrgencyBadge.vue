<script setup>
import Badge from '@/Components/ui/Badge.vue';
defineProps({ urgency: String });
const tone = { low: 'gray', medium: 'sky', high: 'amber', critical: 'red' };
</script>
<template><Badge v-if="urgency" :tone="tone[urgency]">{{ $t('urgency.label') }}: {{ $t(`urgency.${urgency}`) }}</Badge></template>
