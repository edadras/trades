<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Modal from '@/Components/ui/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route } from '@/i18n';

const props = defineProps({ item: Object });
const rejecting = ref(null);
const form = useForm({ accept: true, reason: '' });
const decide = (match, accept) => {
    form.accept = accept;
    form.post(route('cases.matches.decide', { case: props.item.number, match: match.id }), { preserveScroll: true, onSuccess: () => { rejecting.value = null; form.reset(); } });
};
const tone = { proposed: 'navy', invited: 'amber', active: 'green', rejected_by_business: 'gray', expert_declined: 'red', withdrawn: 'gray' };
</script>

<template>
    <div class="space-y-6">
        <section v-if="item.experts.length">
            <h3 class="mb-3 font-semibold text-ink">{{ $t('match.team') }}</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <ExpertCard v-for="e in item.experts" :key="e.id" :expert="e"><Badge tone="green" class="mt-4 w-fit">{{ $t(`match.role_${e.role}`) }}</Badge></ExpertCard>
            </div>
        </section>
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h3 class="font-semibold text-ink">{{ $t('match.proposals') }}</h3>
                <span v-if="item.can.decide_matches" class="text-xs text-gray-500">{{ $t('match.choose_hint') }}</span>
            </div>
            <div v-if="item.matches.length" class="grid gap-4 md:grid-cols-2">
                <ExpertCard v-for="m in item.matches" :key="m.id" :expert="m.expert" :score="m.score" :reasons="m.reasons">
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-[var(--border)] pt-4">
                        <Badge :tone="tone[m.status]">{{ $t(`match_status.${m.status}`) }}</Badge>
                        <Badge v-if="m.source === 'staff'" tone="sky"><Icon name="shield-check" :size="12" />{{ $t('match.by_staff') }}</Badge>
                        <template v-if="item.can.decide_matches && m.status === 'proposed'">
                            <Button size="sm" icon="check" class="ms-auto" :loading="form.processing && form.accept" @click="decide(m, true)">{{ $t('match.accept') }}</Button>
                            <Button size="sm" variant="ghost" no-icon @click="rejecting = m">{{ $t('match.reject') }}</Button>
                        </template>
                    </div>
                </ExpertCard>
            </div>
            <EmptyState v-else icon="network" :title="$t('match.none')" :text="$t('match.none_hint')" />
        </section>
        <Modal :show="!!rejecting" :title="$t('match.reject_title')" @close="rejecting = null">
            <Field v-model="form.reason" as="textarea" :rows="3" :label="$t('match.reject_reason')" />
            <template #footer><Button variant="ghost" no-icon @click="rejecting = null">{{ $t('common.cancel') }}</Button><Button variant="danger" icon="x" :loading="form.processing" @click="decide(rejecting, false)">{{ $t('match.reject') }}</Button></template>
        </Modal>
    </div>
</template>
