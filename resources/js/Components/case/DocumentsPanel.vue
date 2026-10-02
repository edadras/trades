<script setup>
import { useForm } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import Uploader from '@/Components/ui/Uploader.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ item: Object });
const { dateTime, number } = useI18n();
const form = useForm({ files: [], title: '' });
const upload = () => form.post(route('cases.documents.store', { case: props.item.number }), { forceFormData: true, preserveScroll: true, onSuccess: () => form.reset() });
const size = (b) => (b > 1048576 ? `${number(Math.round(b / 104857.6) / 10)} MB` : `${number(Math.round(b / 1024))} KB`);
const scanTone = { clean: 'green', skipped: 'gray', pending: 'amber', infected: 'red', error: 'red' };
</script>

<template>
    <div class="space-y-6">
        <Card v-if="item.can.participate || item.can.edit_problem" :title="$t('documents.upload')" :subtitle="$t('documents.secure_hint')">
            <Uploader v-model="form.files" :error="form.errors['files.0'] || form.errors.files" />
            <Button v-if="form.files.length" class="mt-4" icon="upload" :loading="form.processing" @click="upload">{{ $t('common.upload') }}</Button>
        </Card>
        <Card :title="$t('documents.title')" :padded="false">
            <ul v-if="item.documents.length" class="divide-y divide-[var(--border)]">
                <li v-for="d in item.documents" :key="d.id" class="flex flex-wrap items-center gap-3 px-5 py-4 sm:px-6">
                    <span class="grid size-10 place-items-center rounded-xl bg-navy-50 text-navy-700"><Icon name="file" :size="18" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-ink">{{ d.name }}</p>
                        <p class="text-xs text-gray-500">{{ d.uploader }} · {{ dateTime(d.created_at) }} · <span dir="ltr">{{ size(d.size) }}</span></p>
                    </div>
                    <Badge :tone="scanTone[d.scan_status]">{{ $t(`scan.${d.scan_status}`) }}</Badge>
                    <Button v-if="d.url" :href="d.url" external size="sm" variant="light" icon="download">{{ $t('common.download') }}</Button>
                </li>
            </ul>
            <EmptyState v-else class="m-5" icon="file" :title="$t('documents.empty')" />
        </Card>
        <p class="flex items-center gap-2 text-xs text-gray-500"><Icon name="shield" :size="14" />{{ $t('documents.audit_note') }}</p>
    </div>
</template>
