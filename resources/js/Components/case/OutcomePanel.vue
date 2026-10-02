<script setup>
// Closing requires an outcome. "Effective action started" is reported separately from "Resolved".
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object });
const { t, dateTime } = useI18n();
const outcomes = ['resolved', 'partially_resolved', 'effective_action_started', 'unresolved', 'abandoned'];
const form = useForm({ outcome: props.item.outcome?.outcome ?? '', reason: props.item.outcome?.reason ?? '', result_summary: props.item.outcome?.result_summary ?? '' });
const survey = useForm({ rating: props.item.survey?.rating ?? 0, comment: props.item.survey?.comment ?? '', problem_solved: props.item.survey?.problem_solved ?? null, would_recommend_expert: props.item.survey?.would_recommend_expert ?? null });
const closing = ref(false);
const close = () => router.post(route('cases.close', { case: props.item.number }), {}, { preserveScroll: true, onStart: () => (closing.value = true), onFinish: () => (closing.value = false) });
const tone = { resolved: 'green', partially_resolved: 'sky', effective_action_started: 'violet', unresolved: 'amber', abandoned: 'gray' };
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2">
        <Card :title="$t('outcome.title')" :subtitle="$t('outcome.subtitle')">
            <div v-if="item.outcome" class="mb-6 rounded-2xl bg-[var(--surface-muted)] p-4">
                <Badge :tone="tone[item.outcome.outcome]">{{ $t(`outcome_type.${item.outcome.outcome}`) }}</Badge>
                <p class="mt-2 text-sm text-gray-700">{{ item.outcome.reason }}</p>
                <p v-if="item.outcome.result_summary" class="mt-1 text-sm text-gray-500">{{ item.outcome.result_summary }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ item.outcome.recorded_by }} · {{ dateTime(item.outcome.created_at) }}</p>
            </div>
            <form v-if="item.can.record_outcome" class="space-y-4" @submit.prevent="form.post(route('cases.outcome.store', { case: item.number }), { preserveScroll: true })">
                <div class="grid gap-2">
                    <label v-for="o in outcomes" :key="o" class="flex cursor-pointer items-start gap-3 rounded-2xl p-3 ring-1 transition" :class="form.outcome === o ? 'bg-navy-50 ring-navy-300' : 'ring-[var(--border)] hover:bg-gray-50'">
                        <input v-model="form.outcome" type="radio" :value="o" class="mt-1 accent-navy-900" />
                        <span><span class="block text-sm font-medium text-ink">{{ $t(`outcome_type.${o}`) }}</span><span class="block text-xs text-gray-500">{{ $t(`outcome_hint.${o}`) }}</span></span>
                    </label>
                </div>
                <p v-if="form.errors.outcome" class="text-sm text-rose-600">{{ form.errors.outcome }}</p>
                <Field v-model="form.reason" as="textarea" :rows="3" :label="$t('outcome.reason')" required :error="form.errors.reason" />
                <Field v-model="form.result_summary" as="textarea" :rows="2" :label="$t('outcome.result_summary')" />
                <div class="flex flex-wrap gap-2">
                    <Button type="submit" icon="flag" :loading="form.processing">{{ $t('outcome.save') }}</Button>
                    <Button v-if="item.can.close" variant="light" icon="lock" :loading="closing" @click="close">{{ $t('outcome.close_case') }}</Button>
                </div>
            </form>
            <p v-else-if="!item.outcome" class="text-sm text-gray-500">{{ $t('outcome.none') }}</p>
        </Card>

        <Card v-if="item.can.rate" :title="$t('survey.title')" :subtitle="$t('survey.subtitle')">
            <form class="space-y-5" @submit.prevent="survey.post(route('cases.satisfaction', { case: item.number }), { preserveScroll: true })">
                <div class="flex gap-1" role="radiogroup" :aria-label="$t('fields.rating')">
                    <button v-for="n in 5" :key="n" type="button" class="rounded-xl p-1.5 transition hover:scale-110" :class="n <= survey.rating ? 'text-amber-500' : 'text-gray-300'" :aria-label="`${n}`" @click="survey.rating = n"><Icon name="star" :size="30" :class="n <= survey.rating ? 'fill-current' : ''" /></button>
                </div>
                <p v-if="survey.errors.rating" class="text-sm text-rose-600">{{ survey.errors.rating }}</p>
                <div v-for="q in ['problem_solved', 'would_recommend_expert']" :key="q">
                    <p class="label">{{ $t(`survey.${q}`) }}</p>
                    <div class="flex gap-2">
                        <button v-for="v in [true, false]" :key="String(v)" type="button" class="rounded-full px-5 py-2 text-sm ring-1" :class="survey[q] === v ? 'bg-navy-950 text-white ring-navy-950' : 'ring-[var(--border)]'" @click="survey[q] = v">{{ v ? $t('common.yes') : $t('common.no') }}</button>
                    </div>
                </div>
                <Field v-model="survey.comment" as="textarea" :rows="3" :label="$t('survey.comment')" />
                <Button type="submit" icon="star" :loading="survey.processing" :disabled="!survey.rating">{{ $t('survey.submit') }}</Button>
                <p v-if="item.survey" class="text-xs text-emerald-700">{{ $t('survey.thanks') }}</p>
            </form>
        </Card>
    </div>
</template>
