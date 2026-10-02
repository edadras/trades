<script setup>
// Bilingual knowledge editor. Each language has title/summary/Markdown body plus the structured checklist
// (causes, actions, documents, warnings) that the AI guidance engine is allowed to compose from.
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import DateTimeField from '@/Components/ui/DateTimeField.vue';
import Button from '@/Components/ui/Button.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Badge from '@/Components/ui/Badge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import Markdown from '@/Components/domain/Markdown.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ article: Object, categories: Array, sources: Array, problemTypes: Array, types: Array, canApprove: Boolean });
const page = usePage();
const { t } = useI18n();
const a = props.article;
const emptyT = () => ({ title: '', summary: '', body: '', checklist: { causes: [], actions: [], documents: [], warnings: [] }, seo_title: '', seo_description: '' });
const form = useForm({
    slug: a?.slug ?? '', type: a?.type ?? 'article', knowledge_category_id: a?.knowledge_category_id ?? '', knowledge_source_id: a?.knowledge_source_id ?? '',
    country: a?.country ?? 'IR', industries: a?.industries ?? [], problem_types: a?.problem_types ?? [], tags: a?.tags ?? [], cover_image: a?.cover_image ?? '',
    video_url: a?.video_url ?? '', reading_minutes: a?.reading_minutes ?? 3, valid_until: a?.valid_until ?? '', is_featured: a?.is_featured ?? false,
    translations: { fa: a?.translations?.fa ?? emptyT(), en: a?.translations?.en ?? emptyT() },
});
const lang = ref('fa');
const preview = ref(false);
const checklistText = (k) => computed({ get: () => (form.translations[lang.value].checklist[k] ?? []).join('\n'), set: (v) => (form.translations[lang.value].checklist[k] = v.split('\n').map((x) => x.trim()).filter(Boolean)) });
const lists = { causes: checklistText('causes'), actions: checklistText('actions'), documents: checklistText('documents'), warnings: checklistText('warnings') };
const tagsText = computed({ get: () => form.tags.join(', '), set: (v) => (form.tags = v.split(/[,،]/).map((x) => x.trim()).filter(Boolean)) });
const save = () => (a ? form.put(route('admin.knowledge.update', { article: a.id }), { preserveScroll: true }) : form.post(route('admin.knowledge.store')));
const setStatus = (status) => router.post(route('admin.knowledge.status', { article: a.id }), { status }, { preserveScroll: true });
const tone = { draft: 'gray', in_review: 'amber', approved: 'green', archived: 'gray' };
</script>

<template>
    <AppLayout :title="a ? form.translations.fa.title || form.translations.en.title : $t('knowledge_admin.new')" :back="route('admin.knowledge.index')" wide>
        <form class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]" @submit.prevent="save">
            <div class="space-y-6">
                <Card>
                    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <Tabs v-model="lang" :tabs="[{ key: 'fa', label: 'فارسی' }, { key: 'en', label: 'English' }]" size="sm" />
                        <button type="button" class="text-sm font-medium text-navy-700" @click="preview = !preview">{{ preview ? $t('common.edit') : $t('knowledge_admin.preview') }}</button>
                    </div>
                    <div :dir="lang === 'fa' ? 'rtl' : 'ltr'" class="space-y-4">
                        <Field v-model="form.translations[lang].title" :label="$t('fields.title')" :error="form.errors[`translations.${lang}.title`]" />
                        <Field v-model="form.translations[lang].summary" as="textarea" :rows="2" :label="$t('knowledge_admin.summary')" />
                        <Markdown v-if="preview" :source="form.translations[lang].body" class="rounded-2xl bg-[var(--surface-muted)] p-5" />
                        <Field v-else v-model="form.translations[lang].body" as="textarea" :rows="14" :label="$t('knowledge_admin.body')" :hint="$t('knowledge_admin.markdown_hint')" class="font-mono" />
                    </div>
                </Card>
                <Card :title="$t('knowledge_admin.structured')" :subtitle="$t('knowledge_admin.structured_hint')">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2" :dir="lang === 'fa' ? 'rtl' : 'ltr'">
                        <Field v-for="k in ['causes', 'actions', 'documents', 'warnings']" :key="k" v-model="lists[k].value" as="textarea" :rows="4" :label="$t(`guidance.${k}`)" :hint="$t('knowledge_admin.one_per_line')" />
                    </div>
                </Card>
                <Card :title="$t('knowledge_admin.seo')">
                    <div class="grid gap-4" :dir="lang === 'fa' ? 'rtl' : 'ltr'">
                        <Field v-model="form.translations[lang].seo_title" :label="$t('knowledge_admin.seo_title')" />
                        <Field v-model="form.translations[lang].seo_description" as="textarea" :rows="2" :label="$t('knowledge_admin.seo_description')" />
                    </div>
                </Card>
            </div>
            <div class="space-y-6">
                <Card v-if="a" :title="$t('knowledge_admin.verification')">
                    <Badge :tone="tone[a.status]">{{ $t(`content_status.${a.status}`) }}</Badge>
                    <p class="mt-3 text-xs leading-5 text-gray-500">{{ $t('knowledge_admin.ai_rule') }}</p>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <Button v-if="a.status === 'draft'" size="sm" variant="light" icon="send" @click="setStatus('in_review')">{{ $t('knowledge_admin.send_review') }}</Button>
                        <Button v-if="canApprove && a.status !== 'approved'" size="sm" variant="success" icon="check" @click="setStatus('approved')">{{ $t('knowledge_admin.approve') }}</Button>
                        <Button v-if="a.status !== 'archived'" size="sm" variant="ghost" icon="trash" @click="setStatus('archived')">{{ $t('knowledge_admin.archive') }}</Button>
                        <Button v-if="a.status === 'approved'" :href="route('knowledge.show', { article: a.slug })" size="sm" variant="light" icon="external">{{ $t('common.view') }}</Button>
                    </div>
                </Card>
                <Card :title="$t('knowledge_admin.meta')">
                    <div class="space-y-4">
                        <Field v-model="form.type" as="select" :label="$t('table.type')" :options="types.map((x) => ({ value: x, label: $t(`content_type.${x}`) }))" required />
                        <Field v-model="form.knowledge_category_id" as="select" :label="$t('table.category')" :options="categories.map((c) => ({ value: c.id, label: c.name }))" required :error="form.errors.knowledge_category_id" />
                        <Field v-model="form.knowledge_source_id" as="select" :label="$t('knowledge.source')" :options="sources.map((s) => ({ value: s.id, label: s.name }))" />
                        <div class="grid grid-cols-2 gap-3">
                            <Field v-model="form.reading_minutes" type="number" dir="ltr" :label="$t('knowledge_admin.minutes')" />
                            <DateTimeField v-model="form.valid_until" mode="date" :label="$t('knowledge_admin.valid_until')" />
                        </div>
                        <Field v-model="form.country" as="select" :label="$t('fields.country')" :options="page.props.options.countries" />
                        <Field v-model="form.slug" label="Slug" dir="ltr" :error="form.errors.slug" />
                        <Field v-model="form.cover_image" :label="$t('knowledge_admin.cover')" dir="ltr" />
                        <Field v-model="form.video_url" :label="$t('knowledge_admin.video')" dir="ltr" :error="form.errors.video_url" />
                        <Field v-model="tagsText" :label="$t('knowledge_admin.tags')" />
                        <Checkbox v-model="form.is_featured" :label="$t('knowledge_admin.featured')" />
                    </div>
                </Card>
                <Card :title="$t('knowledge_admin.problem_types')" :subtitle="$t('knowledge_admin.problem_types_hint')">
                    <ChoiceChips v-model="form.problem_types" :options="problemTypes.map((c) => ({ value: c.id, label: c.name }))" multiple />
                </Card>
                <Card :title="$t('expert_profile.industries')"><ChoiceChips v-model="form.industries" :options="page.props.options.industries" multiple /></Card>
                <Button type="submit" block size="lg" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button>
                <p v-if="Object.keys(form.errors).length" class="text-sm text-rose-600">{{ Object.values(form.errors)[0] }}</p>
            </div>
        </form>
    </AppLayout>
</template>
