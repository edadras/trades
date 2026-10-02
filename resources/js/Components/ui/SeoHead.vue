<script setup>
// Title, description, canonical, hreflang, Open Graph and JSON-LD for SSR'd public pages.
import { Head } from '@inertiajs/vue3';
defineProps({ seo: { type: Object, default: null }, title: String });
</script>

<template>
    <Head v-if="seo" :title="seo.title">
        <meta head-key="description" name="description" :content="seo.description" />
        <link head-key="canonical" rel="canonical" :href="seo.canonical" />
        <link v-for="(href, lang) in seo.alternates" :key="lang" :head-key="`alt-${lang}`" rel="alternate" :hreflang="lang" :href="href" />
        <meta head-key="og:title" property="og:title" :content="seo.title" />
        <meta head-key="og:description" property="og:description" :content="seo.description" />
        <meta head-key="og:type" property="og:type" :content="seo.og.type" />
        <meta head-key="og:url" property="og:url" :content="seo.canonical" />
        <meta head-key="og:image" property="og:image" :content="seo.og.image" />
        <meta head-key="og:locale" property="og:locale" :content="seo.og.locale" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <component :is="'script'" v-if="seo.schema" head-key="schema" type="application/ld+json">{{ JSON.stringify(seo.schema) }}</component>
    </Head>
    <Head v-else :title="title" />
</template>
