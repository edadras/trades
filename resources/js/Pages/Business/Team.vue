<script setup>
// Business team: members with roles, invitations by email, role changes and removal (owner/admin only).
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Modal from '@/Components/ui/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route, useI18n } from '@/i18n';

defineProps({ members: Array, invitations: Array, canManage: Boolean });
const page = usePage();
const { t, date } = useI18n();
const currentUserId = computed(() => page.props.auth?.user?.id);

const roleTone = { owner: 'dark', admin: 'navy', member: 'gray' };
const roleOptions = computed(() => ['admin', 'member'].map((r) => ({ value: r, label: t(`team.roles.${r}`) })));

const inviteForm = useForm({ email: '', role: 'member' });
const sendInvite = () => inviteForm.post(route('business.team.invite'), { preserveScroll: true, onSuccess: () => inviteForm.reset('email') });

const changeRole = (member, role) => router.put(route('business.team.update', { member: member.id }), { role }, { preserveScroll: true });

const removing = ref(null);
const removeProcessing = ref(false);
const confirmRemove = () => {
    if (!removing.value) {
        return;
    }
    router.delete(route('business.team.destroy', { member: removing.value.id }), {
        preserveScroll: true,
        onStart: () => (removeProcessing.value = true),
        onFinish: () => {
            removeProcessing.value = false;
            removing.value = null;
        },
    });
};

const revoke = (invitation) => router.delete(route('business.team.revoke', { invitation: invitation.id }), { preserveScroll: true });
</script>

<template>
    <AppLayout :title="$t('team.title')" :subtitle="$t('team.subtitle')">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <Card :title="$t('team.members')">
                    <ul class="divide-y divide-[var(--border)]">
                        <li v-for="m in members" :key="m.id" class="flex flex-wrap items-center gap-3 py-3">
                            <Avatar :name="m.name" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">
                                    {{ m.name }}
                                    <span v-if="m.id === currentUserId" class="text-xs font-normal text-gray-500">({{ $t('team.you') }})</span>
                                </p>
                                <p class="truncate text-xs text-gray-500" dir="ltr">{{ m.email }}</p>
                            </div>
                            <Badge :tone="roleTone[m.role] ?? 'gray'">{{ $t(`team.roles.${m.role}`) }}</Badge>
                            <div v-if="canManage && m.role !== 'owner' && m.id !== currentUserId" class="flex w-full items-center gap-2 sm:w-auto">
                                <select :value="m.role" class="input h-9 flex-1 py-1 text-sm sm:w-32" :aria-label="$t('team.change_role')" @change="changeRole(m, $event.target.value)">
                                    <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                                <Button size="sm" variant="ghost" icon="trash" icon-only :aria-label="$t('team.remove')" :title="$t('team.remove')" @click="removing = m" />
                            </div>
                        </li>
                    </ul>
                </Card>

                <Card v-if="canManage" :title="$t('team.pending_title')">
                    <ul v-if="invitations.length" class="divide-y divide-[var(--border)]">
                        <li v-for="inv in invitations" :key="inv.id" class="flex flex-wrap items-center gap-3 py-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-navy-50 text-navy-700"><Icon name="mail" :size="18" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink" dir="ltr">{{ inv.email }}</p>
                                <p class="text-xs text-gray-500">{{ $t(`team.roles.${inv.role}`) }}</p>
                            </div>
                            <Badge v-if="inv.expired" tone="red">{{ $t('team.expired') }}</Badge>
                            <Badge v-else tone="amber" dot>{{ $t('team.expires', { date: date(inv.expires_at) }) }}</Badge>
                            <Button size="sm" variant="light" icon="x" @click="revoke(inv)">{{ $t('team.revoke') }}</Button>
                        </li>
                    </ul>
                    <EmptyState v-else icon="mail" :title="$t('team.no_pending')" />
                </Card>
            </div>

            <div class="space-y-6">
                <Card v-if="canManage" :title="$t('team.invite_title')" :subtitle="$t('team.invite_hint')">
                    <form class="space-y-4" @submit.prevent="sendInvite">
                        <Field v-model="inviteForm.email" type="email" dir="ltr" autocomplete="off" :label="$t('fields.email')" required :error="inviteForm.errors.email" />
                        <Field v-model="inviteForm.role" as="select" :options="roleOptions" :label="$t('team.role')" required :error="inviteForm.errors.role" />
                        <Button type="submit" block icon="send" :loading="inviteForm.processing">{{ $t('team.invite_send') }}</Button>
                    </form>
                </Card>
                <p v-else class="rounded-[var(--radius-card)] bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">{{ $t('team.no_manage') }}</p>

                <Card :title="$t('team.roles_title')">
                    <ul class="space-y-3">
                        <li v-for="r in ['owner', 'admin', 'member']" :key="r" class="flex items-start gap-3">
                            <Badge :tone="roleTone[r]" class="mt-0.5 shrink-0">{{ $t(`team.roles.${r}`) }}</Badge>
                            <p class="text-sm leading-6 text-gray-600">{{ $t(`team.roles_hint.${r}`) }}</p>
                        </li>
                    </ul>
                </Card>
            </div>
        </div>

        <Modal :show="!!removing" :title="$t('team.remove_title')" @close="removing = null">
            <p class="text-sm leading-6 text-gray-600">{{ $t('team.remove_confirm', { name: removing?.name }) }}</p>
            <template #footer>
                <Button variant="light" no-icon @click="removing = null">{{ $t('common.cancel') }}</Button>
                <Button variant="danger" icon="trash" :loading="removeProcessing" @click="confirmRemove">{{ $t('team.remove') }}</Button>
            </template>
        </Modal>
    </AppLayout>
</template>
