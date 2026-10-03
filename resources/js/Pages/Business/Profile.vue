<script setup>
import { computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import Uploader from '@/Components/ui/Uploader.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import { route } from '@/i18n';

const props = defineProps({ business: Object, canEdit: { type: Boolean, default: true } });
const opts = computed(() => usePage().props.options);
const b = props.business;
const form = useForm({
    trade_name: b.trade_name, legal_name: b.legal_name, registration_number: b.registration_number, founded_year: b.founded_year, website: b.website,
    description: b.description, products_services: b.products_services, industry: b.industry, size: b.size, employees_range: b.employees_range,
    country: b.country, province: b.province, city: b.city, address: b.address, contact_name: b.contact_name, contact_email: b.contact_email,
    contact_phone: b.contact_phone, preferred_language: b.preferred_language, main_needs: b.main_needs ?? [], privacy: { ...b.privacy },
});
const docs = useForm({ documents: [], document_type: 'other' });
const levels = ['private', 'case_team', 'verified_experts', 'public'];
</script>

<template>
    <AppLayout :title="$t('nav.business_profile')">
        <p v-if="!canEdit" class="mb-6 flex items-start gap-2 rounded-2xl bg-sky-50 p-4 text-sm leading-6 text-sky-900 ring-1 ring-sky-200"><Icon name="info" :size="16" class="mt-1 shrink-0" />{{ $t('team.profile_readonly') }}</p>
        <form class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]" @submit.prevent="canEdit && form.put(route('business.profile.update'), { preserveScroll: true })">
            <fieldset :disabled="!canEdit" class="contents">
            <div class="space-y-6">
                <Card :title="$t('onboarding.steps.basics')">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field v-model="form.trade_name" :label="$t('fields.trade_name')" required :error="form.errors.trade_name" />
                        <Field v-model="form.legal_name" :label="$t('fields.legal_name')" :error="form.errors.legal_name" />
                        <Field v-model="form.registration_number" :label="$t('fields.registration_number')" dir="ltr" :error="form.errors.registration_number" />
                        <Field v-model="form.founded_year" :label="$t('fields.founded_year')" type="number" dir="ltr" :error="form.errors.founded_year" />
                        <Field v-model="form.website" class="sm:col-span-2" :label="$t('fields.website')" dir="ltr" :error="form.errors.website" />
                        <Field v-model="form.description" class="sm:col-span-2" as="textarea" :label="$t('fields.description')" :error="form.errors.description" />
                        <Field v-model="form.products_services" class="sm:col-span-2" as="textarea" :rows="3" :label="$t('fields.products_services')" />
                    </div>
                </Card>
                <Card :title="$t('onboarding.steps.industry')">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field v-model="form.industry" as="select" :options="opts.industries" :label="$t('fields.industry')" required :error="form.errors.industry" />
                        <Field v-model="form.size" as="select" :options="opts.sizes" :label="$t('fields.size')" required :error="form.errors.size" />
                        <Field v-model="form.employees_range" as="select" :options="opts.employee_ranges" :label="$t('fields.employees_range')" required />
                        <Field v-model="form.country" as="select" :options="opts.countries" :label="$t('fields.country')" required />
                        <Field v-model="form.province" :as="form.country === 'IR' ? 'select' : 'input'" :options="opts.provinces" :label="$t('fields.province')" />
                        <Field v-model="form.city" :label="$t('fields.city')" />
                        <Field v-model="form.address" class="sm:col-span-2" as="textarea" :rows="2" :label="$t('fields.address')" />
                    </div>
                </Card>
                <Card :title="$t('onboarding.steps.contact')">
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <Field v-model="form.contact_name" :label="$t('fields.contact_name')" required :error="form.errors.contact_name" />
                        <Field v-model="form.contact_email" :label="$t('fields.contact_email')" dir="ltr" required :error="form.errors.contact_email" />
                        <Field v-model="form.contact_phone" :label="$t('fields.contact_phone')" dir="ltr" required :error="form.errors.contact_phone" />
                        <Field v-model="form.preferred_language" as="select" :options="opts.languages" :label="$t('fields.preferred_language')" required />
                    </div>
                    <div class="mt-5"><p class="label">{{ $t('fields.main_needs') }}</p><ChoiceChips v-model="form.main_needs" :options="opts.needs" multiple /></div>
                </Card>
            </div>
            <div class="space-y-6">
                <Card :title="$t('onboarding.steps.privacy')" :subtitle="$t('onboarding.privacy_note')">
                    <div class="space-y-4">
                        <div v-for="(level, field) in form.privacy" :key="field">
                            <label class="label">{{ $t(`fields.${field}`) }}</label>
                            <select v-model="form.privacy[field]" class="input"><option v-for="l in levels" :key="l" :value="l">{{ $t(`privacy_levels.${l}`) }}</option></select>
                        </div>
                    </div>
                </Card>
                <Card :title="$t('onboarding.steps.documents')">
                    <ul class="mb-4 space-y-2">
                        <li v-for="d in business.documents" :key="d.id" class="flex items-center gap-2 rounded-2xl bg-[var(--surface-muted)] px-3 py-2 text-sm">
                            <Icon name="file" :size="16" class="text-navy-600" />
                            <a v-if="d.url" :href="d.url" class="min-w-0 flex-1 truncate hover:underline">{{ d.name }}</a>
                            <span v-else class="min-w-0 flex-1 truncate">{{ d.name }}</span>
                            <Badge :tone="d.scan_status === 'infected' ? 'red' : 'gray'">{{ $t(`scan.${d.scan_status}`) }}</Badge>
                            <button v-if="canEdit" type="button" class="text-gray-400 hover:text-rose-600" :aria-label="$t('common.delete')" @click="router.delete(route('business.documents.destroy', { document: d.id }), { preserveScroll: true })"><Icon name="trash" :size="15" /></button>
                        </li>
                    </ul>
                    <Uploader v-if="canEdit" v-model="docs.documents" compact />
                    <Button v-if="docs.documents.length" class="mt-3" size="sm" icon="upload" :loading="docs.processing" @click="docs.post(route('business.documents.store'), { forceFormData: true, preserveScroll: true, onSuccess: () => docs.reset() })">{{ $t('common.upload') }}</Button>
                </Card>
                <div v-if="canEdit" class="sticky bottom-24 lg:bottom-6"><Button type="submit" block size="lg" :loading="form.processing" icon="check">{{ $t('common.save') }}</Button></div>
            </div>
            </fieldset>
        </form>
    </AppLayout>
</template>
