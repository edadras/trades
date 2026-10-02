<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route, useI18n } from '@/i18n';
defineProps({ notifications: Object });
const { t, relative } = useI18n();
const icons = { new_message: 'chat', expert_suggested: 'users', expert_invited: 'inbox', expert_accepted: 'check', expert_declined: 'x', document_requested: 'file', deadline_approaching: 'clock', appointment_scheduled: 'calendar', appointment_reminder: 'calendar', case_resolved: 'flag', review_required: 'shield-check', task_assigned: 'task', expert_verified: 'shield-check', case_updated: 'refresh' };
const text = (n, part) => t(`notif.${n.event}.${part}`, Object.fromEntries(Object.entries(n.params ?? {}).map(([k, v]) => [k, k === 'status' ? t(`status.${v}`) : v])));
</script>

<template>
    <AppLayout :title="$t('nav.notifications')">
        <template #header-actions><span class="hidden sm:block"><Button size="sm" variant="light" icon="check" @click="router.post(route('notifications.read-all'), {}, { preserveScroll: true })">{{ $t('notif.read_all') }}</Button></span></template>
        <Card v-if="notifications.data.length" :padded="false">
            <ul class="divide-y divide-[var(--border)]">
                <li v-for="n in notifications.data" :key="n.id">
                    <Link :href="route('notifications.read', { id: n.id })" class="flex items-start gap-3 px-5 py-4 hover:bg-navy-50/50" :class="n.read ? '' : 'bg-navy-50/40'">
                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl" :class="n.read ? 'bg-gray-100 text-gray-500' : 'bg-navy-950 text-white'"><Icon :name="icons[n.event] ?? 'bell'" :size="18" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm" :class="n.read ? 'text-gray-700' : 'font-semibold text-ink'">{{ text(n, 'title') }}</p>
                            <p class="text-sm text-gray-500">{{ text(n, 'body') }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-gray-400">{{ relative(n.created_at) }}</span>
                    </Link>
                </li>
            </ul>
        </Card>
        <EmptyState v-else icon="bell" :title="$t('notif.empty')" />
        <Pagination :meta="notifications" />
    </AppLayout>
</template>
