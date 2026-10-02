<script setup>
// Closing requires an outcome confirmed by the business. "Effective action started" is reported separately from "Resolved".
import { computed, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import Modal from '@/Components/ui/Modal.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object });
const { t, date, dateTime } = useI18n();
const outcomes = ['resolved', 'partially_resolved', 'effective_action_started', 'unresolved', 'abandoned'];
const dissatisfactionReasons = ['slow_response', 'expert_mismatch', 'guidance_not_useful', 'problem_not_solved', 'communication', 'cost_or_terms', 'other'];
const form = useForm({ outcome: props.item.outcome?.outcome ?? '', reason: props.item.outcome?.reason ?? '', result_summary: props.item.outcome?.result_summary ?? '' });
const survey = useForm({
    rating: props.item.survey?.rating ?? 0, comment: props.item.survey?.comment ?? '', dissatisfaction_reason: props.item.survey?.dissatisfaction_reason ?? '',
    problem_solved: props.item.survey?.problem_solved ?? null, would_recommend_expert: props.item.survey?.would_recommend_expert ?? null,
});
const needsDissatisfactionReason = computed(() => survey.rating > 0 && survey.rating <= 3);
const submitSurvey = () => survey
    .transform((data) => ({ ...data, dissatisfaction_reason: needsDissatisfactionReason.value ? data.dissatisfaction_reason || null : null }))
    .post(route('cases.satisfaction', { case: props.item.number }), { preserveScroll: true });

const closing = ref(false);
const close = () => router.post(route('cases.close', { case: props.item.number }), {}, { preserveScroll: true, onStart: () => (closing.value = true), onFinish: () => (closing.value = false) });

const confirmation = useForm({ confirm: true, reason: '' });
const disputing = ref(false);
const confirmOutcome = (confirm) => {
    confirmation.confirm = confirm;
    confirmation.post(route('cases.outcome.confirm', { case: props.item.number }), { preserveScroll: true, onSuccess: () => { disputing.value = false; confirmation.reset(); } });
};

const reopenForm = useForm({ reason: '' });
const reopening = ref(false);
const reopen = () => reopenForm.post(route('cases.reopen', { case: props.item.number }), { preserveScroll: true, onSuccess: () => { reopening.value = false; reopenForm.reset(); } });

const tone = { resolved: 'green', partially_resolved: 'sky', effective_action_started: 'violet', unresolved: 'amber', abandoned: 'gray' };
const statusTone = { pending: 'amber', confirmed: 'green', disputed: 'red' };
const status = computed(() => props.item.outcome?.confirmation_status ?? null);
const history = computed(() => props.item.outcome_history ?? []);
const showCloseHint = computed(() => props.item.can.record_outcome && props.item.outcome && status.value !== 'confirmed' && !props.item.can.close);
</script>

<template>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="min-w-0 space-y-6">
            <Card :title="$t('outcome.title')" :subtitle="$t('outcome.subtitle')">
                <template v-if="item.can.reopen" #actions>
                    <Button size="sm" variant="light" icon="refresh" @click="reopening = true">{{ $t('outcome2.reopen') }}</Button>
                </template>
                <div v-if="item.outcome" class="mb-6 rounded-2xl bg-[var(--surface-muted)] p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :tone="tone[item.outcome.outcome]">{{ $t(`outcome_type.${item.outcome.outcome}`) }}</Badge>
                        <Badge v-if="status" :tone="statusTone[status]" dot>{{ $t(`outcome2.status.${status}`) }}</Badge>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">{{ item.outcome.reason }}</p>
                    <p v-if="item.outcome.result_summary" class="mt-1 text-sm text-gray-500">{{ item.outcome.result_summary }}</p>
                    <p class="mt-2 text-xs text-gray-400">{{ item.outcome.recorded_by }} · {{ dateTime(item.outcome.created_at) }}</p>

                    <div v-if="status === 'pending'" class="mt-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                        <p class="flex items-start gap-2"><Icon name="clock" :size="16" class="mt-0.5 shrink-0" />{{ item.outcome.auto_confirm_on ? $t('outcome2.pending_text', { date: date(item.outcome.auto_confirm_on) }) : $t('outcome2.pending_text_no_date') }}</p>
                        <div v-if="item.can.confirm_outcome" class="mt-3 flex flex-wrap gap-2">
                            <Button size="sm" variant="success" icon="check" :loading="confirmation.processing && confirmation.confirm" @click="confirmOutcome(true)">{{ $t('outcome2.confirm') }}</Button>
                            <Button size="sm" variant="light" icon="alert" @click="disputing = true">{{ $t('outcome2.dispute') }}</Button>
                        </div>
                        <p v-if="confirmation.errors.confirm" class="mt-2 text-sm text-rose-600">{{ confirmation.errors.confirm }}</p>
                    </div>
                    <div v-else-if="status === 'disputed'" class="mt-4 rounded-2xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-200">
                        <p class="text-xs font-medium">{{ $t('outcome2.disputed_label') }}</p>
                        <p class="mt-1 whitespace-pre-line">{{ item.outcome.dispute_reason }}</p>
                    </div>
                    <p v-else-if="status === 'confirmed'" class="mt-4 flex items-center gap-2 text-sm text-emerald-700">
                        <Icon name="shield-check" :size="16" />
                        {{ item.outcome.confirmed_by ? $t('outcome2.confirmed_by', { name: item.outcome.confirmed_by, date: dateTime(item.outcome.confirmed_at) }) : $t('outcome2.auto_confirmed', { date: dateTime(item.outcome.confirmed_at) }) }}
                    </p>
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
                    <Field v-model="form.result_summary" as="textarea" :rows="2" :label="$t('outcome.result_summary')" :error="form.errors.result_summary" />
                    <div class="flex flex-wrap gap-2">
                        <Button type="submit" icon="flag" :loading="form.processing">{{ $t('outcome.save') }}</Button>
                        <Button v-if="item.can.close" variant="light" icon="lock" :loading="closing" @click="close">{{ $t('outcome.close_case') }}</Button>
                    </div>
                    <p v-if="showCloseHint" class="flex items-start gap-2 text-xs text-gray-500"><Icon name="info" :size="14" class="mt-0.5 shrink-0" />{{ $t('outcome2.close_hint') }}</p>
                </form>
                <template v-else>
                    <p v-if="!item.outcome" class="text-sm text-gray-500">{{ $t('outcome.none') }}</p>
                    <Button v-if="item.can.close" variant="light" icon="lock" :loading="closing" @click="close">{{ $t('outcome.close_case') }}</Button>
                </template>
            </Card>

            <Card v-if="history.length" :title="$t('outcome2.history')" :subtitle="$t('outcome2.history_hint')">
                <ul class="space-y-3">
                    <li v-for="(h, i) in history" :key="i" class="rounded-2xl p-3 ring-1 ring-[var(--border)]">
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge :tone="tone[h.outcome]">{{ $t(`outcome_type.${h.outcome}`) }}</Badge>
                            <Badge v-if="h.status" :tone="statusTone[h.status] ?? 'gray'">{{ $t(`outcome2.status.${h.status}`) }}</Badge>
                        </div>
                        <p v-if="h.reason" class="mt-2 text-sm text-gray-700">{{ h.reason }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ h.recorded_by }} · {{ dateTime(h.created_at) }}</p>
                    </li>
                </ul>
            </Card>
        </div>

        <Card v-if="item.can.rate" :title="$t('survey.title')" :subtitle="$t('survey.subtitle')">
            <form class="space-y-5" @submit.prevent="submitSurvey">
                <div class="flex gap-1" role="radiogroup" :aria-label="$t('fields.rating')">
                    <button v-for="n in 5" :key="n" type="button" class="rounded-xl p-1.5 transition hover:scale-110" :class="n <= survey.rating ? 'text-amber-500' : 'text-gray-300'" :aria-label="`${n}`" @click="survey.rating = n"><Icon name="star" :size="30" :class="n <= survey.rating ? 'fill-current' : ''" /></button>
                </div>
                <p v-if="survey.errors.rating" class="text-sm text-rose-600">{{ survey.errors.rating }}</p>
                <Field
                    v-if="needsDissatisfactionReason"
                    v-model="survey.dissatisfaction_reason"
                    as="select"
                    required
                    :label="$t('dissatisfaction.label')"
                    :hint="$t('dissatisfaction.hint')"
                    :placeholder="$t('dissatisfaction.placeholder')"
                    :options="dissatisfactionReasons.map((r) => ({ value: r, label: t(`dissatisfaction.reasons.${r}`) }))"
                    :error="survey.errors.dissatisfaction_reason"
                />
                <div v-for="q in ['problem_solved', 'would_recommend_expert']" :key="q">
                    <p class="label">{{ $t(`survey.${q}`) }}</p>
                    <div class="flex gap-2">
                        <button v-for="v in [true, false]" :key="String(v)" type="button" class="rounded-full px-5 py-2 text-sm ring-1" :class="survey[q] === v ? 'bg-navy-950 text-white ring-navy-950' : 'ring-[var(--border)]'" @click="survey[q] = v">{{ v ? $t('common.yes') : $t('common.no') }}</button>
                    </div>
                </div>
                <Field v-model="survey.comment" as="textarea" :rows="3" :label="$t('survey.comment')" :error="survey.errors.comment" />
                <Button type="submit" icon="star" :loading="survey.processing" :disabled="!survey.rating || (needsDissatisfactionReason && !survey.dissatisfaction_reason)">{{ $t('survey.submit') }}</Button>
                <p v-if="item.survey" class="text-xs text-emerald-700">{{ $t('survey.thanks') }}</p>
            </form>
        </Card>

        <Modal :show="disputing" :title="$t('outcome2.dispute_title')" @close="disputing = false">
            <Field v-model="confirmation.reason" as="textarea" :rows="4" required :label="$t('outcome2.dispute_reason')" :hint="$t('outcome2.dispute_hint')" :error="confirmation.errors.reason" />
            <template #footer>
                <Button variant="ghost" no-icon @click="disputing = false">{{ $t('common.cancel') }}</Button>
                <Button variant="danger" icon="alert" :loading="confirmation.processing && !confirmation.confirm" :disabled="!confirmation.reason.trim()" @click="confirmOutcome(false)">{{ $t('outcome2.dispute') }}</Button>
            </template>
        </Modal>
        <Modal :show="reopening" :title="$t('outcome2.reopen_title')" @close="reopening = false">
            <Field v-model="reopenForm.reason" as="textarea" :rows="4" required :label="$t('outcome2.reopen_reason')" :hint="$t('outcome2.reopen_hint')" :error="reopenForm.errors.reason" />
            <template #footer>
                <Button variant="ghost" no-icon @click="reopening = false">{{ $t('common.cancel') }}</Button>
                <Button icon="refresh" :loading="reopenForm.processing" :disabled="reopenForm.reason.trim().length < 5" @click="reopen">{{ $t('outcome2.reopen') }}</Button>
            </template>
        </Modal>
    </div>
</template>
