<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import { route } from '@/i18n';
defineProps({ status: Number });
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :title="String(status)" />
        <section class="mx-auto max-w-xl px-5 py-24 text-center">
            <p class="text-7xl font-semibold text-navy-200">{{ status }}</p>
            <h1 class="mt-4 text-2xl font-semibold text-ink">{{ $t(`errors.${status}`) }}</h1>
            <Button :href="route('home')" class="mt-8" icon="home">{{ $t('nav.home') }}</Button>
        </section>
    </PublicLayout>
</template>
