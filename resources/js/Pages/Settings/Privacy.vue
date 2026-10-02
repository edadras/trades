<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Icon from '@/Components/ui/Icon.vue';
import SettingsNav from './SettingsNav.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ consents: Object, history: Array, dataRequests: { type: Array, default: () => [] } });
const { dateTime } = useI18n();
const toggle = (type, granted) => router.post(route('settings.privacy.update'), { type, granted }, { preserveScroll: true });

const requestTone = { pending: 'amber', processing: 'amber', completed: 'green', rejected: 'red', failed: 'red' };
const hasPending = (type) => props.dataRequests.some((d) => d.type === type && ['pending', 'processing'].includes(d.status));
const exportPending = computed(() => hasPending('export'));
const deletePending = computed(() => hasPending('delete'));

const exportForm = useForm({ type: 'export' });
const requestExport = () => exportForm.post(route('settings.data.request'), { preserveScroll: true });

const showDelete = ref(false);
const deleteForm = useForm({ type: 'delete', reason: '', password: '' });
const requestDelete = () =>
    deleteForm.post(route('settings.data.request'), {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
            deleteForm.reset();
        },
        onFinish: () => deleteForm.reset('password'),
    });
</script>

<template>
    <AppLayout :title="$t('nav.settings')">
        <SettingsNav current="privacy" />
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card :title="$t('settings.consents')" :subtitle="$t('settings.consents_hint')">
                <ul class="divide-y divide-[var(--border)]">
                    <li v-for="type in ['terms', 'privacy', 'data_processing', 'ai_processing', 'marketing', 'nda']" :key="type" class="flex items-center gap-3 py-3">
                        <div class="flex-1"><p class="text-sm font-medium">{{ $t(`consent.${type}`) }}</p><p v-if="consents[type]" class="text-xs text-gray-500">v{{ consents[type].version }} · {{ dateTime(consents[type].at) }}</p></div>
                        <Badge :tone="consents[type]?.granted ? 'green' : 'gray'">{{ consents[type]?.granted ? $t('settings.granted') : $t('settings.not_granted') }}</Badge>
                        <button v-if="['marketing', 'ai_processing'].includes(type)" type="button" class="text-sm text-navy-700 hover:underline" @click="toggle(type, !consents[type]?.granted)">{{ consents[type]?.granted ? $t('settings.withdraw') : $t('settings.grant') }}</button>
                    </li>
                </ul>
            </Card>
            <Card :title="$t('settings.consent_history')">
                <ul class="space-y-2 text-sm"><li v-for="(h, i) in history" :key="i" class="flex justify-between gap-3"><span>{{ $t(`consent.${h.type}`) }} — {{ h.granted ? '✓' : '✕' }}</span><span class="text-xs text-gray-500">{{ dateTime(h.created_at) }}</span></li></ul>
            </Card>
        </div>

        <section class="mt-8">
            <h2 class="text-lg font-semibold text-ink">{{ $t('privacy_data.title') }}</h2>
            <p class="mt-1 text-sm text-gray-500">{{ $t('privacy_data.subtitle') }}</p>
            <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <Card :title="$t('privacy_data.export_title')">
                    <p class="text-sm leading-6 text-gray-600">{{ $t('privacy_data.export_text') }}</p>
                    <Button class="mt-4" icon="download" :disabled="exportPending" :loading="exportForm.processing" @click="requestExport">{{ $t('privacy_data.export_button') }}</Button>
                    <p v-if="exportPending" class="mt-2 text-xs text-amber-700">{{ $t('privacy_data.pending_note') }}</p>
                </Card>
                <Card :title="$t('privacy_data.delete_title')">
                    <p class="text-sm leading-6 text-gray-600">{{ $t('privacy_data.delete_text') }}</p>
                    <Button class="mt-4" variant="danger" icon="trash" :disabled="deletePending" @click="showDelete = true">{{ $t('privacy_data.delete_button') }}</Button>
                    <p v-if="deletePending" class="mt-2 text-xs text-amber-700">{{ $t('privacy_data.pending_note') }}</p>
                </Card>
                <Card class="lg:col-span-2" :title="$t('privacy_data.history')">
                    <ul v-if="dataRequests.length" class="divide-y divide-[var(--border)]">
                        <li v-for="d in dataRequests" :key="d.id" class="flex flex-wrap items-center gap-3 py-3">
                            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-navy-50 text-navy-700"><Icon :name="d.type === 'export' ? 'download' : 'trash'" :size="16" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-ink">{{ $t(`privacy_data.types.${d.type}`) }}</p>
                                <p class="text-xs text-gray-500">{{ dateTime(d.created_at) }}</p>
                                <p v-if="d.resolution" class="mt-1 text-xs text-gray-600">{{ d.resolution }}</p>
                            </div>
                            <Badge :tone="requestTone[d.status] ?? 'gray'" dot>{{ $t(`privacy_data.statuses.${d.status}`) }}</Badge>
                            <Button v-if="d.download_url" :href="d.download_url" external size="sm" variant="light" icon="download">{{ $t('privacy_data.download') }}</Button>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('privacy_data.no_history') }}</p>
                    <p v-if="dataRequests.some((d) => d.download_url)" class="mt-3 text-xs text-gray-500">{{ $t('privacy_data.download_hint') }}</p>
                </Card>
            </div>
        </section>

        <Modal :show="showDelete" :title="$t('privacy_data.delete_modal_title')" @close="showDelete = false">
            <form id="delete-account-request" class="space-y-4" @submit.prevent="requestDelete">
                <div class="flex items-start gap-3 rounded-2xl bg-rose-50 p-4 text-sm leading-6 text-rose-800 ring-1 ring-rose-200">
                    <Icon name="alert" class="mt-0.5 shrink-0" />
                    <p>{{ $t('privacy_data.delete_warning') }}</p>
                </div>
                <Field v-model="deleteForm.reason" as="textarea" :rows="3" :label="$t('privacy_data.reason')" :error="deleteForm.errors.reason" />
                <Field v-model="deleteForm.password" type="password" dir="ltr" autocomplete="current-password" :label="$t('privacy_data.password')" required :error="deleteForm.errors.password" />
            </form>
            <template #footer>
                <Button variant="light" no-icon @click="showDelete = false">{{ $t('common.cancel') }}</Button>
                <Button type="submit" form="delete-account-request" variant="danger" icon="trash" :loading="deleteForm.processing" :disabled="!deleteForm.password">{{ $t('privacy_data.confirm_delete') }}</Button>
            </template>
        </Modal>
    </AppLayout>
</template>
