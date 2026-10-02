<script setup>
// Active case team with engagement terms, plus leaving (expert), removal (staff) and replacement requests (business).
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Modal from '@/Components/ui/Modal.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object });
const emit = defineEmits(['open-supporters']);
const { date, dateTime } = useI18n();

const experts = computed(() => props.item.experts ?? []);
const pastExperts = computed(() => props.item.past_experts ?? []);
const isBusiness = computed(() => props.item.context === 'business');
const canAddSecond = computed(() => experts.value.length > 0 && (props.item.can.decide_matches || props.item.can.assign));

const leaveForm = useForm({ reason: '' });
const leaving = ref(false);
const leave = () => leaveForm.post(route('cases.leave', { case: props.item.number }), { onSuccess: () => { leaving.value = false; leaveForm.reset(); } });

/** The server decides the mode: a business member requests a replacement, staff remove the expert. */
const releaseForm = useForm({ reason: '' });
const releasing = ref(null);
const release = () => releaseForm.post(route('cases.experts.release', { case: props.item.number, expert: releasing.value.id }), {
    preserveScroll: true,
    onSuccess: () => { releasing.value = null; releaseForm.reset(); },
});
const releaseTitle = computed(() => (isBusiness.value ? 'team_case.replace_title' : 'team_case.remove_title'));
</script>

<template>
    <Card :title="$t('team_case.title')">
        <template v-if="canAddSecond" #actions>
            <Button size="sm" variant="light" icon="plus" @click="emit('open-supporters')">{{ $t('team_case.add_second_short') }}</Button>
        </template>

        <ul v-if="experts.length" class="grid gap-3 md:grid-cols-2">
            <li v-for="e in experts" :key="e.id" class="flex min-w-0 flex-col rounded-3xl p-4 ring-1 ring-[var(--border)]">
                <div class="flex items-start gap-3">
                    <Avatar :name="e.name" :src="e.avatar" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <p class="font-semibold text-ink">{{ e.name }}</p>
                            <Badge v-if="e.is_me" tone="dark">{{ $t('team_case.you') }}</Badge>
                        </div>
                        <p v-if="e.headline" class="mt-0.5 text-sm text-gray-500">{{ e.headline }}</p>
                    </div>
                    <Badge :tone="e.role === 'lead' ? 'green' : 'sky'" class="shrink-0">{{ $t(`match.role_${e.role}`) }}</Badge>
                </div>
                <dl class="mt-4 grid grid-cols-[minmax(0,auto)_minmax(0,1fr)] gap-x-3 gap-y-1.5 text-sm">
                    <dt class="text-gray-500">{{ $t('team_case.engagement') }}</dt>
                    <dd class="text-end">
                        <Badge v-if="e.engagement_model" :tone="e.engagement_model === 'commercial' ? 'amber' : 'navy'">{{ $t(`engagement.models.${e.engagement_model}`) }}</Badge>
                        <span v-else>—</span>
                    </dd>
                    <template v-if="e.engagement_terms">
                        <dt class="text-gray-500">{{ $t('team_case.terms') }}</dt>
                        <dd class="whitespace-pre-line break-words text-end text-gray-700">{{ e.engagement_terms }}</dd>
                    </template>
                    <dt class="text-gray-500">{{ $t('team_case.joined') }}</dt>
                    <dd class="text-end">{{ date(e.joined_at) }}</dd>
                </dl>
                <div v-if="(e.is_me && item.can.leave) || (!e.is_me && item.can.release_experts)" class="mt-auto pt-4"><div class="flex flex-wrap gap-2 border-t border-[var(--border)] pt-3">
                    <Button v-if="e.is_me && item.can.leave" size="sm" variant="light" icon="logout" @click="leaving = true">{{ $t('team_case.leave') }}</Button>
                    <Button v-else-if="!e.is_me && item.can.release_experts" size="sm" variant="light" :icon="isBusiness ? 'refresh' : 'x'" @click="releasing = e">{{ isBusiness ? $t('team_case.replace') : $t('team_case.remove') }}</Button>
                </div></div>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500">{{ $t('team_case.none') }}</p>

        <p v-if="canAddSecond" class="mt-4 flex items-start gap-2 rounded-2xl bg-navy-50 p-3 text-xs leading-6 text-navy-800"><Icon name="users" :size="14" class="mt-1 shrink-0" />{{ $t('team_case.add_second') }}</p>

        <div v-if="pastExperts.length" class="mt-6 border-t border-[var(--border)] pt-5">
            <p class="mb-3 text-sm font-medium text-ink">{{ $t('team_case.past') }}</p>
            <ul class="space-y-2">
                <li v-for="(p, i) in pastExperts" :key="i" class="rounded-2xl bg-[var(--surface-muted)] p-3 text-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-medium text-ink">{{ p.name }}</span>
                        <Badge tone="gray">{{ $t(`team_case.status.${p.status}`) }}</Badge>
                        <span class="ms-auto text-xs text-gray-400">{{ dateTime(p.left_at) }}</span>
                    </div>
                    <p v-if="p.reason" class="mt-1 whitespace-pre-line text-gray-600">{{ p.reason }}</p>
                </li>
            </ul>
        </div>

        <Modal :show="leaving" :title="$t('team_case.leave_title')" @close="leaving = false">
            <Field v-model="leaveForm.reason" as="textarea" :rows="4" required :label="$t('team_case.leave_reason')" :hint="$t('team_case.leave_hint')" :error="leaveForm.errors.reason" />
            <template #footer>
                <Button variant="ghost" no-icon @click="leaving = false">{{ $t('common.cancel') }}</Button>
                <Button variant="danger" icon="logout" :loading="leaveForm.processing" :disabled="leaveForm.reason.trim().length < 5" @click="leave">{{ $t('team_case.leave') }}</Button>
            </template>
        </Modal>
        <Modal :show="!!releasing" :title="$t(releaseTitle)" @close="releasing = null">
            <p v-if="releasing" class="mb-4 text-sm font-medium text-ink">{{ releasing.name }}</p>
            <Field v-model="releaseForm.reason" as="textarea" :rows="4" required :label="$t('team_case.release_reason')" :hint="$t('team_case.release_hint')" :error="releaseForm.errors.reason || releaseForm.errors.expert" />
            <template #footer>
                <Button variant="ghost" no-icon @click="releasing = null">{{ $t('common.cancel') }}</Button>
                <Button variant="danger" :icon="isBusiness ? 'refresh' : 'x'" :loading="releaseForm.processing" :disabled="releaseForm.reason.trim().length < 5" @click="release">{{ isBusiness ? $t('team_case.replace') : $t('team_case.remove') }}</Button>
            </template>
        </Modal>
    </Card>
</template>
