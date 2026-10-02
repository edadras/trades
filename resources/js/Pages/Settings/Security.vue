<script setup>
// Two-factor authentication (Fortify) and active session/device management.
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import SettingsNav from './SettingsNav.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ twoFactor: Object, sessions: Array });
const { relative } = useI18n();
const qr = ref(null);
const codes = ref([]);
const confirm = useForm({ code: '' });
const xhr = async (url) => (await fetch(url, { headers: { Accept: 'application/json' } })).json();

function enable() {
    router.post(route('two-factor.enable'), {}, { preserveScroll: true, onSuccess: async () => { qr.value = (await xhr(route('two-factor.qr-code'))).svg; } });
}
const confirmCode = () => confirm.post(route('two-factor.confirm'), { preserveScroll: true, errorBag: 'confirmTwoFactorAuthentication', onSuccess: async () => { qr.value = null; codes.value = await xhr(route('two-factor.recovery-codes')); } });
const disable = () => router.delete(route('two-factor.disable'), { preserveScroll: true });
const showCodes = async () => (codes.value = await xhr(route('two-factor.recovery-codes')));
const logoutOthers = useForm({ password: '' });
</script>

<template>
    <AppLayout :title="$t('nav.settings')">
        <SettingsNav current="security" />
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card :title="$t('settings.two_factor')" :subtitle="$t('settings.two_factor_hint')">
                <p v-if="twoFactor.required && !twoFactor.confirmed" class="mb-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900">{{ $t('settings.two_factor_required') }}</p>
                <Badge :tone="twoFactor.confirmed ? 'green' : 'gray'"><Icon :name="twoFactor.confirmed ? 'shield-check' : 'shield'" :size="12" />{{ twoFactor.confirmed ? $t('settings.enabled') : $t('settings.disabled') }}</Badge>
                <div v-if="qr || (twoFactor.enabled && !twoFactor.confirmed)" class="mt-4 space-y-4">
                    <p class="text-sm text-gray-600">{{ $t('settings.scan_qr') }}</p>
                    <div v-if="qr" class="w-fit rounded-2xl bg-white p-3 ring-1 ring-[var(--border)]" v-html="qr" />
                    <Button v-else size="sm" variant="light" icon="refresh" @click="xhr(route('two-factor.qr-code')).then((r) => (qr = r.svg))">{{ $t('settings.show_qr') }}</Button>
                    <form class="flex items-end gap-2" @submit.prevent="confirmCode">
                        <Field v-model="confirm.code" class="flex-1" :label="$t('auth.code')" dir="ltr" inputmode="numeric" :error="confirm.errors.code" />
                        <Button type="submit" icon="check" :loading="confirm.processing">{{ $t('common.confirm') }}</Button>
                    </form>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <Button v-if="!twoFactor.enabled" icon="lock" @click="enable">{{ $t('settings.enable_2fa') }}</Button>
                    <template v-if="twoFactor.confirmed">
                        <Button variant="light" size="sm" icon="key" @click="showCodes">{{ $t('settings.recovery_codes') }}</Button>
                        <Button variant="danger" size="sm" icon="x" @click="disable">{{ $t('settings.disable_2fa') }}</Button>
                    </template>
                </div>
                <ul v-if="codes.length" class="mt-4 grid grid-cols-2 gap-2 rounded-2xl bg-[var(--surface-muted)] p-4 font-mono text-sm" dir="ltr"><li v-for="c in codes" :key="c">{{ c }}</li></ul>
            </Card>
            <Card :title="$t('settings.sessions')" :subtitle="$t('settings.sessions_hint')">
                <ul class="space-y-3">
                    <li v-for="s in sessions" :key="s.id" class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-2xl bg-navy-50 text-navy-700"><Icon name="globe" :size="18" /></span>
                        <div class="flex-1 text-sm"><p class="font-medium">{{ s.agent || '—' }} <Badge v-if="s.current" tone="green">{{ $t('settings.this_device') }}</Badge></p><p class="text-xs text-gray-500" dir="ltr">{{ s.ip }} · {{ relative(s.last_active) }}</p></div>
                    </li>
                </ul>
                <form class="mt-5 flex items-end gap-2" @submit.prevent="logoutOthers.delete(route('settings.sessions.destroy'), { preserveScroll: true, onSuccess: () => logoutOthers.reset() })">
                    <Field v-model="logoutOthers.password" class="flex-1" type="password" dir="ltr" :label="$t('fields.password')" :error="logoutOthers.errors.password" />
                    <Button type="submit" variant="light" icon="logout" :loading="logoutOthers.processing">{{ $t('settings.logout_others') }}</Button>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
