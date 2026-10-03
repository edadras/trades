<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import Badge from '@/Components/ui/Badge.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const canOpenCases = computed(() => ['cases.review', 'cases.view_all'].some((p) => usePage().props.auth.user?.permissions?.includes(p)));

const props = defineProps({ tab: String, can: Object, requests: Array, paths: Array, dataRequests: Array, complaints: Array });
const { t, dateTime, relative } = useI18n();

/* Tabs */
const tabs = computed(() => [
    { key: 'legal', label: t('compliance.tabs.legal'), count: props.requests.filter((r) => r.status === 'pending').length },
    { key: 'data', label: t('compliance.tabs.data'), count: props.dataRequests.filter((d) => d.status === 'pending').length },
    { key: 'complaints', label: t('compliance.tabs.complaints'), count: props.complaints.filter((c) => ['open', 'in_review'].includes(c.status)).length },
].filter((tab) => props.can[tab.key]));
const resolveTab = (key) => (tabs.value.some((tab) => tab.key === key) ? key : tabs.value[0]?.key);
const activeTab = ref(resolveTab(props.tab));
watch(() => props.tab, (key) => (activeTab.value = resolveTab(key)));
function switchTab(key) {
    activeTab.value = key;
    router.get(route('admin.compliance.index'), { tab: key }, { preserveState: true, preserveScroll: true, replace: true });
}

/* Tones */
const requestTone = { pending: 'amber', approved: 'green', rejected: 'red' };
const modeTone = { allowed: 'green', review_required: 'amber', disabled: 'gray' };
const dataTone = { pending: 'amber', processing: 'sky', completed: 'green', rejected: 'red' };
const complaintTone = { open: 'amber', in_review: 'sky', resolved: 'green', rejected: 'red' };

/* Legal: collaboration requests (already ordered pending-first by the server; keep that order stable here). */
const sortedRequests = computed(() => [...props.requests].sort((a, b) => (a.status === 'pending' ? 0 : 1) - (b.status === 'pending' ? 0 : 1)));
const deciding = ref(null);
const decisionForm = useForm({ approve: true, conditions: '' });
const decisionOptions = computed(() => [{ value: 'approve', label: t('compliance.approve') }, { value: 'reject', label: t('compliance.reject') }]);
const decisionChoice = computed({ get: () => (decisionForm.approve ? 'approve' : 'reject'), set: (v) => (decisionForm.approve = v === 'approve') });
function openDecision(request) {
    deciding.value = request;
    decisionForm.reset();
    decisionForm.clearErrors();
}
const submitDecision = () => decisionForm.post(route('admin.compliance.requests.decide', { collaboration: deciding.value.id }), { preserveScroll: true, onSuccess: () => (deciding.value = null) });

/* Legal: service paths, edited inline per row. */
const pathDrafts = reactive({});
const pathErrors = reactive({});
const savingPath = ref(null);
function syncPathDrafts() {
    for (const path of props.paths) pathDrafts[path.id] = { mode: path.mode, documents: (path.required_documents ?? []).join('\n') };
}
syncPathDrafts();
watch(() => props.paths, syncPathDrafts);
const modeOptions = computed(() => ['allowed', 'review_required', 'disabled'].map((mode) => ({ value: mode, label: t(`compliance.modes.${mode}`) })));
const isPathDirty = (path) => pathDrafts[path.id] && (pathDrafts[path.id].mode !== path.mode || pathDrafts[path.id].documents.trim() !== (path.required_documents ?? []).join('\n'));
function savePath(path) {
    const draft = pathDrafts[path.id];
    savingPath.value = path.id;
    pathErrors[path.id] = null;
    router.put(route('admin.compliance.paths.update', { path: path.id }), {
        mode: draft.mode,
        required_documents: draft.documents.split('\n').map((line) => line.trim()).filter(Boolean),
    }, {
        preserveScroll: true,
        onError: (errors) => (pathErrors[path.id] = Object.values(errors)[0]),
        onFinish: () => (savingPath.value = null),
    });
}

/* Data requests */
const decidingData = ref(null);
const dataForm = useForm({ approve: false, resolution: '' });
function openDataDecision(request) {
    decidingData.value = request;
    dataForm.reset();
    dataForm.clearErrors();
}
function submitDataDecision(approve) {
    dataForm.approve = approve;
    dataForm.post(route('admin.compliance.data.decide', { dataRequest: decidingData.value.id }), { preserveScroll: true, onSuccess: () => (decidingData.value = null) });
}

/* Complaints */
const expandedComplaint = ref(null);
const complaintForm = useForm({ status: 'open', resolution: '' });
const complaintStatusOptions = computed(() => ['open', 'in_review', 'resolved', 'rejected'].map((status) => ({ value: status, label: t(`complaints.statuses.${status}`) })));
const resolutionRequired = computed(() => ['resolved', 'rejected'].includes(complaintForm.status));
function toggleComplaint(complaint) {
    if (expandedComplaint.value === complaint.id) {
        expandedComplaint.value = null;
        return;
    }
    expandedComplaint.value = complaint.id;
    complaintForm.clearErrors();
    complaintForm.status = complaint.status;
    complaintForm.resolution = complaint.resolution ?? '';
}
const saveComplaint = (complaint) => complaintForm
    .transform((data) => ({ ...data, resolution: data.resolution || null }))
    .put(route('admin.compliance.complaints.update', { complaint: complaint.id }), { preserveScroll: true });
</script>

<template>
    <AppLayout :title="$t('nav.compliance')" :subtitle="$t('compliance.subtitle')" wide>
        <Tabs v-if="tabs.length > 1" :tabs="tabs" :model-value="activeTab" class="mb-6" @update:model-value="switchTab" />

        <!-- Legal & collaboration -->
        <div v-if="activeTab === 'legal'" class="space-y-6">
            <div class="flex gap-3 rounded-[var(--radius-card)] bg-navy-950 p-5 text-white sm:p-6">
                <span class="grid size-10 shrink-0 place-items-center rounded-2xl bg-white/15"><Icon name="scale" :size="18" /></span>
                <div class="min-w-0 space-y-1.5 text-sm leading-7">
                    <p class="font-semibold">{{ $t('compliance.note_title') }}</p>
                    <p class="text-white/80">{{ $t('compliance.note_body') }}</p>
                    <p class="flex items-start gap-2 text-white/80"><Icon name="lock" :size="15" class="mt-1.5 shrink-0" />{{ $t('compliance.note_cross_border') }}</p>
                </div>
            </div>

            <Card :title="$t('compliance.requests')" :subtitle="$t('compliance.requests_hint')" :padded="false">
                <div v-if="!sortedRequests.length" class="p-5 sm:p-6"><EmptyState icon="shield-check" :title="$t('compliance.no_requests')" /></div>
                <ul v-else class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                    <li v-for="r in sortedRequests" :key="r.id" class="px-5 py-4 sm:px-6" :class="r.status === 'pending' ? 'bg-amber-50/40' : ''">
                        <div class="flex flex-wrap items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="flex flex-wrap items-center gap-2 font-medium text-ink">
                                    {{ r.path?.name ?? '—' }}
                                    <Badge :tone="requestTone[r.status] ?? 'gray'" dot>{{ $t(`compliance.request_status.${r.status}`) }}</Badge>
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    <span v-if="r.case_number">{{ $t('compliance.case') }} <Link v-if="canOpenCases" :href="route('review.cases.show', { case: r.case_number })" class="font-medium text-navy-700 hover:underline" dir="ltr">{{ r.case_number }}</Link><span v-else dir="ltr">{{ r.case_number }}</span> · </span>
                                    {{ $t('compliance.requester') }}: {{ r.requester ?? '—' }} ·
                                    <span :title="dateTime(r.created_at)">{{ relative(r.created_at) }}</span>
                                </p>
                            </div>
                            <Button v-if="r.status === 'pending'" size="sm" icon="scale" @click="openDecision(r)">{{ $t('compliance.decide') }}</Button>
                        </div>
                        <div v-if="r.description" class="mt-3 rounded-2xl bg-white p-3 text-sm leading-7 text-gray-700 ring-1 ring-[var(--border)]">
                            <p class="mb-1 text-xs font-medium text-gray-500">{{ $t('compliance.justification') }}</p>
                            <p class="whitespace-pre-line break-words">{{ r.description }}</p>
                        </div>
                        <div v-if="r.status !== 'pending'" class="mt-2 text-xs text-gray-500">
                            <span v-if="r.reviewer">{{ $t('compliance.reviewed_by', { name: r.reviewer }) }}</span>
                            <span v-if="r.reviewed_at"> · {{ dateTime(r.reviewed_at) }}</span>
                            <p v-if="r.conditions" class="mt-1 whitespace-pre-line break-words text-sm text-gray-700"><span class="font-medium">{{ $t('compliance.conditions') }}:</span> {{ r.conditions }}</p>
                        </div>
                    </li>
                </ul>
            </Card>

            <Card :title="$t('compliance.paths')" :subtitle="$t('compliance.paths_hint')" :padded="false">
                <ul class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                    <li v-for="p in paths" :key="p.id" class="grid grid-cols-1 gap-3 px-5 py-4 sm:px-6 lg:grid-cols-[minmax(0,1.2fr)_12rem_minmax(0,1.5fr)_auto] lg:items-start">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-medium text-ink">{{ p.name }} <Badge :tone="modeTone[p.mode] ?? 'gray'">{{ $t(`compliance.modes.${p.mode}`) }}</Badge></p>
                            <p class="text-xs text-gray-400" dir="ltr">{{ p.key }}</p>
                            <p v-if="p.description" class="mt-1 text-sm text-gray-500">{{ p.description }}</p>
                        </div>
                        <Field v-if="pathDrafts[p.id]" v-model="pathDrafts[p.id].mode" as="select" :label="$t('compliance.mode')" :options="modeOptions" required />
                        <Field v-if="pathDrafts[p.id]" v-model="pathDrafts[p.id].documents" as="textarea" :rows="3" :label="$t('compliance.required_documents')" :hint="$t('compliance.required_documents_hint')" :error="pathErrors[p.id] ?? undefined" />
                        <div class="lg:pt-7">
                            <Button size="sm" icon="check" :variant="isPathDirty(p) ? 'primary' : 'light'" :disabled="!isPathDirty(p)" :loading="savingPath === p.id" @click="savePath(p)">{{ $t('compliance.save_path') }}</Button>
                        </div>
                    </li>
                </ul>
            </Card>
        </div>

        <!-- Data requests -->
        <Card v-else-if="activeTab === 'data'" :title="$t('data_requests.title')" :subtitle="$t('data_requests.hint')" :padded="false">
            <div v-if="!dataRequests.length" class="p-5 sm:p-6"><EmptyState icon="shield" :title="$t('data_requests.empty')" /></div>
            <ul v-else class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                <li v-for="d in dataRequests" :key="d.id" class="flex flex-wrap items-start gap-3 px-5 py-4 sm:px-6">
                    <span class="grid size-10 shrink-0 place-items-center rounded-2xl" :class="d.type === 'delete' ? 'bg-rose-50 text-rose-700' : 'bg-navy-50 text-navy-800'">
                        <Icon :name="d.type === 'delete' ? 'trash' : 'download'" :size="18" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 font-medium text-ink">
                            {{ $t(`data_requests.types.${d.type}`) }}
                            <Badge :tone="dataTone[d.status] ?? 'gray'" dot>{{ $t(`data_requests.statuses.${d.status}`) }}</Badge>
                        </p>
                        <p class="mt-0.5 text-xs text-gray-500">
                            {{ d.user?.name ?? $t('data_requests.deleted_user') }}
                            <span v-if="d.user?.email" dir="ltr"> · {{ d.user.email }}</span>
                            · <span :title="dateTime(d.created_at)">{{ relative(d.created_at) }}</span>
                        </p>
                        <p class="mt-2 whitespace-pre-line break-words text-sm text-gray-700"><span class="text-gray-500">{{ $t('data_requests.reason') }}:</span> {{ d.reason || $t('data_requests.no_reason') }}</p>
                        <p v-if="d.resolution" class="mt-1 whitespace-pre-line break-words text-sm text-gray-700"><span class="text-gray-500">{{ $t('data_requests.resolution') }}:</span> {{ d.resolution }}</p>
                        <p v-if="d.handler" class="mt-1 text-xs text-gray-500">{{ $t('data_requests.handled_by', { name: d.handler }) }}</p>
                    </div>
                    <Button v-if="d.type === 'delete' && d.status === 'pending'" size="sm" variant="light" icon="scale" @click="openDataDecision(d)">{{ $t('data_requests.decide') }}</Button>
                </li>
            </ul>
        </Card>

        <!-- Complaints -->
        <Card v-else-if="activeTab === 'complaints'" :title="$t('complaints.title')" :subtitle="$t('complaints.hint')" :padded="false">
            <div v-if="!complaints.length" class="p-5 sm:p-6"><EmptyState icon="flag" :title="$t('complaints.empty')" /></div>
            <ul v-else class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                <li v-for="c in complaints" :key="c.id" class="px-5 py-4 sm:px-6">
                    <button type="button" class="flex w-full items-start gap-3 text-start" :aria-expanded="expandedComplaint === c.id" @click="toggleComplaint(c)">
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-medium text-ink">
                                <span class="break-words">{{ c.subject }}</span>
                                <Badge :tone="complaintTone[c.status] ?? 'gray'" dot>{{ $t(`complaints.statuses.${c.status}`) }}</Badge>
                                <Badge tone="navy">{{ $t(`complaints.categories.${c.category}`) }}</Badge>
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ c.user?.name ?? '—' }} ·
                                <span v-if="c.case_number">{{ $t('compliance.case') }} <Link v-if="canOpenCases" :href="route('review.cases.show', { case: c.case_number })" class="font-medium text-navy-700 hover:underline" dir="ltr">{{ c.case_number }}</Link><span v-else dir="ltr">{{ c.case_number }}</span></span>
                                <span v-else>{{ $t('complaints.no_case') }}</span>
                                · <span :title="dateTime(c.created_at)">{{ relative(c.created_at) }}</span>
                                <span v-if="c.assignee"> · {{ $t('complaints.assignee', { name: c.assignee.name }) }}</span>
                                <span v-if="c.resolved_at"> · {{ $t('complaints.resolved_at', { when: dateTime(c.resolved_at) }) }}</span>
                            </p>
                        </div>
                        <span class="mt-1 inline-flex shrink-0 items-center gap-1 text-xs font-medium text-navy-700">
                            <span class="hidden sm:inline">{{ expandedComplaint === c.id ? $t('complaints.hide_body') : $t('complaints.show_body') }}</span>
                            <Icon :name="expandedComplaint === c.id ? 'chevron-up' : 'chevron-down'" :size="16" />
                        </span>
                    </button>
                    <div v-if="expandedComplaint === c.id" class="mt-4 space-y-4">
                        <div class="rounded-2xl bg-navy-50/50 p-4 text-sm leading-7 text-gray-800 ring-1 ring-[var(--border)]">
                            <p class="mb-1 text-xs font-medium text-gray-500">{{ $t('complaints.body') }}</p>
                            <p class="whitespace-pre-line break-words">{{ c.body }}</p>
                        </div>
                        <form class="grid grid-cols-1 gap-4 sm:grid-cols-[14rem_minmax(0,1fr)]" @submit.prevent="saveComplaint(c)">
                            <Field v-model="complaintForm.status" as="select" :label="$t('complaints.status')" :options="complaintStatusOptions" required :error="complaintForm.errors.status" />
                            <Field v-model="complaintForm.resolution" as="textarea" :rows="3" :label="$t('complaints.resolution')" :hint="$t('complaints.resolution_hint')" :required="resolutionRequired" :error="complaintForm.errors.resolution" />
                            <div class="sm:col-span-2 flex justify-end">
                                <Button type="submit" size="sm" icon="check" :loading="complaintForm.processing">{{ $t('complaints.update') }}</Button>
                            </div>
                        </form>
                    </div>
                </li>
            </ul>
        </Card>

        <!-- Collaboration decision -->
        <Modal :show="!!deciding" :title="$t('compliance.decide_title', { path: deciding?.path?.name ?? '' })" width="max-w-xl" @close="deciding = null">
            <form id="decision-form" class="space-y-4" @submit.prevent="submitDecision">
                <p v-if="deciding?.case_number" class="text-sm text-gray-500">{{ $t('compliance.case') }} <span dir="ltr">{{ deciding.case_number }}</span> · {{ deciding.requester }}</p>
                <div>
                    <p class="label">{{ $t('compliance.decision') }}</p>
                    <ChoiceChips v-model="decisionChoice" :options="decisionOptions" />
                    <p v-if="decisionForm.errors.approve" class="mt-1.5 text-sm text-rose-600">{{ decisionForm.errors.approve }}</p>
                </div>
                <Field v-model="decisionForm.conditions" as="textarea" :rows="4" :label="$t('compliance.conditions')" :hint="$t('compliance.conditions_hint')" :error="decisionForm.errors.conditions" />
                <p v-if="decisionForm.errors.status" class="text-sm text-rose-600">{{ decisionForm.errors.status }}</p>
            </form>
            <template #footer>
                <Button type="submit" form="decision-form" :variant="decisionForm.approve ? 'success' : 'danger'" :icon="decisionForm.approve ? 'check' : 'x'" :loading="decisionForm.processing">
                    {{ decisionForm.approve ? $t('compliance.approve') : $t('compliance.reject') }}
                </Button>
            </template>
        </Modal>

        <!-- Data deletion decision -->
        <Modal :show="!!decidingData" :title="$t('data_requests.decide_title', { name: decidingData?.user?.name ?? $t('data_requests.deleted_user') })" width="max-w-xl" @close="decidingData = null">
            <div class="space-y-4">
                <div class="flex gap-3 rounded-2xl bg-rose-50 p-4 text-sm leading-7 text-rose-800 ring-1 ring-rose-200">
                    <Icon name="alert" :size="18" class="mt-1 shrink-0" />
                    <p>{{ $t('data_requests.warning') }}</p>
                </div>
                <p v-if="decidingData?.reason" class="whitespace-pre-line break-words text-sm text-gray-700"><span class="text-gray-500">{{ $t('data_requests.reason') }}:</span> {{ decidingData.reason }}</p>
                <Field v-model="dataForm.resolution" as="textarea" :rows="3" :label="$t('data_requests.resolution')" :hint="$t('data_requests.resolution_hint')" required :error="dataForm.errors.resolution" />
                <p v-if="dataForm.errors.approve" class="text-sm text-rose-600">{{ dataForm.errors.approve }}</p>
            </div>
            <template #footer>
                <div class="flex flex-wrap justify-end gap-2">
                    <Button variant="light" icon="x" :disabled="!dataForm.resolution.trim()" :loading="dataForm.processing && !dataForm.approve" @click="submitDataDecision(false)">{{ $t('data_requests.reject') }}</Button>
                    <Button variant="danger" icon="trash" :disabled="!dataForm.resolution.trim()" :loading="dataForm.processing && dataForm.approve" @click="submitDataDecision(true)">{{ $t('data_requests.approve') }}</Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
