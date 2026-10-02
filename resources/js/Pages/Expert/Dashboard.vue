<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import { route, useI18n } from '@/i18n';
defineProps({ profile: Object, stats: Object, cases: Array, tasks: Array, appointments: Array });
const { relative, dateTime } = useI18n();
</script>

<template>
    <AppLayout :title="$t('expert_dash.title')">
        <section class="hero-gradient rounded-[32px] p-6 text-white sm:p-8">
            <p class="text-sm text-white/75">{{ profile.headline }}</p>
            <h2 class="mt-1 text-2xl font-semibold">{{ $t('expert_dash.greeting') }}</h2>
            <div class="mt-5 flex flex-wrap gap-3">
                <Button :href="route('expert.invitations.index')" variant="light" icon="inbox">{{ $t('nav.invitations') }} ({{ stats.invitations }})</Button>
                <Button :href="route('expert.profile.edit')" variant="glass" icon="user">{{ $t('nav.expert_profile') }}</Button>
            </div>
        </section>
        <section class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <StatCard :label="$t('expert_dash.invitations')" :value="stats.invitations" icon="inbox" tone="amber" />
            <StatCard :label="$t('expert_dash.active_cases')" :value="stats.active_cases" icon="folder" />
            <StatCard :label="$t('expert_dash.pending_tasks')" :value="stats.pending_tasks" icon="task" tone="light" />
            <StatCard :label="$t('expert_dash.upcoming_meetings')" :value="stats.upcoming_meetings" icon="calendar" tone="light" />
            <StatCard :label="$t('expert_dash.response_time')" :value="stats.response_hours" :suffix="$t('kpi.hours')" icon="clock" tone="emerald" />
            <StatCard :label="$t('expert_dash.completed')" :value="stats.completed_cases" icon="check" tone="emerald" />
        </section>
        <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
            <section>
                <h2 class="mb-4 text-lg font-semibold text-ink">{{ $t('expert_dash.active_cases') }}</h2>
                <div v-if="cases.length" class="grid grid-cols-1 gap-4 md:grid-cols-2"><CaseCard v-for="c in cases" :key="c.id" :item="c" :href="route('expert.cases.show', { case: c.number })" /></div>
                <EmptyState v-else icon="folder" :title="$t('expert_dash.no_cases')" :text="$t('expert_dash.no_cases_hint')" />
            </section>
            <aside class="space-y-6">
                <Card :title="$t('dashboard.tasks')">
                    <ul v-if="tasks.length" class="space-y-2">
                        <li v-for="tk in tasks" :key="tk.id"><Link :href="route('expert.cases.show', { case: tk.case_number, tab: 'workspace' })" class="flex items-start gap-3 rounded-2xl p-2 hover:bg-navy-50"><Icon name="task" class="mt-0.5 text-navy-700" /><div><p class="text-sm font-medium">{{ tk.title }}</p><p class="text-xs text-gray-500"><span dir="ltr">{{ tk.case_number }}</span><template v-if="tk.due_at"> · {{ relative(tk.due_at) }}</template></p></div></Link></li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('dashboard.no_tasks') }}</p>
                </Card>
                <Card :title="$t('dashboard.appointments')">
                    <ul v-if="appointments.length" class="space-y-3">
                        <li v-for="a in appointments" :key="a.id" class="text-sm"><p class="font-medium">{{ a.title }}</p><p class="text-gray-500">{{ dateTime(a.starts_at) }} · <span dir="ltr">{{ a.case_number }}</span></p></li>
                    </ul>
                    <p v-else class="text-sm text-gray-500">{{ $t('dashboard.no_appointments') }}</p>
                </Card>
            </aside>
        </div>
    </AppLayout>
</template>
