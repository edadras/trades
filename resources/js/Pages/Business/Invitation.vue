<script setup>
// Landing page of a team invitation link: reachable by guests (sign in / register) and signed-in users (accept).
import { computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ token: String, invitation: Object, emailMatches: { type: Boolean, default: null } });
const page = usePage();
const { t } = useI18n();
const isGuest = computed(() => !page.props.auth?.user);
const acceptForm = useForm({});
const accept = () => acceptForm.post(route('invitations.accept', { token: props.token }));
const logout = () => router.post(route('logout'));
const homeHref = computed(() => route(page.props.auth?.user?.home ?? 'dashboard'));
</script>

<template>
    <AuthLayout :title="$t('invite.title')" :subtitle="invitation.valid ? $t('invite.subtitle') : undefined">
        <SeoHead :title="t('invite.title')" />

        <div v-if="!invitation.valid" class="rounded-[var(--radius-card)] bg-rose-50 p-5 ring-1 ring-rose-200">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white text-rose-600"><Icon name="alert" /></span>
                <div>
                    <p class="font-semibold text-rose-800">{{ $t('invite.invalid_title') }}</p>
                    <p class="mt-1 text-sm leading-6 text-rose-700">{{ $t('invite.invalid_text') }}</p>
                </div>
            </div>
            <Button v-if="!isGuest" class="mt-5" variant="light" icon="home" :href="homeHref">{{ $t('invite.go_home') }}</Button>
        </div>

        <div v-else class="space-y-6">
            <dl class="divide-y divide-[var(--border)] rounded-[var(--radius-card)] bg-white ring-1 ring-[var(--border)]">
                <div class="flex items-center gap-3 p-4">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-navy-950 text-white"><Icon name="briefcase" /></span>
                    <div class="min-w-0">
                        <dt class="text-xs text-gray-500">{{ $t('invite.business') }}</dt>
                        <dd class="truncate font-semibold text-ink">{{ invitation.business }}</dd>
                    </div>
                </div>
                <div v-if="invitation.inviter" class="flex items-center justify-between gap-3 p-4 text-sm">
                    <dt class="text-gray-500">{{ $t('invite.inviter') }}</dt>
                    <dd class="font-medium text-ink">{{ invitation.inviter }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3 p-4 text-sm">
                    <dt class="text-gray-500">{{ $t('invite.role') }}</dt>
                    <dd><Badge :tone="invitation.role === 'admin' ? 'navy' : 'gray'">{{ $t(`team.roles.${invitation.role}`) }}</Badge></dd>
                </div>
                <div class="flex items-center justify-between gap-3 p-4 text-sm">
                    <dt class="text-gray-500">{{ $t('invite.email') }}</dt>
                    <dd class="truncate font-medium text-ink" dir="ltr">{{ invitation.email }}</dd>
                </div>
            </dl>

            <template v-if="isGuest">
                <p class="text-sm leading-6 text-gray-600">{{ $t('invite.guest_hint') }}</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <Button :href="route('register', { invitation: token, email: invitation.email })" block size="lg" icon="plus">{{ $t('invite.register') }}</Button>
                    <Button :href="route('login')" variant="light" block size="lg" icon="key">{{ $t('invite.login') }}</Button>
                </div>
            </template>

            <template v-else-if="emailMatches">
                <Button block size="lg" icon="check" :loading="acceptForm.processing" @click="accept">{{ $t('invite.accept') }}</Button>
                <p v-if="acceptForm.errors.token" class="text-sm text-rose-600">{{ acceptForm.errors.token }}</p>
            </template>

            <div v-else class="rounded-[var(--radius-card)] bg-amber-50 p-5 ring-1 ring-amber-200">
                <div class="flex items-start gap-3">
                    <Icon name="alert" class="mt-0.5 shrink-0 text-amber-700" />
                    <div>
                        <p class="font-semibold text-amber-900">{{ $t('invite.mismatch_title') }}</p>
                        <p class="mt-1 text-sm leading-6 text-amber-800">{{ $t('invite.mismatch_text', { email: invitation.email }) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <Button variant="light" size="sm" icon="logout" @click="logout">{{ $t('nav.logout') }}</Button>
                    <Button variant="ghost" size="sm" icon="home" :href="homeHref">{{ $t('invite.go_home') }}</Button>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
