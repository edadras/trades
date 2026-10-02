<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import Reveal from '@/Components/domain/Reveal.vue';
import { route, useI18n } from '@/i18n';
defineProps({ seo: Object });
const { number } = useI18n();
const steps = [['submit', 'mic'], ['analysis', 'sparkles'], ['guidance', 'book'], ['supporter', 'users'], ['collaborate', 'chat'], ['follow_up', 'calendar'], ['result', 'flag']];
const principles = [['ai_suggests', 'sparkles'], ['human_decides', 'shield-check'], ['approved_only', 'book'], ['privacy', 'lock']];
</script>

<template>
    <PublicLayout :announcement="false">
        <SeoHead :seo="seo" />
        <section class="mx-auto max-w-4xl px-5 pt-16 text-center sm:px-8">
            <Badge tone="navy">{{ $t('home.how_badge') }}</Badge>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight text-ink sm:text-5xl">{{ $t('how_page.title') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg leading-8 text-gray-500">{{ $t('how_page.subtitle') }}</p>
        </section>
        <section class="mx-auto max-w-4xl px-5 pt-14 sm:px-8">
            <ol class="relative space-y-4 before:absolute before:inset-y-6 before:start-[27px] before:w-px before:bg-navy-100 sm:before:start-[31px]">
                <Reveal v-for="([key, icon], i) in steps" :key="key" as="li" :delay="i * 40" class="relative flex gap-4 sm:gap-6">
                    <span class="relative z-10 grid size-14 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white sm:size-16"><Icon :name="icon" :size="22" /></span>
                    <div class="card flex-1 p-5 sm:p-6">
                        <p class="text-xs font-semibold text-navy-600">{{ $t('how_page.step', { n: number(i + 1) }) }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-ink">{{ $t(`how.${key}.title`) }}</h2>
                        <p class="mt-2 leading-7 text-gray-600">{{ $t(`how_page.${key}`) }}</p>
                    </div>
                </Reveal>
            </ol>
        </section>
        <section class="mx-auto max-w-6xl px-5 pt-20 sm:px-8">
            <h2 class="text-center text-3xl font-semibold tracking-tight text-ink">{{ $t('how_page.principles') }}</h2>
            <div class="mt-10 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="[key, icon] in principles" :key="key" class="card p-6">
                    <span class="grid size-12 place-items-center rounded-2xl bg-navy-50 text-navy-800"><Icon :name="icon" /></span>
                    <h3 class="mt-5 font-semibold text-ink">{{ $t(`how_page.p_${key}.title`) }}</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">{{ $t(`how_page.p_${key}.text`) }}</p>
                </div>
            </div>
            <div class="mt-14 flex flex-wrap justify-center gap-3">
                <Button :href="route('register')" size="lg" icon="plus">{{ $t('home.cta_submit') }}</Button>
                <Button :href="route('knowledge.index')" variant="light" size="lg" icon="book">{{ $t('nav.knowledge') }}</Button>
            </div>
        </section>
    </PublicLayout>
</template>
