<script setup>
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useI18n } from '@/i18n';
import { computed } from 'vue';
const props = defineProps({ expert: { type: Object, required: true }, score: Number, reasons: Array });
/** Only link http(s) profile URLs. */
const safeLinkedin = computed(() => (/^https?:\/\//i.test(props.expert.linkedin_url ?? '') ? props.expert.linkedin_url : null));
const { option, number, percent } = useI18n();
const modelTone = { voluntary: 'violet', free: 'green', subsidized: 'sky', commercial: 'gray' };
</script>

<template>
    <article class="card flex h-full flex-col p-5">
        <div class="flex items-start gap-3">
            <Avatar :name="expert.name" :src="expert.avatar" size="lg" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-semibold text-ink">{{ expert.name }}</h3>
                    <Badge v-if="expert.verified" tone="green"><Icon name="shield-check" :size="12" />{{ $t('expert.verified') }}</Badge>
                    <Badge v-if="expert.supporter_type" :tone="expert.supporter_type === 'organization' ? 'navy' : 'gray'">
                        <Icon :name="expert.supporter_type === 'organization' ? 'factory' : 'user'" :size="12" />{{ $t(`supporter_profile.types.${expert.supporter_type}`) }}
                    </Badge>
                </div>
                <p v-if="expert.contact_person" class="mt-0.5 text-xs text-gray-500">{{ $t('supporter_profile.contact_person', { name: expert.contact_person }) }}</p>
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
        <div v-if="expert.support_models?.length" class="mt-2 flex flex-wrap gap-1.5">
            <Badge v-for="m in expert.support_models" :key="m" :tone="modelTone[m] ?? 'gray'" dot>{{ $t(`supporter_profile.models.${m}`) }}</Badge>
        </div>
        <ul v-if="expert.certifications?.length" class="mt-3 space-y-1 text-xs text-gray-600">
            <li v-for="(c, i) in expert.certifications.slice(0, 3)" :key="i" class="flex items-start gap-1.5">
                <Icon name="star" :size="13" class="mt-0.5 shrink-0 text-amber-500" />
                <span>{{ c.title }}<template v-if="c.issuer"> · {{ c.issuer }}</template><template v-if="c.year"> · {{ c.year }}</template></span>
            </li>
        </ul>
        <div class="mt-auto flex flex-wrap items-center gap-x-4 gap-y-1 pt-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1"><Icon name="globe" :size="14" />{{ option('countries', expert.country) }}<template v-if="expert.city"> · {{ expert.city }}</template></span>
            <span class="inline-flex items-center gap-1"><Icon name="chat" :size="14" />{{ expert.languages?.map((l) => option('languages', l)).join('، ') }}</span>
            <span class="inline-flex items-center gap-1"><Icon name="briefcase" :size="14" />{{ $t('expert.years', { n: number(expert.years_experience) }) }}</span>
        </div>
        <div v-if="expert.email || expert.phone || safeLinkedin" class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-[var(--border)] pt-3 text-xs">
            <a v-if="expert.email" :href="`mailto:${expert.email}`" class="inline-flex items-center gap-1 text-navy-700 hover:underline" dir="ltr"><Icon name="mail" :size="14" />{{ expert.email }}</a>
            <a v-if="expert.phone" :href="`tel:${expert.phone}`" class="inline-flex items-center gap-1 text-navy-700 hover:underline" dir="ltr"><Icon name="phone" :size="14" />{{ expert.phone }}</a>
            <a v-if="safeLinkedin" :href="safeLinkedin" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-navy-700 hover:underline"><Icon name="link" :size="14" />{{ $t('supporter_profile.privacy_fields.linkedin_url') }}</a>
        </div>
        <slot />
    </article>
</template>
