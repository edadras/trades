<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Badge from '@/Components/ui/Badge.vue';
import SettingsNav from './SettingsNav.vue';
import { route, useI18n } from '@/i18n';
defineProps({ consents: Object, history: Array });
const { dateTime } = useI18n();
const toggle = (type, granted) => router.post(route('settings.privacy.update'), { type, granted }, { preserveScroll: true });
</script>

<template>
    <AppLayout :title="$t('nav.settings')">
        <SettingsNav current="privacy" />
        <div class="grid gap-6 lg:grid-cols-2">
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
    </AppLayout>
</template>
