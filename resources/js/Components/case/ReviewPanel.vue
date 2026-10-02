<script setup>
// Internal case-expert controls: confirm/edit AI classification, request info, escalate, re-run, match, assign.
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import ExpertCard from '@/Components/domain/ExpertCard.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object, categories: Array });
const page = usePage();
const { t, percent, relative } = useI18n();
const roots = computed(() => props.categories.filter((c) => !c.parent_id).map((c) => ({ value: c.id, label: c.name })));
const form = useForm({ decision: 'confirmed', category_id: props.item.category?.id ?? '', subcategory_id: props.item.subcategory?.id ?? '', urgency: props.item.urgency ?? 'medium', notes: '', request: '', run_matching: true });
const subs = computed(() => props.categories.filter((c) => c.parent_id === Number(form.category_id)).map((c) => ({ value: c.id, label: c.name })));
const urgencies = ['low', 'medium', 'high', 'critical'].map((u) => ({ value: u, label: t(`urgency.${u}`) }));
const pending = computed(() => props.item.reviews.find((r) => r.status === 'pending'));

function submit(decision) {
    form.decision = decision === 'confirmed' && (Number(form.category_id) !== props.item.category?.id || form.urgency !== props.item.urgency || Number(form.subcategory_id || 0) !== (props.item.subcategory?.id ?? 0)) ? 'edited' : decision;
    form.transform((d) => ({ ...d, subcategory_id: d.subcategory_id || null })).post(route('review.cases.decide', { case: props.item.number }), { preserveScroll: true });
}
const busy = ref(false);
const action = (name) => router.post(route(name, { case: props.item.number }), {}, { preserveScroll: true, onStart: () => (busy.value = true), onFinish: () => (busy.value = false) });
const assignMe = () => router.post(route('review.cases.manager', { case: props.item.number }), { user_id: page.props.auth.user.id }, { preserveScroll: true });

const picker = ref(false);
const candidates = ref([]);
async function openPicker() {
    picker.value = true;
    const res = await fetch(route('review.cases.candidates', { case: props.item.number }), { headers: { Accept: 'application/json' } });
    candidates.value = (await res.json()).candidates;
}
const propose = (id) => router.post(route('review.cases.propose', { case: props.item.number }), { expert_profile_id: id }, { preserveScroll: true, onSuccess: () => (picker.value = false) });
</script>

<template>
    <div class="space-y-4">
        <Card :title="$t('review.panel_title')">
            <div v-if="pending" class="mb-4 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                <p class="font-medium">{{ $t(`review_reason.${pending.reason}`) }}</p>
                <p class="text-xs">{{ $t('review.waiting_since', { time: relative(pending.created_at) }) }} · AI: {{ pending.ai_category ?? '—' }} · {{ percent(Math.round((pending.ai_confidence ?? 0) * 100)) }}</p>
            </div>
            <form class="space-y-4" @submit.prevent="submit('confirmed')">
                <Field v-model="form.category_id" as="select" :options="roots" :label="$t('analysis.category')" @update:model-value="form.subcategory_id = ''" />
                <Field v-if="subs.length" v-model="form.subcategory_id" as="select" :options="subs" :label="$t('analysis.subcategory')" />
                <Field v-model="form.urgency" as="select" :options="urgencies" :label="$t('urgency.label')" />
                <Field v-model="form.notes" as="textarea" :rows="2" :label="$t('review.notes')" :hint="$t('review.notes_hint')" />
                <Checkbox v-model="form.run_matching" :label="$t('review.run_matching')" />
                <div class="grid gap-2">
                    <Button type="submit" icon="check" block :loading="form.processing && ['confirmed', 'edited'].includes(form.decision)">{{ $t('review.confirm') }}</Button>
                    <Field v-model="form.request" :placeholder="$t('review.request_placeholder')" :error="form.errors.request" />
                    <div class="grid grid-cols-2 gap-2">
                        <Button variant="light" size="sm" icon="file" @click="submit('info_requested')">{{ $t('review.request_info') }}</Button>
                        <Button variant="outline" size="sm" icon="alert" @click="submit('escalated')">{{ $t('review.escalate') }}</Button>
                    </div>
                </div>
            </form>
        </Card>
        <Card :title="$t('review.tools')">
            <div class="grid gap-2">
                <Button variant="light" size="sm" icon="refresh" :loading="busy" @click="action('review.cases.reanalyze')">{{ $t('review.reanalyze') }}</Button>
                <Button v-if="item.can.assign" variant="light" size="sm" icon="network" :loading="busy" @click="action('review.cases.matching')">{{ $t('review.rematch') }}</Button>
                <Button v-if="item.can.assign" variant="light" size="sm" icon="users" @click="openPicker">{{ $t('review.propose_expert') }}</Button>
                <Button v-if="item.can.assign && item.case_manager?.id !== page.props.auth.user.id" variant="ghost" size="sm" icon="user" @click="assignMe">{{ $t('review.assign_me') }}</Button>
            </div>
            <p class="mt-4 text-xs text-gray-500">{{ $t('review.manager') }}: {{ item.case_manager?.name ?? '—' }}</p>
        </Card>
        <Card v-if="item.reviews.length" :title="$t('review.history')">
            <ul class="space-y-3 text-sm">
                <li v-for="r in item.reviews" :key="r.id" class="rounded-2xl bg-[var(--surface-muted)] p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge :tone="r.status === 'pending' ? 'amber' : 'green'">{{ r.decision ? $t(`decision.${r.decision}`) : $t('review.pending') }}</Badge>
                        <Badge v-if="r.category_agreed === true" tone="green">{{ $t('review.ai_agreed') }}</Badge>
                        <Badge v-if="r.category_agreed === false" tone="red">{{ $t('review.ai_corrected') }}</Badge>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">AI: {{ r.ai_category ?? '—' }} → {{ r.final_category ?? '—' }} · {{ r.reviewer ?? '' }} {{ r.reviewed_at ? relative(r.reviewed_at) : '' }}</p>
                    <p v-if="r.notes" class="mt-1 text-xs text-gray-600">{{ r.notes }}</p>
                </li>
            </ul>
        </Card>
        <Modal :show="picker" :title="$t('review.propose_expert')" width="max-w-3xl" @close="picker = false">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <ExpertCard v-for="c in candidates" :key="c.expert.id" :expert="c.expert" :score="c.score ?? undefined" :reasons="c.reasons">
                    <Button size="sm" class="mt-4" icon="plus" @click="propose(c.expert.id)">{{ $t('review.propose') }}</Button>
                </ExpertCard>
            </div>
            <p v-if="!candidates.length" class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
        </Modal>
    </div>
</template>
