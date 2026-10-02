<script setup>
// Service-path requests (e.g. commercial contract, cross-border data). Review-required paths go to legal & compliance.
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ item: Object, servicePaths: { type: Array, default: () => [] } });
const { dateTime } = useI18n();
const form = useForm({ service_path_id: null, description: '' });
const selected = computed(() => props.servicePaths.find((p) => p.id === form.service_path_id) ?? null);
const requests = computed(() => props.item.collaboration_requests ?? []);
const submit = () => form.post(route('cases.collaboration.store', { case: props.item.number }), { preserveScroll: true, onSuccess: () => form.reset() });

const statusTone = { pending: 'amber', approved: 'green', rejected: 'red' };
const modeTone = { allowed: 'green', review_required: 'amber', disabled: 'gray' };
</script>

<template>
    <Card :title="$t('collab_req.title')" :subtitle="$t('collab_req.subtitle')">
        <ul v-if="requests.length" class="space-y-3">
            <li v-for="r in requests" :key="r.id" class="rounded-2xl p-4 ring-1 ring-[var(--border)]">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-medium text-ink">{{ r.path?.name }}</p>
                    <Badge :tone="statusTone[r.status] ?? 'gray'" dot>{{ $t(`collab_req.status.${r.status}`) }}</Badge>
                </div>
                <p v-if="r.description" class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ r.description }}</p>
                <div v-if="r.conditions" class="mt-3 rounded-2xl bg-navy-50 p-3 text-sm text-navy-900">
                    <p class="text-xs font-medium text-navy-700">{{ $t('collab_req.conditions') }}</p>
                    <p class="mt-1 whitespace-pre-line">{{ r.conditions }}</p>
                </div>
                <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-400">
                    <span v-if="r.requester">{{ $t('collab_req.requested_by') }}: {{ r.requester }} · {{ dateTime(r.created_at) }}</span>
                    <span v-if="r.reviewer">{{ $t('collab_req.reviewer') }}: {{ r.reviewer }}<template v-if="r.reviewed_at"> · {{ dateTime(r.reviewed_at) }}</template></span>
                </p>
            </li>
        </ul>
        <p v-else class="text-sm text-gray-500">{{ $t('collab_req.empty') }}</p>

        <form v-if="item.can.request_collaboration && servicePaths.length" class="mt-6 space-y-4 border-t border-[var(--border)] pt-6" @submit.prevent="submit">
            <p class="font-medium text-ink">{{ $t('collab_req.new') }}</p>
            <div class="grid gap-2" role="radiogroup" :aria-label="$t('collab_req.path')">
                <label
                    v-for="p in servicePaths"
                    :key="p.id"
                    class="flex items-start gap-3 rounded-2xl p-3 ring-1 transition"
                    :class="p.mode === 'disabled' ? 'cursor-not-allowed opacity-60 ring-[var(--border)]' : form.service_path_id === p.id ? 'cursor-pointer bg-navy-50 ring-navy-300' : 'cursor-pointer ring-[var(--border)] hover:bg-gray-50'"
                >
                    <input v-model="form.service_path_id" type="radio" :value="p.id" :disabled="p.mode === 'disabled'" class="mt-1 accent-navy-900" />
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-ink">{{ p.name }}</span>
                            <Badge :tone="modeTone[p.mode] ?? 'gray'">{{ $t(`collab_req.mode.${p.mode}`) }}</Badge>
                        </span>
                        <span v-if="p.description" class="mt-0.5 block text-xs text-gray-500">{{ p.description }}</span>
                    </span>
                </label>
            </div>
            <p v-if="form.errors.service_path_id" class="text-sm text-rose-600">{{ form.errors.service_path_id }}</p>
            <div v-if="selected" class="rounded-2xl p-3 text-sm ring-1" :class="selected.mode === 'review_required' ? 'bg-amber-50 text-amber-900 ring-amber-200' : 'bg-emerald-50 text-emerald-800 ring-emerald-200'">
                <p class="flex items-start gap-2"><Icon :name="selected.mode === 'review_required' ? 'scale' : 'check'" :size="16" class="mt-0.5 shrink-0" />{{ $t(`collab_req.mode_hint.${selected.mode}`) }}</p>
                <p v-if="selected.required_documents?.length" class="mt-2 text-xs">{{ $t('collab_req.required_documents') }}: {{ selected.required_documents.join('، ') }}</p>
            </div>
            <Field v-model="form.description" as="textarea" :rows="3" required :label="$t('collab_req.description')" :placeholder="$t('collab_req.description_placeholder')" :error="form.errors.description" />
            <Button type="submit" icon="send" :loading="form.processing" :disabled="!form.service_path_id || form.description.trim().length < 10">{{ $t('collab_req.submit') }}</Button>
        </form>
    </Card>
</template>
