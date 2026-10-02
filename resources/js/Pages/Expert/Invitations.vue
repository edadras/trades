<script setup>
import { ref } from 'vue';
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
const form = useForm({ accept: true, reason: '' });
const declining = ref(null);
const respond = (inv, accept) => { form.accept = accept; form.post(route('expert.invitations.respond', { match: inv.id }), { preserveScroll: true, onSuccess: () => (declining.value = null) }); };
const tone = { invited: 'amber', active: 'green', expert_declined: 'gray' };
</script>

<template>
    <AppLayout :title="$t('nav.invitations')" :subtitle="$t('invitations.subtitle')">
        <div v-if="invitations.length" class="grid gap-4 lg:grid-cols-2">
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
                <div v-if="inv.status === 'invited'" class="mt-4 flex gap-2 border-t border-[var(--border)] pt-4">
                    <Button icon="check" :loading="form.processing && form.accept" @click="respond(inv, true)">{{ $t('invitations.accept') }}</Button>
                    <Button variant="ghost" no-icon @click="declining = inv">{{ $t('invitations.decline') }}</Button>
                    <span class="ms-auto self-center text-xs text-gray-400">{{ relative(inv.invited_at) }}</span>
                </div>
                <Button v-else-if="inv.status === 'active'" :href="route('expert.cases.show', { case: inv.case.number })" class="mt-4" icon="arrow">{{ $t('invitations.open_case') }}</Button>
            </Card>
        </div>
        <EmptyState v-else icon="inbox" :title="$t('invitations.empty')" :text="$t('invitations.empty_hint')" />
        <Modal :show="!!declining" :title="$t('invitations.decline')" @close="declining = null">
            <Field v-model="form.reason" as="textarea" :rows="3" :label="$t('match.reject_reason')" />
            <template #footer><Button variant="danger" icon="x" :loading="form.processing" @click="respond(declining, false)">{{ $t('invitations.decline') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
