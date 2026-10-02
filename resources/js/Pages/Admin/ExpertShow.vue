<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ expert: Object });
const { t, dateTime } = useI18n();
const checks = ['identity', 'experience', 'certificates', 'references', 'nda'];
const form = useForm({ status: 'verified', checklist: Object.fromEntries(checks.map((c) => [c, false])), notes: '' });
const decide = (status) => { form.status = status; form.post(route('admin.experts.verify', { expert: props.expert.id }), { preserveScroll: true }); };
</script>

<template>
    <AppLayout :title="expert.name" :back="route('admin.experts.index')" wide>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div class="space-y-6">
                <ExpertCard :expert="expert" />
                <Card :title="$t('expert_profile.about')"><p class="whitespace-pre-line text-sm leading-7 text-gray-700">{{ expert.bio }}</p>
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">{{ $t('fields.email') }}</dt><dd dir="ltr">{{ expert.email }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">NDA</dt><dd>{{ expert.nda_accepted_at ? dateTime(expert.nda_accepted_at) : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">{{ $t('fields.max_active_cases') }}</dt><dd>{{ expert.max_active_cases }}</dd></div>
                        <div v-if="expert.linkedin_url" class="flex justify-between"><dt class="text-gray-500">{{ $t('fields.linkedin') }}</dt><dd><a :href="expert.linkedin_url" class="text-navy-700 hover:underline" target="_blank" rel="noopener">LinkedIn</a></dd></div>
                    </dl>
                </Card>
                <Card :title="$t('expert_profile.certifications')"><ul class="space-y-1 text-sm"><li v-for="(c, i) in expert.certifications" :key="i">{{ c.title }} — {{ c.issuer }} <span dir="ltr">{{ c.year }}</span></li></ul></Card>
                <Card :title="$t('expert_profile.documents')"><ul class="space-y-2 text-sm"><li v-for="d in expert.documents" :key="d.id" class="flex items-center gap-2"><Icon name="file" :size="16" /><a v-if="d.url" :href="d.url" class="hover:underline">{{ d.name }}</a><span v-else>{{ d.name }}</span><Badge tone="gray">{{ $t(`expert_doc.${d.type}`) }}</Badge></li></ul></Card>
            </div>
            <div class="space-y-6">
                <Card :title="$t('admin.verification')">
                    <Badge tone="navy">{{ $t(`expert_status.${expert.status}`) }}</Badge>
                    <div class="mt-4 space-y-1"><Checkbox v-for="c in checks" :key="c" v-model="form.checklist[c]" :label="$t(`verify_check.${c}`)" /></div>
                    <Field v-model="form.notes" as="textarea" :rows="3" class="mt-4" :label="$t('admin.verification_notes')" />
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <Button icon="check" variant="success" :loading="form.processing && form.status === 'verified'" @click="decide('verified')">{{ $t('admin.verify') }}</Button>
                        <Button icon="x" variant="danger" :loading="form.processing && form.status === 'rejected'" @click="decide('rejected')">{{ $t('admin.reject') }}</Button>
                        <Button size="sm" variant="light" icon="clock" @click="decide('in_review')">{{ $t('admin.mark_in_review') }}</Button>
                        <Button size="sm" variant="ghost" icon="lock" @click="decide('suspended')">{{ $t('admin.suspend') }}</Button>
                    </div>
                </Card>
                <Card :title="$t('review.history')">
                    <ul class="space-y-3 text-sm"><li v-for="(v, i) in expert.verifications" :key="i" class="rounded-2xl bg-[var(--surface-muted)] p-3"><Badge tone="gray">{{ $t(`expert_status.${v.status}`) }}</Badge><span class="ms-2 text-xs text-gray-500">{{ v.reviewer }} · {{ dateTime(v.created_at) }}</span><p v-if="v.notes" class="mt-1 text-gray-600">{{ v.notes }}</p></li></ul>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
