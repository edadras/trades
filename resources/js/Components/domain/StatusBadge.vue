<script setup>
import Badge from '@/Components/ui/Badge.vue';
defineProps({ status: String });
const tone = { draft: 'gray', submitted: 'sky', ai_processing: 'violet', human_review: 'amber', ready: 'navy', matching: 'navy', expert_proposed: 'violet', accepted: 'green', in_progress: 'green', waiting: 'amber', resolved: 'green', closed: 'gray' };
</script>
<template><Badge :tone="tone[status] ?? 'gray'" dot>{{ $t(`status.${status}`) }}</Badge></template>
