<script setup>
// AI analysis + initial guidance, always labelled with its provenance. Grounded only in approved knowledge.
import { computed } from 'vue';
import Card from '@/Components/ui/Card.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import ProvenanceBadge from '@/Components/domain/ProvenanceBadge.vue';
import KnowledgeCard from '@/Components/domain/KnowledgeCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { useI18n } from '@/i18n';

const props = defineProps({ analysis: Object, contents: Array });
const { percent, dateTime } = useI18n();
const g = computed(() => props.analysis?.guidance ?? {});
const sources = (ids) => props.contents.filter((c) => ids?.includes(c.id));
const sections = [['possible_causes', 'info', 'text-navy-600'], ['suggested_actions', 'check', 'text-emerald-600'], ['required_documents', 'file', 'text-sky-600'], ['warnings', 'alert', 'text-amber-600']];
</script>

<template>
    <EmptyState v-if="!analysis" icon="sparkles" :title="$t('analysis.pending')" :text="$t('analysis.pending_hint')" />
    <div v-else class="space-y-6">
        <Card>
            <div class="flex flex-wrap items-center gap-2">
                <ProvenanceBadge :state="analysis.verification_state" />
                <Badge tone="gray">{{ $t('analysis.confidence') }}: {{ percent(Math.round(analysis.confidence * 100)) }}</Badge>
                <Badge v-if="analysis.is_sensitive" tone="red"><Icon name="shield" :size="12" />{{ $t('analysis.sensitive') }}</Badge>
                <span class="ms-auto text-xs text-gray-400">{{ $t('analysis.version', { n: analysis.version }) }} · {{ dateTime(analysis.created_at) }}</span>
            </div>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl bg-[var(--surface-muted)] p-4"><p class="text-xs text-gray-500">{{ $t('analysis.category') }}</p><p class="mt-1 font-medium text-ink">{{ analysis.category?.name ?? '—' }}</p><p v-if="analysis.subcategory" class="text-sm text-gray-500">{{ analysis.subcategory.name }}</p></div>
                <div class="rounded-2xl bg-[var(--surface-muted)] p-4"><p class="text-xs text-gray-500">{{ $t('urgency.label') }}</p><p class="mt-1 font-medium text-ink">{{ analysis.urgency ? $t(`urgency.${analysis.urgency}`) : '—' }}</p></div>
                <div class="rounded-2xl bg-[var(--surface-muted)] p-4"><p class="text-xs text-gray-500">{{ $t('analysis.expert_needed') }}</p><p class="mt-1 font-medium text-ink">{{ analysis.needs_expert ? $t('common.yes') : $t('analysis.self_serve') }}</p></div>
            </div>
            <div class="mt-5">
                <h3 class="text-sm font-semibold text-ink">{{ $t('analysis.summary') }}</h3>
                <p class="mt-2 leading-7 text-gray-700">{{ g.situation || analysis.summary }}</p>
            </div>
            <div v-if="analysis.facts?.length" class="mt-5">
                <h3 class="text-sm font-semibold text-ink">{{ $t('analysis.facts') }}</h3>
                <div class="mt-2 flex flex-wrap gap-2"><Badge v-for="(f, i) in analysis.facts" :key="i" tone="navy">{{ f }}</Badge></div>
            </div>
            <p class="mt-6 flex items-start gap-2 rounded-2xl bg-violet-50 p-3 text-xs leading-5 text-violet-800"><Icon name="info" :size="14" class="mt-0.5" />{{ $t('analysis.disclaimer') }}</p>
        </Card>

        <div v-if="analysis.safety_flags?.length" class="rounded-[var(--radius-card)] bg-amber-50 p-5 ring-1 ring-amber-200">
            <p class="flex items-center gap-2 font-semibold text-amber-900"><Icon name="alert" />{{ $t('analysis.safety_title') }}</p>
            <p class="mt-1 text-sm text-amber-800">{{ $t('analysis.safety_text') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <template v-for="[key, icon, tone] in sections" :key="key">
                <Card v-if="g[key]?.length" :title="$t(`guidance.${key}`)">
                    <ul class="space-y-3">
                        <li v-for="(item, i) in g[key]" :key="i" class="flex items-start gap-2.5 text-sm leading-6 text-gray-700">
                            <Icon :name="icon" :size="16" class="mt-1" :class="tone" />
                            <div>
                                {{ item.text }}
                                <span v-for="s in sources(item.sources)" :key="s.id" class="ms-1 inline-flex items-center gap-1 rounded-full bg-navy-50 px-2 text-[11px] text-navy-700"><Icon name="book" :size="11" />{{ s.title }}</span>
                            </div>
                        </li>
                    </ul>
                </Card>
            </template>
        </div>
        <Card v-if="analysis.missing_information?.length" :title="$t('analysis.missing')">
            <ul class="list-disc space-y-1 ps-5 text-sm text-gray-700"><li v-for="(m, i) in analysis.missing_information" :key="i">{{ m }}</li></ul>
        </Card>
        <section v-if="contents.length">
            <h3 class="mb-3 font-semibold text-ink">{{ $t('analysis.related') }}</h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"><KnowledgeCard v-for="a in contents" :key="a.id" :article="a" /></div>
        </section>
        <p v-if="analysis.provider" class="text-xs text-gray-400" dir="ltr">engine: {{ analysis.provider }}</p>
    </div>
</template>
