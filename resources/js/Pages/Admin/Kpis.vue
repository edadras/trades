<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import KpiCard from '@/Components/domain/KpiCard.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ kpis: Array, live: Array, metrics: Object });
const { t, date, number } = useI18n();
const editing = ref(null);
const blank = () => ({ key: '', name: { fa: '', en: '' }, description: { fa: '', en: '' }, metric: Object.keys(props.metrics)[0], unit: 'count', comparator: '>=', is_active: true, sort_order: 0, target_value: '', period_start: '', period_end: '' });
const form = useForm(blank());
function open(kpi) {
    editing.value = kpi ?? {};
    const current = kpi?.targets?.[0];
    Object.assign(form, kpi ? { ...kpi, name: { ...kpi.name }, description: { fa: kpi.description.fa ?? '', en: kpi.description.en ?? '' }, target_value: current?.target_value ?? '', period_start: '', period_end: '' } : blank());
}
const save = () => (editing.value?.id ? form.put(route('admin.kpis.update', { kpi: editing.value.id }), { onSuccess: () => (editing.value = null) }) : form.post(route('admin.kpis.store'), { onSuccess: () => (editing.value = null) }));
const metricOptions = Object.keys(props.metrics).map((m) => ({ value: m, label: t(`metrics.${m}`) }));
</script>

<template>
    <AppLayout :title="$t('nav.kpis')" :subtitle="$t('kpi.subtitle')" wide>
        <template #header-actions>
            <Button size="sm" variant="light" icon="refresh" @click="router.post(route('admin.kpis.snapshot'), {}, { preserveScroll: true })">{{ $t('kpi.snapshot') }}</Button>
            <Button size="sm" icon="plus" @click="open(null)">{{ $t('kpi.add') }}</Button>
        </template>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3"><KpiCard v-for="k in live" :key="k.id" :kpi="k" /></div>
        <Card class="mt-8" :title="$t('kpi.definitions')" :padded="false">
            <ul class="divide-y divide-[var(--border)]">
                <li v-for="k in kpis" :key="k.id" class="flex flex-wrap items-center gap-3 px-5 py-4 sm:px-6">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ink">{{ k.name[$page.props.app.locale] }} <Badge v-if="!k.is_active" tone="gray">{{ $t('kpi.inactive') }}</Badge></p>
                        <p class="text-xs text-gray-500"><span dir="ltr">{{ k.key }}</span> · {{ $t(`metrics.${k.metric}`) }}</p>
                    </div>
                    <div class="text-sm text-gray-600">
                        <template v-if="k.targets[0]">{{ $t('kpi.target') }}: <span dir="ltr">{{ k.comparator }} {{ number(k.targets[0].target_value) }}</span><span v-if="k.targets[0].period_end" class="text-xs text-gray-400"> ({{ date(k.targets[0].period_start) }} – {{ date(k.targets[0].period_end) }})</span></template>
                    </div>
                    <Button size="sm" variant="light" icon="edit" @click="open(k)">{{ $t('common.edit') }}</Button>
                </li>
            </ul>
        </Card>
        <Modal :show="!!editing" :title="editing?.id ? $t('kpi.edit') : $t('kpi.add')" width="max-w-2xl" @close="editing = null">
            <form id="kpi-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <Field v-model="form.name.fa" :label="$t('kpi.name_fa')" required :error="form.errors['name.fa']" />
                <Field v-model="form.name.en" :label="$t('kpi.name_en')" dir="ltr" required :error="form.errors['name.en']" />
                <Field v-model="form.key" :label="$t('kpi.key')" dir="ltr" required :error="form.errors.key" />
                <Field v-model="form.metric" as="select" :options="metricOptions" :label="$t('kpi.metric')" required />
                <Field v-model="form.unit" as="select" :options="['count', 'percent', 'hours', 'score'].map((u) => ({ value: u, label: $t(`units.${u}`) }))" :label="$t('kpi.unit')" />
                <Field v-model="form.comparator" as="select" :options="[{ value: '>=', label: '≥' }, { value: '<=', label: '≤' }]" :label="$t('kpi.comparator')" />
                <Field v-model="form.target_value" type="number" step="any" dir="ltr" :label="$t('kpi.target')" :error="form.errors.target_value" />
                <Field v-model="form.sort_order" type="number" dir="ltr" :label="$t('kpi.order')" />
                <Field v-model="form.period_start" type="date" dir="ltr" :label="$t('kpi.period_start')" />
                <Field v-model="form.period_end" type="date" dir="ltr" :label="$t('kpi.period_end')" :error="form.errors.period_end" />
                <Field v-model="form.description.fa" class="sm:col-span-2" :label="$t('kpi.description_fa')" />
                <Field v-model="form.description.en" class="sm:col-span-2" dir="ltr" :label="$t('kpi.description_en')" />
                <Checkbox v-model="form.is_active" :label="$t('kpi.active')" />
            </form>
            <template #footer><Button type="submit" form="kpi-form" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
