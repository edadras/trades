<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
defineProps({ seo: Object, page: String });
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <article class="mx-auto max-w-3xl px-5 pt-16 sm:px-8">
            <h1 class="text-4xl font-semibold tracking-tight text-ink">{{ $t(`legal.${page}.title`) }}</h1>
            <p class="mt-2 text-sm text-gray-400">{{ $t('legal.updated') }}</p>
            <div class="prose-app mt-8">
                <template v-for="i in 6" :key="i">
                    <h2>{{ $t(`legal.${page}.h${i}`) }}</h2>
                    <p>{{ $t(`legal.${page}.p${i}`) }}</p>
                </template>
            </div>
        </article>
    </PublicLayout>
</template>
