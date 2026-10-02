<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ item: Object });
const { relative } = useI18n();
const form = useForm({ body: '', visibility: props.item.can.internal_notes ? 'internal' : 'team' });
const save = () => form.post(route('cases.notes.store', { case: props.item.number }), { preserveScroll: true, onSuccess: () => form.reset('body') });
</script>

<template>
    <Card :title="$t('notes.title')">
        <form v-if="item.can.participate" class="mb-6 space-y-3" @submit.prevent="save">
            <Field v-model="form.body" as="textarea" :rows="3" :placeholder="$t('notes.placeholder')" :error="form.errors.body" />
            <div class="flex flex-wrap items-center justify-between gap-3">
                <select v-if="item.can.internal_notes" v-model="form.visibility" class="input w-auto py-2 text-sm">
                    <option value="internal">{{ $t('notes.internal') }}</option>
                    <option value="team">{{ $t('notes.team') }}</option>
                </select>
                <span v-else />
                <Button type="submit" size="sm" icon="note" :loading="form.processing" :disabled="!form.body">{{ $t('notes.add') }}</Button>
            </div>
        </form>
        <ul class="space-y-4">
            <li v-for="n in item.notes" :key="n.id" class="flex gap-3">
                <Avatar :name="n.user" size="sm" />
                <div class="min-w-0 flex-1 rounded-2xl p-3" :class="n.visibility === 'internal' ? 'bg-amber-50 ring-1 ring-amber-200' : 'bg-[var(--surface-muted)]'">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500"><span class="font-medium text-ink">{{ n.user }}</span>{{ relative(n.created_at) }}<Badge v-if="n.visibility === 'internal'" tone="amber">{{ $t('notes.internal') }}</Badge></div>
                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700">{{ n.body }}</p>
                </div>
            </li>
            <li v-if="!item.notes.length" class="text-sm text-gray-500">{{ $t('notes.empty') }}</li>
        </ul>
    </Card>
</template>
