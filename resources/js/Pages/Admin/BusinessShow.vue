<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ business: Object, cases: Array, canManagePilot: Boolean });
const { t, option } = useI18n();
const fields = ['legal_name', 'registration_number', 'founded_year', 'website', 'contact_name', 'contact_email', 'contact_phone', 'address'];

const OVERRIDE_PREFIX = 'override:';
const eligibilityTones = { eligible: 'green', waitlisted: 'amber', ineligible: 'red' };
const eligibilityReasons = computed(() => {
    const reason = props.business.eligibility_reason?.trim();
    if (!reason) {
        return [];
    }
    if (reason.startsWith(OVERRIDE_PREFIX)) {
        const text = reason.slice(OVERRIDE_PREFIX.length).trim();
        return [text ? `${t('eligibility.override')}: ${text}` : t('eligibility.override')];
    }
    const keys = reason.split(',').map((k) => k.trim()).filter(Boolean);
    const known = ['region', 'industry', 'size', 'capacity'];
    return keys.every((k) => known.includes(k)) ? keys.map((k) => t(`eligibility.reasons.${k}`)) : [reason];
});
const eligibilityForm = useForm({ eligibility_status: props.business.eligibility_status ?? 'eligible', eligibility_reason: '' });
const statusOptions = ['eligible', 'waitlisted', 'ineligible'].map((s) => ({ value: s, label: t(`eligibility.status.${s}`) }));
function saveEligibility() {
    eligibilityForm
        .transform((data) => ({ ...data, eligibility_reason: data.eligibility_reason.trim() ? `${OVERRIDE_PREFIX} ${data.eligibility_reason.trim()}` : OVERRIDE_PREFIX }))
        .post(route('admin.businesses.eligibility', { business: props.business.id }), { preserveScroll: true, onSuccess: () => eligibilityForm.reset('eligibility_reason') });
}
</script>

<template>
    <AppLayout :title="business.trade_name" :back="route('admin.businesses.index')" wide>
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
            <div class="space-y-6">
                <Card :title="$t('nav.business_profile')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.industry') }}</dt><dd>{{ option('industries', business.industry) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.size') }}</dt><dd>{{ option('sizes', business.size) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.province') }}</dt><dd>{{ option('provinces', business.province) || business.province }}</dd></div>
                        <div v-for="f in fields" :key="f" class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t(`fields.${f}`) }}</dt><dd class="text-end break-all">{{ business[f] ?? '—' }}</dd></div>
                    </dl>
                    <p class="mt-4 flex items-center gap-2 text-xs text-gray-500"><Icon name="eye" :size="14" />{{ $t('admin.view_audited') }}</p>
                </Card>
                <Card :title="$t('eligibility.title')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ $t('table.status') }}</dt>
                            <dd><Badge :tone="eligibilityTones[business.eligibility_status] ?? 'gray'" dot>{{ business.eligibility_status ? $t(`eligibility.status.${business.eligibility_status}`) : $t('eligibility.not_evaluated') }}</Badge></dd>
                        </div>
                        <div v-if="eligibilityReasons.length" class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-500">{{ $t('eligibility.reason') }}</dt>
                            <dd class="min-w-0 text-end"><p v-for="r in eligibilityReasons" :key="r" class="break-words">{{ r }}</p></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">{{ $t('eligibility.partner') }}</dt>
                            <dd class="text-end">{{ business.partner || $t('eligibility.no_partner') }}</dd>
                        </div>
                    </dl>
                    <form v-if="canManagePilot" class="mt-5 space-y-4 border-t border-[var(--border)] pt-5" @submit.prevent="saveEligibility">
                        <p class="text-sm font-medium text-ink">{{ $t('eligibility.override_title') }}</p>
                        <Field v-model="eligibilityForm.eligibility_status" as="select" :label="$t('eligibility.override_status')" :options="statusOptions" required :error="eligibilityForm.errors.eligibility_status" />
                        <Field v-model="eligibilityForm.eligibility_reason" as="textarea" :rows="2" maxlength="480" :label="$t('eligibility.override_reason')" :hint="$t('eligibility.override_reason_hint')" :error="eligibilityForm.errors.eligibility_reason" />
                        <div class="flex justify-end"><Button type="submit" size="sm" icon="check" :loading="eligibilityForm.processing">{{ $t('eligibility.save') }}</Button></div>
                    </form>
                </Card>
                <Card :title="$t('onboarding.steps.documents')">
                    <ul class="space-y-2 text-sm"><li v-for="d in business.documents" :key="d.id"><a v-if="d.url" :href="d.url" class="flex items-center gap-2 hover:underline"><Icon name="file" :size="16" />{{ d.name }}</a></li></ul>
                </Card>
            </div>
            <div class="grid content-start gap-4 md:grid-cols-2"><CaseCard v-for="c in cases" :key="c.id" :item="c" :href="route('review.cases.show', { case: c.number })" /></div>
        </div>
    </AppLayout>
</template>
