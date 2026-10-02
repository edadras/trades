<script setup>
// Action plan: tasks / next actions with owner & deadline, appointments, and the document request shortcut.
import { ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import Modal from '@/Components/ui/Modal.vue';
import Field from '@/Components/ui/Field.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object });
const { t, relative, dateTime } = useI18n();
const isBusiness = props.item.context === 'business';
const taskModal = ref(false);
const meetingModal = ref(false);
const requestModal = ref(false);
const task = useForm({ title: '', description: '', owner_role: 'business', due_at: '', is_next_action: true });
const meeting = useForm({ title: '', agenda: '', starts_at: '', ends_at: '', meeting_url: '', location: '' });
const request = useForm({ what: '', due_at: '' });
const setStatus = (taskItem, status) => router.patch(route('cases.tasks.update', { case: props.item.number, task: taskItem.id }), { status }, { preserveScroll: true });
const owners = ['business', 'expert', 'case_manager'].map((v) => ({ value: v, label: t(`roles_short.${v}`) }));
const post = (form, name, modal) => form.post(route(name, { case: props.item.number }), { preserveScroll: true, onSuccess: () => { form.reset(); modal.value = false; } });
const submitTask = () => post(task, 'cases.tasks.store', taskModal);
const submitMeeting = () => post(meeting, 'cases.appointments.store', meetingModal);
const submitRequest = () => post(request, 'cases.documents.request', requestModal);
</script>

<template>
    <div class="space-y-6">
        <Card :title="$t('tasks.title')" :subtitle="$t('tasks.subtitle')">
            <template #actions>
                <Button v-if="item.can.participate && !isBusiness" size="sm" variant="light" icon="file" @click="requestModal = true">{{ $t('tasks.request_document') }}</Button>
                <Button v-if="item.can.participate" size="sm" icon="plus" @click="taskModal = true">{{ $t('tasks.add') }}</Button>
            </template>
            <ul v-if="item.tasks.length" class="divide-y divide-[var(--border)]">
                <li v-for="tk in item.tasks" :key="tk.id" class="flex items-start gap-3 py-3">
                    <button type="button" class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-lg ring-1 transition" :class="tk.status === 'done' ? 'bg-emerald-500 text-white ring-emerald-500' : 'ring-gray-300 hover:ring-navy-500'" :disabled="!item.can.participate" :aria-label="$t('tasks.toggle')" @click="setStatus(tk, tk.status === 'done' ? 'open' : 'done')">
                        <Icon v-if="tk.status === 'done'" name="check" :size="14" />
                    </button>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium" :class="tk.status === 'done' ? 'text-gray-400 line-through' : 'text-ink'">{{ tk.title }}</p>
                        <p v-if="tk.description" class="text-sm text-gray-500">{{ tk.description }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                            <Badge v-if="tk.is_next_action" tone="dark">{{ $t('case.next_action') }}</Badge>
                            <span>{{ $t('tasks.owner') }}: {{ $t(`roles_short.${tk.owner_role}`) }}<template v-if="tk.assignee"> ({{ tk.assignee.name }})</template></span>
                            <span v-if="tk.due_at" :class="tk.overdue ? 'font-medium text-rose-600' : ''" :title="dateTime(tk.due_at)"><Icon name="clock" :size="12" class="inline" /> {{ relative(tk.due_at) }}</span>
                        </div>
                    </div>
                    <select v-if="item.can.participate && tk.status !== 'done'" class="rounded-full border-0 bg-gray-50 py-1 text-xs" :value="tk.status" @change="setStatus(tk, $event.target.value)">
                        <option v-for="s in ['open', 'in_progress', 'done', 'cancelled']" :key="s" :value="s">{{ $t(`task_status.${s}`) }}</option>
                    </select>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-500">{{ $t('tasks.empty') }}</p>
        </Card>

        <Card :title="$t('appointments.title')">
            <template #actions><Button v-if="item.can.participate" size="sm" variant="light" icon="calendar" @click="meetingModal = true">{{ $t('appointments.add') }}</Button></template>
            <ul v-if="item.appointments.length" class="space-y-3">
                <li v-for="a in item.appointments" :key="a.id" class="flex items-start gap-3 rounded-2xl bg-[var(--surface-muted)] p-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-navy-950 text-white"><Icon name="video" :size="18" /></span>
                    <div class="min-w-0 flex-1 text-sm">
                        <p class="font-medium text-ink">{{ a.title }}</p>
                        <p class="text-gray-500">{{ dateTime(a.starts_at) }} · {{ a.organizer?.name }}</p>
                        <p v-if="a.agenda" class="mt-1 text-gray-600">{{ a.agenda }}</p>
                    </div>
                    <Button v-if="a.meeting_url" :href="a.meeting_url" external size="sm" icon="external">{{ $t('appointments.join') }}</Button>
                </li>
            </ul>
            <p v-else class="text-sm text-gray-500">{{ $t('appointments.empty') }}</p>
        </Card>

        <Modal :show="taskModal" :title="$t('tasks.add')" @close="taskModal = false">
            <form id="task-form" class="space-y-4" @submit.prevent="submitTask">
                <Field v-model="task.title" :label="$t('fields.title')" required :error="task.errors.title" />
                <Field v-model="task.description" as="textarea" :rows="2" :label="$t('fields.description')" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field v-model="task.owner_role" as="select" :options="owners" :label="$t('tasks.owner')" required />
                    <Field v-model="task.due_at" type="datetime-local" :label="$t('fields.due_at')" dir="ltr" :error="task.errors.due_at" />
                </div>
                <Checkbox v-model="task.is_next_action" :label="$t('tasks.mark_next')" />
            </form>
            <template #footer><Button type="submit" form="task-form" icon="check" :loading="task.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
        <Modal :show="meetingModal" :title="$t('appointments.add')" @close="meetingModal = false">
            <form id="meeting-form" class="space-y-4" @submit.prevent="submitMeeting">
                <Field v-model="meeting.title" :label="$t('fields.title')" required :error="meeting.errors.title" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <Field v-model="meeting.starts_at" type="datetime-local" :label="$t('fields.starts_at')" dir="ltr" required :error="meeting.errors.starts_at" />
                    <Field v-model="meeting.ends_at" type="datetime-local" :label="$t('fields.ends_at')" dir="ltr" required :error="meeting.errors.ends_at" />
                </div>
                <Field v-model="meeting.meeting_url" :label="$t('appointments.url')" dir="ltr" placeholder="https://" :error="meeting.errors.meeting_url" />
                <Field v-model="meeting.agenda" as="textarea" :rows="2" :label="$t('appointments.agenda')" />
            </form>
            <template #footer><Button type="submit" form="meeting-form" icon="calendar" :loading="meeting.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
        <Modal :show="requestModal" :title="$t('tasks.request_document')" @close="requestModal = false">
            <form id="request-form" class="space-y-4" @submit.prevent="submitRequest">
                <Field v-model="request.what" as="textarea" :rows="2" :label="$t('tasks.what_needed')" required :error="request.errors.what" />
                <Field v-model="request.due_at" type="datetime-local" :label="$t('fields.due_at')" dir="ltr" />
            </form>
            <template #footer><Button type="submit" form="request-form" icon="send" :loading="request.processing">{{ $t('common.send') }}</Button></template>
        </Modal>
    </div>
</template>
