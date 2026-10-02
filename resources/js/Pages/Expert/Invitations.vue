<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Modal from '@/Components/ui/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import UrgencyBadge from '@/Components/domain/UrgencyBadge.vue';
import { route, useI18n } from '@/i18n';
defineProps({ invitations: Array });
const { relative, option, percent } = useI18n();
const supportModels = ['voluntary', 'free', 'subsidized', 'commercial'];
const form = useForm({ accept: true, reason: '', engagement_model: '', engagement_terms: '' });
const declining = ref(null);
const accepting = ref(null);
const respond = (inv, accept) => {
    form.accept = accept;
    form
        .transform((data) => (accept ? { accept: true, engagement_model: data.engagement_model, engagement_terms: data.engagement_terms || null } : { accept: false, reason: data.reason || null }))
        .post(route('expert.invitations.respond', { match: inv.id }), { preserveScroll: true, onSuccess: () => { declining.value = null; accepting.value = null; form.reset(); } });
};
/** Offer the expert's own support models when set, otherwise every model the platform supports. */
const modelOptions = computed(() => {
    const own = (accepting.value?.support_models ?? []).filter((m) => supportModels.includes(m));
    return own.length ? own : supportModels;
});
const openAccept = (inv) => {
    accepting.value = inv;
    form.clearErrors();
    form.engagement_model = inv.engagement_model ?? (modelOptions.value.length === 1 ? modelOptions.value[0] : '');
    form.engagement_terms = '';
};
const tone = { invited: 'amber', active: 'green', expert_declined: 'gray' };
</script>

<template>
    <AppLayout :title="$t('nav.invitations')" :subtitle="$t('invitations.subtitle')">
        <div v-if="invitations.length" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card v-for="inv in invitations" :key="inv.id">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs text-gray-400" dir="ltr">{{ inv.case.number }}</span>
                    <Badge :tone="tone[inv.status]">{{ $t(`match_status.${inv.status}`) }}</Badge>
                    <UrgencyBadge :urgency="inv.case.urgency" />
                    <span class="ms-auto rounded-full bg-navy-950 px-3 py-1 text-xs font-semibold text-white">{{ percent(Math.round(inv.score)) }}</span>
                </div>
                <h3 class="mt-3 font-semibold text-ink">{{ inv.case.category }}<template v-if="inv.case.subcategory"> · {{ inv.case.subcategory }}</template></h3>
                <p class="mt-2 text-sm leading-7 text-gray-600">{{ inv.case.summary }}</p>
                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    <Badge tone="gray">{{ option('industries', inv.case.business.industry) }}</Badge>
                    <Badge tone="gray">{{ option('sizes', inv.case.business.size) }}</Badge>
                    <Badge tone="gray">{{ option('provinces', inv.case.business.province) || option('countries', inv.case.business.country) }}</Badge>
                </div>
                <ul v-if="inv.reasons.length" class="mt-4 space-y-1 text-sm text-gray-700"><li v-for="(r, i) in inv.reasons" :key="i" class="flex gap-2"><Icon name="check" :size="15" class="mt-1 text-emerald-600" />{{ r }}</li></ul>
                <p class="mt-4 flex items-center gap-2 rounded-2xl bg-navy-50 p-3 text-xs text-navy-800"><Icon name="lock" :size="14" />{{ $t('invitations.confidential') }}</p>
                <p v-if="inv.status === 'invited' && inv.sensitive && inv.foreign" class="mt-2 flex items-start gap-2 rounded-2xl bg-amber-50 p-3 text-xs leading-6 text-amber-900 ring-1 ring-amber-200"><Icon name="globe" :size="14" class="mt-1 shrink-0" />{{ $t('engagement.cross_border_notice') }}</p>
                <p v-if="inv.status === 'active' && inv.engagement_model" class="mt-3 text-xs text-gray-500">{{ $t('team_case.engagement') }}: <Badge :tone="inv.engagement_model === 'commercial' ? 'amber' : 'navy'">{{ $t(`engagement.models.${inv.engagement_model}`) }}</Badge></p>
                <div v-if="inv.status === 'invited'" class="mt-4 flex gap-2 border-t border-[var(--border)] pt-4">
                    <Button icon="check" @click="openAccept(inv)">{{ $t('invitations.accept') }}</Button>
                    <Button variant="ghost" no-icon @click="declining = inv">{{ $t('invitations.decline') }}</Button>
                    <span class="ms-auto self-center text-xs text-gray-400">{{ relative(inv.invited_at) }}</span>
                </div>
                <Button v-else-if="inv.status === 'active'" :href="route('expert.cases.show', { case: inv.case.number })" class="mt-4" icon="arrow">{{ $t('invitations.open_case') }}</Button>
            </Card>
        </div>
        <EmptyState v-else icon="inbox" :title="$t('invitations.empty')" :text="$t('invitations.empty_hint')" />
        <Modal :show="!!accepting" :title="$t('engagement.accept_title')" @close="accepting = null">
            <div v-if="accepting" class="space-y-5">
                <div>
                    <p class="label">{{ $t('engagement.choose') }}<span class="text-rose-500"> *</span></p>
                    <div class="grid gap-2" role="radiogroup" :aria-label="$t('engagement.choose')">
                        <label v-for="m in modelOptions" :key="m" class="flex cursor-pointer items-start gap-3 rounded-2xl p-3 ring-1 transition" :class="form.engagement_model === m ? 'bg-navy-50 ring-navy-300' : 'ring-[var(--border)] hover:bg-gray-50'">
                            <input v-model="form.engagement_model" type="radio" :value="m" class="mt-1 accent-navy-900" />
                            <span><span class="block text-sm font-medium text-ink">{{ $t(`engagement.models.${m}`) }}</span><span class="block text-xs text-gray-500">{{ $t(`engagement.model_hints.${m}`) }}</span></span>
                        </label>
                    </div>
                    <p v-if="form.errors.engagement_model" class="mt-1.5 text-sm text-rose-600">{{ form.errors.engagement_model }}</p>
                </div>
                <p v-if="form.engagement_model === 'commercial'" class="flex items-start gap-2 rounded-2xl bg-amber-50 p-3 text-sm leading-6 text-amber-900 ring-1 ring-amber-200"><Icon name="scale" :size="16" class="mt-1 shrink-0" />{{ $t('engagement.commercial_notice') }}</p>
                <p v-if="accepting.sensitive && accepting.foreign" class="flex items-start gap-2 rounded-2xl bg-amber-50 p-3 text-sm leading-6 text-amber-900 ring-1 ring-amber-200"><Icon name="globe" :size="16" class="mt-1 shrink-0" />{{ $t('engagement.cross_border_notice') }}</p>
                <Field v-model="form.engagement_terms" as="textarea" :rows="3" :label="$t('engagement.terms')" :placeholder="$t('engagement.terms_placeholder')" :error="form.errors.engagement_terms" />
                <p v-if="form.errors.match" class="text-sm text-rose-600">{{ form.errors.match }}</p>
            </div>
            <template #footer>
                <Button variant="ghost" no-icon @click="accepting = null">{{ $t('common.cancel') }}</Button>
                <Button icon="check" :loading="form.processing && form.accept" :disabled="!form.engagement_model" @click="respond(accepting, true)">{{ $t('engagement.confirm') }}</Button>
            </template>
        </Modal>
        <Modal :show="!!declining" :title="$t('invitations.decline')" @close="declining = null">
            <Field v-model="form.reason" as="textarea" :rows="3" :label="$t('match.reject_reason')" />
            <template #footer><Button variant="danger" icon="x" :loading="form.processing" @click="respond(declining, false)">{{ $t('invitations.decline') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
