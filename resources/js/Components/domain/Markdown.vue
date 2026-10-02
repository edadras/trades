<script setup>
// Renders admin-authored Markdown safely (sanitised with DOMPurify on the client; escaped on SSR).
import { computed } from 'vue';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
const props = defineProps({ source: { type: String, default: '' } });
const html = computed(() => {
    const raw = marked.parse(props.source || '', { breaks: true });
    if (typeof window === 'undefined') return raw.replace(/<(script|iframe|object|embed|style)[\s\S]*?<\/\1>/gi, '').replace(/ on\w+="[^"]*"/gi, '');
    return DOMPurify.sanitize(raw);
});
</script>
<template><div class="prose-app" v-html="html" /></template>
