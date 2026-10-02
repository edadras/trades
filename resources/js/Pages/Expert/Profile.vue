<script setup>
// Expert membership application & profile: expertise, experience, languages, availability, NDA, documents.
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ profile: Object, categories: Array, collaborationTypes: Array });
const page = usePage();
const { t } = useI18n();
const opts = computed(() => page.props.options);
const p = props.profile ?? {};
const form = useForm({
    headline: p.headline ?? '', bio: p.bio ?? '', country: p.country ?? 'IR', city: p.city ?? '', timezone: p.timezone ?? 'Asia/Tehran',
    years_experience: p.years_experience ?? 5, industries: p.industries ?? [], serves_countries: p.serves_countries ?? ['IR'],
    collaboration_types: p.collaboration_types ?? ['consultation'], certifications: p.certifications ?? [], linkedin_url: p.linkedin_url ?? '',
    max_active_cases: p.max_active_cases ?? 5, is_available: p.is_available ?? true,
    skills: p.skills?.length ? p.skills : [{ case_category_id: '', level: 3, years: 3 }],
    languages: p.languages?.length ? p.languages : [{ language: 'fa', proficiency: 'native' }],
    availability: p.availability ?? [],
});
const submitForm = useForm({ nda: false });
const doc = useForm({ file: null, type: 'certificate', title: '' });
const catOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: (c.parent_id ? '— ' : '') + c.name })));
const weekdays = [0, 1, 2, 3, 4, 5, 6].map((d) => ({ value: d, label: t(`weekdays.${d}`) }));
const statusTone = { draft: 'gray', submitted: 'amber', in_review: 'amber', verified: 'green', rejected: 'red', suspended: 'red' };
const canSubmit = computed(() => p.verification_status && ['draft', 'rejected'].includes(p.verification_status));
const save = () => form.put(route('expert.profile.update'), { preserveScroll: true });
</script>

<template>
    <AppLayout :title="$t('nav.expert_profile')">
        <div class="mb-6 flex flex-wrap items-center gap-3 rounded-[var(--radius-card)] bg-white p-4 ring-1 ring-[var(--border)]">
            <span class="grid size-11 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="shield-check" /></span>
            <div class="flex-1">
                <p class="text-sm text-gray-500">{{ $t('expert_profile.status') }}</p>
                <Badge :tone="statusTone[p.verification_status ?? 'draft']">{{ $t(`expert_status.${p.verification_status ?? 'new'}`) }}</Badge>
            </div>
            <p v-if="p.feedback" class="w-full rounded-2xl bg-amber-50 p-3 text-sm text-amber-900">{{ p.feedback }}</p>
        </div>
        <form class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]" @submit.prevent="save">
            <div class="space-y-6">
                <Card :title="$t('expert_profile.about')">
                    <div class="space-y-5">
                        <Field v-model="form.headline" :label="$t('fields.headline')" required :error="form.errors.headline" />
                        <Field v-model="form.bio" as="textarea" :rows="5" :label="$t('fields.bio')" required :error="form.errors.bio" />
                        <div class="grid gap-5 sm:grid-cols-3">
                            <Field v-model="form.country" as="select" :options="opts.countries" :label="$t('fields.country')" required />
                            <Field v-model="form.city" :label="$t('fields.city')" />
                            <Field v-model="form.years_experience" type="number" :label="$t('fields.years_experience')" dir="ltr" required :error="form.errors.years_experience" />
                        </div>
                        <Field v-model="form.linkedin_url" :label="$t('fields.linkedin')" dir="ltr" placeholder="https://" :error="form.errors.linkedin_url" />
                    </div>
                </Card>
                <Card :title="$t('expert_profile.expertise')" :subtitle="$t('expert_profile.expertise_hint')">
                    <div class="space-y-3">
                        <div v-for="(s, i) in form.skills" :key="i" class="grid grid-cols-[minmax(0,1fr)_90px_90px_auto] items-end gap-2">
                            <Field v-model="s.case_category_id" as="select" :options="catOptions" :label="i === 0 ? $t('fields.expertise') : undefined" :error="form.errors[`skills.${i}.case_category_id`]" />
                            <Field v-model="s.level" as="select" :options="[1, 2, 3, 4, 5].map((n) => ({ value: n, label: String(n) }))" :label="i === 0 ? $t('fields.level') : undefined" />
                            <Field v-model="s.years" type="number" dir="ltr" :label="i === 0 ? $t('fields.years') : undefined" />
                            <button type="button" class="mb-2 grid size-10 place-items-center rounded-full text-gray-400 hover:bg-rose-50 hover:text-rose-600" :aria-label="$t('common.remove')" @click="form.skills.splice(i, 1)"><Icon name="trash" :size="16" /></button>
                        </div>
                        <Button size="sm" variant="light" icon="plus" @click="form.skills.push({ case_category_id: '', level: 3, years: 1 })">{{ $t('expert_profile.add_skill') }}</Button>
                        <p v-if="form.errors.skills" class="text-sm text-rose-600">{{ form.errors.skills }}</p>
                    </div>
                    <div class="mt-6"><p class="label">{{ $t('expert_profile.industries') }}</p><ChoiceChips v-model="form.industries" :options="opts.industries" multiple /></div>
                </Card>
                <Card :title="$t('expert_profile.languages')">
                    <div class="space-y-3">
                        <div v-for="(l, i) in form.languages" :key="i" class="grid grid-cols-[minmax(0,1fr)_1fr_auto] items-end gap-2">
                            <Field v-model="l.language" as="select" :options="[{ value: 'fa', label: $t('languages.fa') }, { value: 'en', label: $t('languages.en') }, { value: 'ar', label: $t('languages.ar') }, { value: 'tr', label: $t('languages.tr') }, { value: 'de', label: $t('languages.de') }]" />
                            <Field v-model="l.proficiency" as="select" :options="['native', 'fluent', 'professional', 'basic'].map((v) => ({ value: v, label: $t(`proficiency.${v}`) }))" />
                            <button type="button" class="mb-2 grid size-10 place-items-center rounded-full text-gray-400 hover:text-rose-600" @click="form.languages.splice(i, 1)"><Icon name="trash" :size="16" /></button>
                        </div>
                        <Button size="sm" variant="light" icon="plus" @click="form.languages.push({ language: 'en', proficiency: 'fluent' })">{{ $t('expert_profile.add_language') }}</Button>
                    </div>
                </Card>
                <Card :title="$t('expert_profile.certifications')">
                    <div class="space-y-3">
                        <div v-for="(c, i) in form.certifications" :key="i" class="grid grid-cols-[minmax(0,1fr)_1fr_90px_auto] items-end gap-2">
                            <Field v-model="c.title" :placeholder="$t('fields.title')" />
                            <Field v-model="c.issuer" :placeholder="$t('fields.issuer')" />
                            <Field v-model="c.year" type="number" dir="ltr" :placeholder="$t('fields.year')" />
                            <button type="button" class="mb-2 grid size-10 place-items-center rounded-full text-gray-400 hover:text-rose-600" @click="form.certifications.splice(i, 1)"><Icon name="trash" :size="16" /></button>
                        </div>
                        <Button size="sm" variant="light" icon="plus" @click="form.certifications.push({ title: '', issuer: '', year: null })">{{ $t('expert_profile.add_certification') }}</Button>
                    </div>
                </Card>
            </div>
            <div class="space-y-6">
                <Card :title="$t('expert_profile.collaboration')">
                    <ChoiceChips v-model="form.collaboration_types" :options="collaborationTypes.map((c) => ({ value: c, label: $t(`collab.${c}`) }))" multiple />
                    <div class="mt-5"><p class="label">{{ $t('expert_profile.serves') }}</p><ChoiceChips v-model="form.serves_countries" :options="opts.countries.slice(0, 8)" multiple /></div>
                    <Field v-model="form.max_active_cases" type="number" dir="ltr" class="mt-5" :label="$t('fields.max_active_cases')" />
                    <Checkbox v-model="form.is_available" class="mt-3" :label="$t('expert_profile.available')" />
                </Card>
                <Card :title="$t('expert_profile.availability')">
                    <div class="space-y-2">
                        <div v-for="(a, i) in form.availability" :key="i" class="grid grid-cols-[minmax(0,1fr)_90px_90px_auto] items-end gap-2">
                            <Field v-model="a.weekday" as="select" :options="weekdays" />
                            <Field v-model="a.starts_at" type="time" dir="ltr" />
                            <Field v-model="a.ends_at" type="time" dir="ltr" />
                            <button type="button" class="mb-2 grid size-10 place-items-center rounded-full text-gray-400 hover:text-rose-600" @click="form.availability.splice(i, 1)"><Icon name="trash" :size="16" /></button>
                        </div>
                        <Button size="sm" variant="light" icon="plus" @click="form.availability.push({ weekday: 0, starts_at: '09:00', ends_at: '13:00' })">{{ $t('expert_profile.add_slot') }}</Button>
                    </div>
                </Card>
                <Card v-if="profile" :title="$t('expert_profile.documents')">
                    <ul class="mb-3 space-y-2 text-sm"><li v-for="d in profile.documents" :key="d.id" class="flex items-center gap-2"><Icon name="file" :size="16" />{{ d.name }}</li></ul>
                    <div class="space-y-3">
                        <Field v-model="doc.type" as="select" :options="['certificate', 'cv', 'id', 'portfolio', 'other'].map((v) => ({ value: v, label: $t(`expert_doc.${v}`) }))" />
                        <input type="file" class="block w-full text-sm" @change="doc.file = $event.target.files[0]" />
                        <Button size="sm" icon="upload" :disabled="!doc.file" :loading="doc.processing" @click="doc.post(route('expert.profile.documents.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => doc.reset() })">{{ $t('common.upload') }}</Button>
                        <p v-if="doc.errors.file" class="text-sm text-rose-600">{{ doc.errors.file }}</p>
                    </div>
                </Card>
                <Button type="submit" block size="lg" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button>
                <Card v-if="canSubmit" :title="$t('expert_profile.submit_title')">
                    <p class="text-sm leading-6 text-gray-600">{{ $t('expert_profile.nda_text') }}</p>
                    <Checkbox v-model="submitForm.nda" class="mt-3" :label="$t('expert_profile.nda_accept')" :error="submitForm.errors.nda || submitForm.errors.skills" />
                    <Button class="mt-3" block icon="send" :disabled="!submitForm.nda" :loading="submitForm.processing" @click="submitForm.post(route('expert.profile.submit'), { preserveScroll: true })">{{ $t('expert_profile.submit') }}</Button>
                </Card>
            </div>
        </form>
    </AppLayout>
</template>
