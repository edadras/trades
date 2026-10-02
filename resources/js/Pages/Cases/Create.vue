<script setup>
// Problem intake: free-form text and/or voice + files, then a short AI follow-up conversation, then submit.
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Field from '@/Components/ui/Field.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Uploader from '@/Components/ui/Uploader.vue';
import VoiceRecorder from '@/Components/ui/VoiceRecorder.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Stepper from '@/Components/ui/Stepper.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ draft: Object, example: String, partners: { type: Array, default: () => [] }, maxUploadKb: Number, accept: String });
const { t } = useI18n();

const start = useForm({
    description: '', voice: null, attachments: [], actions_taken: '', partner_id: '',
    consent_ai_processing: true, consent_share_with_foreign_experts: true, consent_anonymized_learning: false,
});
/** Booleans are sent as 1/0 because the request is multipart (FormData). */
const submitStart = () => start
    .transform((data) => ({
        ...data,
        partner_id: data.partner_id || null,
        consent_ai_processing: data.consent_ai_processing ? 1 : 0,
        consent_share_with_foreign_experts: data.consent_share_with_foreign_experts ? 1 : 0,
        consent_anonymized_learning: data.consent_anonymized_learning ? 1 : 0,
    }))
    .post(route('cases.store'), { forceFormData: true });
const consents = ['ai_processing', 'share_with_foreign_experts', 'anonymized_learning'];

const pending = computed(() => props.draft?.answers.find((a) => a.answer === null));
const answer = useForm({ key: '', answer: '', attachments: [] });
const showAttach = ref(false);
const sendAnswer = (skip = false) => {
    answer.key = pending.value.key;
    if (skip) answer.answer = '';
    answer.post(route('cases.intake.answer', { case: props.draft.number }), { forceFormData: true, preserveScroll: true, onSuccess: () => { answer.reset(); showAttach.value = false; } });
};
const submitting = ref(false);
const submitCase = () => router.post(route('cases.submit', { case: props.draft.number }), {}, { onStart: () => (submitting.value = true), onFinish: () => (submitting.value = false) });

const thread = ref(null);
const scrollDown = () => nextTick(() => thread.value?.scrollTo({ top: thread.value.scrollHeight, behavior: 'smooth' }));
onMounted(scrollDown);
watch(() => props.draft?.answers.length, scrollDown);

const steps = computed(() => ['submit', 'complete_info', 'ai_analysis', 'expert_review', 'choose_supporter', 'action', 'result'].map((key, i) => ({ key, state: i === 0 ? (props.draft ? 'done' : 'current') : i === 1 && props.draft ? 'current' : 'upcoming' })));
const examples = computed(() => [t('intake.example_1'), t('intake.example_2'), t('intake.example_3')]);
</script>

<template>
    <AppLayout :title="$t('intake.title')" :back="route('cases.index')">
        <div class="mx-auto max-w-3xl">
            <div class="card mb-6 px-3 py-4"><Stepper :steps="steps" /></div>

            <!-- Step 1: describe the problem -->
            <form v-if="!draft" class="card p-5 sm:p-8" @submit.prevent="submitStart">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="sparkles" /></span>
                    <div>
                        <h2 class="text-xl font-semibold text-ink">{{ $t('intake.question') }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ $t('intake.hint') }}</p>
                    </div>
                </div>
                <Field v-model="start.description" as="textarea" :rows="6" class="mt-6" :placeholder="$t('intake.placeholder')" :error="start.errors.description" />
                <div class="mt-3 flex flex-wrap gap-2">
                    <button v-for="(ex, i) in examples" :key="i" type="button" class="rounded-full bg-navy-50 px-3 py-1.5 text-start text-xs text-navy-800 hover:bg-navy-100" @click="start.description = ex">{{ ex }}</button>
                </div>
                <div class="mt-6 grid gap-4">
                    <div>
                        <p class="label">{{ $t('intake.or_voice') }}</p>
                        <VoiceRecorder v-model="start.voice" />
                        <p v-if="start.errors.voice" class="mt-1 text-sm text-rose-600">{{ start.errors.voice }}</p>
                    </div>
                    <div>
                        <p class="label">{{ $t('intake.attachments') }}</p>
                        <Uploader v-model="start.attachments" :accept="accept" :max-kb="maxUploadKb" :error="start.errors['attachments.0']" />
                    </div>
                    <Field v-model="start.actions_taken" as="textarea" :rows="3" :label="$t('lifecycle.actions_taken_label')" :hint="$t('lifecycle.actions_taken_hint')" :placeholder="$t('lifecycle.actions_taken_placeholder')" :error="start.errors.actions_taken" />
                    <Field v-if="partners.length" v-model="start.partner_id" as="select" :label="$t('lifecycle.referral_partner')" :hint="$t('lifecycle.referral_hint')" :placeholder="$t('lifecycle.referral_none')" :options="partners" :error="start.errors.partner_id" />
                </div>
                <fieldset class="mt-6 rounded-3xl bg-[var(--surface-muted)] p-4 ring-1 ring-[var(--border)] sm:p-5">
                    <legend class="sr-only">{{ $t('data_consent.title') }}</legend>
                    <div class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-2xl bg-white text-navy-800 ring-1 ring-[var(--border)]"><Icon name="shield-check" :size="18" /></span>
                        <div class="min-w-0">
                            <p class="font-semibold text-ink">{{ $t('data_consent.title') }}</p>
                            <p class="mt-0.5 text-xs leading-6 text-gray-500">{{ $t('data_consent.subtitle') }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2">
                        <div v-for="c in consents" :key="c" class="rounded-2xl bg-white p-3 ring-1 ring-[var(--border)]">
                            <Checkbox v-model="start[`consent_${c}`]" :label="$t(`data_consent.${c}`)" :description="$t(`data_consent.${c}_hint`)" :error="start.errors[`consent_${c}`]" />
                        </div>
                    </div>
                </fieldset>
                <div class="mt-8 flex flex-col-reverse items-stretch justify-between gap-3 border-t border-[var(--border)] pt-6 sm:flex-row sm:items-center">
                    <p class="flex items-center gap-2 text-xs text-gray-500"><Icon name="lock" :size="14" />{{ $t('intake.privacy') }}</p>
                    <Button type="submit" size="lg" icon="arrow" :loading="start.processing" :disabled="!start.description && !start.voice">{{ $t('common.continue') }}</Button>
                </div>
            </form>

            <!-- Step 2: AI follow-up conversation -->
            <section v-else class="card flex flex-col overflow-hidden">
                <header class="flex items-center justify-between gap-3 border-b border-[var(--border)] px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="sparkles" :size="18" /></span>
                        <div>
                            <p class="font-semibold text-ink">{{ $t('intake.assistant') }}</p>
                            <p class="text-xs text-gray-500" dir="ltr">{{ draft.number }}</p>
                        </div>
                    </div>
                    <span class="chip bg-violet-50 text-violet-700">{{ $t('provenance.ai_suggested') }}</span>
                </header>
                <div ref="thread" class="max-h-[58vh] min-h-72 space-y-4 overflow-y-auto bg-[var(--surface-muted)] p-4 sm:p-6">
                    <div class="flex justify-end">
                        <div class="max-w-[85%] rounded-3xl rounded-ee-md bg-navy-950 px-4 py-3 text-[15px] leading-7 text-white">
                            <p v-if="draft.description" class="whitespace-pre-line">{{ draft.description }}</p>
                            <p v-if="draft.has_voice" class="mt-1 flex items-center gap-1.5 text-sm text-white/80"><Icon name="mic" :size="14" />{{ $t('intake.voice_attached') }}</p>
                            <p v-for="d in draft.documents" :key="d.id" class="mt-1 flex items-center gap-1.5 text-sm text-white/80"><Icon name="paperclip" :size="14" />{{ d.original_name }}</p>
                        </div>
                    </div>
                    <template v-for="a in draft.answers" :key="a.key">
                        <div class="flex items-end gap-2">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white text-navy-800 ring-1 ring-[var(--border)]"><Icon name="sparkles" :size="15" /></span>
                            <div class="max-w-[85%] rounded-3xl rounded-es-md bg-white px-4 py-3 text-[15px] leading-7 text-ink ring-1 ring-[var(--border)]">{{ a.question }}</div>
                        </div>
                        <div v-if="a.answer !== null" class="flex justify-end">
                            <div class="max-w-[85%] rounded-3xl rounded-ee-md bg-navy-950 px-4 py-3 text-[15px] leading-7 text-white">{{ a.answer === '—' ? $t('intake.skipped') : a.answer }}</div>
                        </div>
                    </template>
                    <div v-if="answer.processing" class="flex items-center gap-2 text-sm text-gray-500"><span class="flex gap-1"><span v-for="i in 3" :key="i" class="size-1.5 animate-bounce rounded-full bg-navy-400" :style="{ animationDelay: `${i * 120}ms` }" /></span>{{ $t('intake.thinking') }}</div>
                    <div v-if="!pending" class="rounded-3xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ $t('intake.complete') }}</div>
                </div>
                <form v-if="pending" class="space-y-3 border-t border-[var(--border)] p-4" @submit.prevent="sendAnswer()">
                    <div class="flex items-end gap-2">
                        <button type="button" class="grid size-11 shrink-0 place-items-center rounded-full text-gray-500 hover:bg-gray-100" :aria-label="$t('intake.attach')" @click="showAttach = !showAttach"><Icon name="paperclip" /></button>
                        <textarea v-model="answer.answer" rows="1" class="input max-h-40 min-h-11 flex-1 resize-none py-2.5" :placeholder="$t('intake.answer_placeholder')" @keydown.enter.exact.prevent="answer.answer && sendAnswer()" />
                        <Button type="submit" icon-only icon="send" :loading="answer.processing" :disabled="!answer.answer" :aria-label="$t('common.send')" />
                    </div>
                    <Uploader v-if="showAttach" v-model="answer.attachments" compact :accept="accept" :max-kb="maxUploadKb" />
                    <button type="button" class="text-xs text-gray-500 hover:text-ink" @click="sendAnswer(true)">{{ $t('intake.skip_question') }}</button>
                </form>
                <footer class="flex flex-col gap-3 border-t border-[var(--border)] bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-gray-500">{{ pending ? $t('intake.can_submit_now') : $t('intake.ready') }}</p>
                    <Button :variant="pending ? 'light' : 'primary'" icon="check" :loading="submitting" @click="submitCase">{{ $t('intake.submit') }}</Button>
                </footer>
            </section>
            <p class="mt-4 text-center text-xs text-gray-400">{{ example }}</p>
        </div>
    </AppLayout>
</template>
