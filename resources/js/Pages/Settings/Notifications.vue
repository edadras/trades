<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import SettingsNav from './SettingsNav.vue';
import { route } from '@/i18n';
const props = defineProps({ events: Array, preferences: Object, hasPhone: Boolean });
/** Mirrors PlatformNotification::MAIL_BY_DEFAULT. */
const mailDefault = ['expert_suggested', 'expert_invited', 'expert_accepted', 'document_requested', 'deadline_approaching', 'appointment_reminder', 'case_resolved', 'review_required', 'expert_verified', 'outcome_confirmation_requested', 'outcome_disputed', 'legal_review_required', 'complaint_updated', 'data_request_ready', 'data_request_decided', 'pilot_report_ready'];
const form = useForm({ preferences: Object.fromEntries(props.events.map((e) => [e, { mail: props.preferences[e]?.mail ?? mailDefault.includes(e), sms: !!props.preferences[e]?.sms, whatsapp: !!props.preferences[e]?.whatsapp }])) });
</script>

<template>
    <AppLayout :title="$t('nav.settings')">
        <SettingsNav current="notifications" />
        <Card :title="$t('settings.notifications')" :subtitle="$t('settings.notifications_hint')">
            <!-- Phones: one card per event with large toggles -->
            <ul class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:hidden">
                <li v-for="e in events" :key="e" class="rounded-2xl p-4 ring-1 ring-[var(--border)]">
                    <p class="text-sm font-medium text-ink">{{ $t(`notif_event.${e}`) }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="chip bg-navy-50 text-navy-800"><Icon name="check" :size="12" />{{ $t('settings.in_app') }}</span>
                        <label v-for="ch in ['mail', 'sms', 'whatsapp']" :key="ch" class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-full px-3 text-sm ring-1 transition" :class="form.preferences[e][ch] ? 'bg-navy-950 text-white ring-navy-950' : 'ring-[var(--border)]'" :aria-disabled="ch !== 'mail' && !hasPhone">
                            <input v-model="form.preferences[e][ch]" type="checkbox" class="size-4 accent-white" :disabled="ch !== 'mail' && !hasPhone" />
                            {{ ch === 'mail' ? $t('settings.email') : ch === 'sms' ? 'SMS' : 'WhatsApp' }}
                        </label>
                    </div>
                </li>
            </ul>
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs text-gray-500"><th class="py-2 text-start font-medium">{{ $t('settings.event') }}</th><th class="px-3">{{ $t('settings.in_app') }}</th><th class="px-3">{{ $t('settings.email') }}</th><th class="px-3">SMS</th><th class="px-3">WhatsApp</th></tr></thead>
                    <tbody>
                        <tr v-for="e in events" :key="e" class="border-t border-[var(--border)]">
                            <td class="py-3">{{ $t(`notif_event.${e}`) }}</td>
                            <td class="px-3 text-center"><input type="checkbox" checked disabled class="size-5 accent-navy-900" /></td>
                            <td class="px-3 text-center"><input v-model="form.preferences[e].mail" type="checkbox" class="size-5 accent-navy-900" /></td>
                            <td class="px-3 text-center"><input v-model="form.preferences[e].sms" type="checkbox" class="size-5 accent-navy-900" :disabled="!hasPhone" /></td>
                            <td class="px-3 text-center"><input v-model="form.preferences[e].whatsapp" type="checkbox" class="size-5 accent-navy-900" :disabled="!hasPhone" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!hasPhone" class="mt-3 text-xs text-gray-500">{{ $t('settings.phone_needed') }}</p>
            <Button class="mt-5" icon="check" :loading="form.processing" @click="form.put(route('settings.notifications.update'), { preserveScroll: true })">{{ $t('common.save') }}</Button>
        </Card>
    </AppLayout>
</template>
