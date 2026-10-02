<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ partners: Array, types: Array });
const { t, locale } = useI18n();

const typeOptions = props.types.map((type) => ({ value: type, label: t(`partners.types.${type}`) }));
const columns = [
    { key: 'name', label: t('table.name') },
    { key: 'referral', label: t('partners.referral_url') },
    { key: 'contact', label: t('partners.contact') },
    { key: 'counts', label: t('partners.counts') },
    { key: 'status', label: t('table.status') },
    { key: 'actions', label: '' },
];

const blank = () => ({ name: { fa: '', en: '' }, type: props.types[0], referral_code: '', contact_name: '', contact_email: '', referral_method: '', is_active: true });
const editing = ref(null);
const form = useForm(blank());

function open(partner) {
    editing.value = partner ?? {};
    form.clearErrors();
    Object.assign(form, partner
        ? { name: { ...partner.name }, type: partner.type, referral_code: partner.referral_code ?? '', contact_name: partner.contact_name ?? '', contact_email: partner.contact_email ?? '', referral_method: partner.referral_method ?? '', is_active: partner.is_active }
        : blank());
}

function save() {
    const payload = form.transform(({ referral_code, ...data }) => (referral_code ? { ...data, referral_code } : data));
    const options = { preserveScroll: true, onSuccess: () => (editing.value = null) };
    editing.value?.id ? payload.put(route('admin.partners.update', { partner: editing.value.id }), options) : payload.post(route('admin.partners.store'), options);
}

const copiedId = ref(null);
async function copyLink(partner) {
    try {
        await navigator.clipboard.writeText(partner.referral_url);
        copiedId.value = partner.id;
        setTimeout(() => copiedId.value === partner.id && (copiedId.value = null), 2000);
    } catch {
        window.prompt(t('partners.referral_url'), partner.referral_url);
    }
}
</script>

<template>
    <AppLayout :title="$t('nav.partners')" :subtitle="$t('partners.subtitle')" wide>
        <template #header-actions><Button size="sm" icon="plus" @click="open(null)">{{ $t('partners.add') }}</Button></template>

        <EmptyState v-if="!partners.length" icon="network" :title="$t('partners.empty_title')" :text="$t('partners.empty_text')">
            <Button size="sm" icon="plus" @click="open(null)">{{ $t('partners.add') }}</Button>
        </EmptyState>

        <Card v-else :padded="false"><div class="p-2 sm:p-4">
            <DataTable :columns="columns" :rows="partners">
                <template #cell-name="{ row }">
                    <div class="min-w-0">
                        <p class="font-medium text-ink">{{ row.name[locale] || row.name.fa || row.name.en }}</p>
                        <p class="text-xs text-gray-500">{{ $t(`partners.types.${row.type}`) }}</p>
                    </div>
                </template>
                <template #cell-referral="{ row }">
                    <div class="flex min-w-0 flex-col items-end gap-1 md:items-start">
                        <div class="flex max-w-full items-center gap-2">
                            <span class="rounded-full bg-navy-50 px-2.5 py-0.5 font-mono text-xs text-navy-800" dir="ltr">{{ row.referral_code }}</span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium text-navy-700 ring-1 ring-[var(--border)] transition hover:bg-navy-50"
                                :aria-label="$t('partners.copy')"
                                @click="copyLink(row)"
                            >
                                <Icon :name="copiedId === row.id ? 'check' : 'link'" :size="13" />
                                {{ copiedId === row.id ? $t('partners.copied') : $t('partners.copy') }}
                            </button>
                        </div>
                        <span class="block max-w-[16rem] truncate font-mono text-[11px] text-gray-400" dir="ltr" :title="row.referral_url">{{ row.referral_url }}</span>
                        <p v-if="row.referral_method" class="max-w-[16rem] truncate text-xs text-gray-500" :title="row.referral_method">{{ row.referral_method }}</p>
                    </div>
                </template>
                <template #cell-contact="{ row }">
                    <div v-if="row.contact_name || row.contact_email">
                        <p class="text-sm">{{ row.contact_name || '—' }}</p>
                        <p v-if="row.contact_email" class="text-xs text-gray-500" dir="ltr">{{ row.contact_email }}</p>
                    </div>
                    <span v-else class="text-gray-400">—</span>
                </template>
                <template #cell-counts="{ row }">
                    <span class="flex flex-wrap justify-end gap-1 md:justify-start">
                        <Badge tone="navy">{{ $t('partners.businesses_count', { count: row.businesses_count ?? 0 }) }}</Badge>
                        <Badge tone="gray">{{ $t('partners.cases_count', { count: row.cases_count ?? 0 }) }}</Badge>
                    </span>
                </template>
                <template #cell-status="{ row }">
                    <Badge :tone="row.is_active ? 'green' : 'gray'" dot>{{ row.is_active ? $t('partners.active') : $t('partners.inactive') }}</Badge>
                </template>
                <template #cell-actions="{ row }"><Button size="sm" variant="light" icon="edit" @click="open(row)">{{ $t('common.edit') }}</Button></template>
            </DataTable>
        </div></Card>

        <Modal :show="!!editing" :title="editing?.id ? $t('partners.edit') : $t('partners.add')" width="max-w-2xl" @close="editing = null">
            <form id="partner-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <Field v-model="form.name.fa" :label="$t('partners.name_fa')" required :error="form.errors['name.fa']" />
                <Field v-model="form.name.en" :label="$t('partners.name_en')" dir="ltr" required :error="form.errors['name.en']" />
                <Field v-model="form.type" as="select" :label="$t('partners.type')" :options="typeOptions" required :error="form.errors.type" />
                <Field v-model="form.referral_code" :label="$t('partners.referral_code')" :hint="$t('partners.referral_code_hint')" dir="ltr" :error="form.errors.referral_code" />
                <Field v-model="form.contact_name" :label="$t('partners.contact_name')" :error="form.errors.contact_name" />
                <Field v-model="form.contact_email" type="email" :label="$t('partners.contact_email')" dir="ltr" :error="form.errors.contact_email" />
                <Field v-model="form.referral_method" as="textarea" :rows="3" class="sm:col-span-2" :label="$t('partners.referral_method')" :hint="$t('partners.referral_method_hint')" :error="form.errors.referral_method" />
                <Checkbox v-model="form.is_active" class="sm:col-span-2" :label="$t('partners.is_active')" :description="$t('partners.is_active_hint')" :error="form.errors.is_active" />
            </form>
            <template #footer><Button type="submit" form="partner-form" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
