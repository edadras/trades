<script setup>
// AI never pretends to be final: every classification shows whether it is an AI suggestion or human-checked.
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
defineProps({ state: String });
const map = { ai_suggested: ['violet', 'sparkles'], expert_verified: ['sky', 'shield-check'], human_approved: ['green', 'check'] };
</script>
<template>
    <Badge v-if="state" :tone="map[state]?.[0] ?? 'gray'"><Icon :name="map[state]?.[1] ?? 'info'" :size="13" />{{ $t(`provenance.${state}`) }}</Badge>
</template>
