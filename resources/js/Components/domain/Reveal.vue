<script setup>
// Fades/slides content in when it scrolls into view (light IntersectionObserver, no library).
import { onBeforeUnmount, onMounted, ref } from 'vue';
const props = defineProps({ delay: { type: Number, default: 0 }, as: { type: String, default: 'div' } });
const el = ref(null);
const shown = ref(false);
let observer;
onMounted(() => {
    if (!('IntersectionObserver' in window)) return (shown.value = true);
    observer = new IntersectionObserver(([entry]) => { if (entry.isIntersecting) { shown.value = true; observer.disconnect(); } }, { rootMargin: '0px 0px -60px 0px' });
    observer.observe(el.value);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <component :is="as" ref="el" class="transition duration-700 ease-out" :class="shown ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'" :style="{ transitionDelay: `${delay}ms` }"><slot /></component>
</template>
