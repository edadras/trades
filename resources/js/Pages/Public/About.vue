<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route } from '@/i18n';
defineProps({ seo: Object });
const values = [['network', 'network'], ['evidence', 'chart'], ['trust', 'shield-check']];
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <section class="px-2 sm:px-3">
            <div class="section-gradient mx-auto mt-6 max-w-[1400px] rounded-[36px] px-5 py-20 text-center text-white sm:rounded-[48px]">
                <h1 class="mx-auto max-w-3xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl">{{ $t('about.title') }}</h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-white/85">{{ $t('about.subtitle') }}</p>
            </div>
        </section>
        <section class="mx-auto max-w-3xl px-5 pt-16 sm:px-8">
            <h2 class="text-2xl font-semibold text-ink">{{ $t('about.mission_title') }}</h2>
            <p class="mt-4 leading-8 text-gray-600">{{ $t('about.mission') }}</p>
            <h2 class="mt-10 text-2xl font-semibold text-ink">{{ $t('about.pilot_title') }}</h2>
            <p class="mt-4 leading-8 text-gray-600">{{ $t('about.pilot') }}</p>
        </section>
        <section class="mx-auto max-w-6xl px-5 pt-14 sm:px-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div v-for="[key, icon] in values" :key="key" class="card p-6">
                    <span class="grid size-12 place-items-center rounded-2xl bg-navy-950 text-white"><Icon :name="icon" /></span>
                    <h3 class="mt-5 font-semibold text-ink">{{ $t(`about.v_${key}.title`) }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">{{ $t(`about.v_${key}.text`) }}</p>
                </div>
            </div>
            <div class="mt-12 text-center"><Button :href="route('register')" size="lg">{{ $t('nav.get_started') }}</Button></div>
        </section>
    </PublicLayout>
</template>
