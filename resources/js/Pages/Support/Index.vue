<script setup>
// Support requests and complaints for any signed-in user, with the history of previous requests.
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ complaints: Array, cases: { type: Array, default: () => [] }, categories: Array });
const { t, date } = useI18n();

const form = useForm({ category: props.categories?.[0] ?? 'other', subject: '', body: '', case_id: '' });
const categoryOptions = computed(() => props.categories.map((c) => ({ value: c, label: t(`support.categories.${c}`) })));
const caseOptions = computed(() => [{ value: '', label: t('support.no_case') }, ...props.cases]);
const statusTone = { open: 'sky', in_review: 'amber', resolved: 'green', rejected: 'red' };

const submit = () =>
    form
        .transform((data) => ({ ...data, case_id: data.case_id || null }))
        .post(route('support.store'), { preserveScroll: true, onSuccess: () => form.reset('subject', 'body', 'case_id') });
</script>

<template>
    <AppLayout :title="$t('support.title')" :subtitle="$t('support.subtitle')">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
            <Card :title="$t('support.new_title')">
                <div class="mb-5 flex items-start gap-3 rounded-2xl bg-navy-50 p-4 text-sm leading-6 text-navy-900">
                    <Icon name="shield-check" class="mt-0.5 shrink-0 text-navy-700" />
                    <p>{{ $t('support.confidential') }}</p>
                </div>
                <form class="space-y-4" @submit.prevent="submit">
                    <Field v-model="form.category" as="select" :options="categoryOptions" :label="$t('support.category')" required :error="form.errors.category" />
                    <Field v-if="cases.length" v-model="form.case_id" as="select" :options="caseOptions" :label="$t('support.related_case')" :error="form.errors.case_id" />
                    <Field v-model="form.subject" :label="$t('support.subject')" maxlength="200" required :error="form.errors.subject" />
                    <Field v-model="form.body" as="textarea" :rows="6" :label="$t('support.body')" :hint="$t('support.body_hint')" maxlength="5000" required :error="form.errors.body" />
                    <Button type="submit" block icon="send" :loading="form.processing">{{ $t('support.submit') }}</Button>
                </form>
            </Card>

            <section>
                <h2 class="mb-4 text-lg font-semibold text-ink">{{ $t('support.history') }}</h2>
                <ul v-if="complaints.length" class="space-y-3">
                    <li v-for="c in complaints" :key="c.id" class="card p-5">
                        <div class="flex flex-wrap items-start gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink">{{ c.subject }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $t(`support.categories.${c.category}`) }} · {{ date(c.created_at) }}
                                    <template v-if="c.case_number"> · <span dir="ltr">{{ $t('support.case', { number: c.case_number }) }}</span></template>
                                </p>
                            </div>
                            <Badge :tone="statusTone[c.status] ?? 'gray'" dot>{{ $t(`support.statuses.${c.status}`) }}</Badge>
                        </div>
                        <p class="mt-3 line-clamp-3 whitespace-pre-line text-sm leading-6 text-gray-600">{{ c.body }}</p>
                        <div v-if="c.resolution" class="mt-3 rounded-2xl bg-emerald-50 p-3 text-sm ring-1 ring-emerald-200">
                            <p class="text-xs font-medium text-emerald-800">{{ $t('support.resolution') }}<template v-if="c.resolved_at"> · {{ $t('support.resolved_at', { date: date(c.resolved_at) }) }}</template></p>
                            <p class="mt-1 whitespace-pre-line leading-6 text-emerald-900">{{ c.resolution }}</p>
                        </div>
                    </li>
                </ul>
                <EmptyState v-else icon="inbox" :title="$t('support.empty')" />
            </section>
        </div>
    </AppLayout>
</template>
