<script setup>
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useI18n } from '@/i18n';
defineProps({ expert: { type: Object, required: true }, score: Number, reasons: Array });
const { option, number, percent } = useI18n();
</script>

<template>
    <article class="card flex h-full flex-col p-5">
        <div class="flex items-start gap-3">
            <Avatar :name="expert.name" :src="expert.avatar" size="lg" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-semibold text-ink">{{ expert.name }}</h3>
                    <Badge v-if="expert.verified" tone="green"><Icon name="shield-check" :size="12" />{{ $t('expert.verified') }}</Badge>
                </div>
                <p class="mt-0.5 text-sm text-gray-500">{{ expert.headline }}</p>
            </div>
            <div v-if="score !== undefined && score !== null" class="shrink-0 rounded-2xl bg-navy-950 px-3 py-2 text-center text-white">
                <p class="text-lg font-semibold leading-none tabular-nums">{{ percent(Math.round(score)) }}</p>
                <p class="mt-1 text-[10px] opacity-70">{{ $t('match.match') }}</p>
            </div>
        </div>
        <ul v-if="reasons?.length" class="mt-4 space-y-1.5">
            <li v-for="(r, i) in reasons" :key="i" class="flex items-start gap-2 text-sm text-gray-700"><Icon name="check" :size="15" class="mt-1 text-emerald-600" />{{ r }}</li>
        </ul>
        <div class="mt-4 flex flex-wrap gap-1.5">
            <Badge v-for="s in expert.skills?.slice(0, 4)" :key="s.id" tone="navy">{{ s.name }}</Badge>
        </div>
        <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1"><Icon name="globe" :size="14" />{{ option('countries', expert.country) }}</span>
            <span class="inline-flex items-center gap-1"><Icon name="chat" :size="14" />{{ expert.languages?.map((l) => option('languages', l)).join('، ') }}</span>
            <span class="inline-flex items-center gap-1"><Icon name="briefcase" :size="14" />{{ $t('expert.years', { n: number(expert.years_experience) }) }}</span>
        </div>
        <slot />
    </article>
</template>
