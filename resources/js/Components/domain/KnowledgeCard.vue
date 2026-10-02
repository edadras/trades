<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';
import { useI18n } from '@/i18n';
const props = defineProps({ article: { type: Object, required: true }, horizontal: Boolean });
const { date, number } = useI18n();
const tint = (c) => c?.color ?? '#3f68cc';
</script>

<template>
    <Link :href="route('knowledge.show', { article: article.slug })" class="group card card-hover block overflow-hidden p-1.5" :class="horizontal ? 'sm:flex sm:items-stretch' : ''">
        <div class="relative grid shrink-0 place-items-center overflow-hidden rounded-[22px]" :class="horizontal ? 'aspect-[4/3] sm:w-2/5' : 'aspect-[16/10]'" :style="{ background: `linear-gradient(140deg, ${tint(article.category)}22, ${tint(article.category)}55)` }">
            <img v-if="article.cover_image" :src="article.cover_image" alt="" class="size-24 opacity-90 transition duration-500 group-hover:scale-110" loading="lazy" />
            <span class="absolute start-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-medium text-ink">{{ $t(`content_type.${article.type}`) }}</span>
        </div>
        <div class="flex flex-col p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1"><Icon name="calendar" :size="14" />{{ date(article.published_at) }}</span>
                <span class="h-3 w-px bg-gray-200" />
                <span class="inline-flex items-center gap-1"><Icon name="clock" :size="14" />{{ $t('knowledge.minutes', { n: number(article.reading_minutes) }) }}</span>
            </div>
            <h3 class="mt-3 text-[17px] font-semibold leading-7 text-ink">{{ article.title }}</h3>
            <p v-if="article.summary && !horizontal" class="mt-2 line-clamp-2 text-sm leading-6 text-gray-500">{{ article.summary }}</p>
            <span class="mt-4 inline-flex w-fit items-center gap-2 rounded-full bg-white py-1 ps-1 pe-3.5 text-sm ring-1 ring-[var(--border)]">
                <span class="grid size-7 place-items-center rounded-full bg-navy-950 text-white"><Icon name="dots-mark" :size="13" /></span>{{ $t('common.read_more') }}
            </span>
        </div>
    </Link>
</template>
