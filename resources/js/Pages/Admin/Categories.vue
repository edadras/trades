<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ categories: Array, urgencies: Array });
const { t, locale } = useI18n();
const roots = computed(() => props.categories.filter((c) => !c.parent_id));
const children = (id) => props.categories.filter((c) => c.parent_id === id);
const editing = ref(null);
const form = useForm({ parent_id: '', slug: '', name: { fa: '', en: '' }, description: { fa: '', en: '' }, keywords_fa: '', keywords_en: '', icon: '', is_sensitive: false, default_urgency: 'medium', sort_order: 0, is_active: true });
function open(c, parentId = null) {
    editing.value = c ?? { new: true };
    Object.assign(form, c ? { ...c, parent_id: c.parent_id ?? '', name: { ...c.name }, description: { fa: c.description.fa ?? '', en: c.description.en ?? '' }, keywords_fa: (c.keywords.fa ?? []).join('، '), keywords_en: (c.keywords.en ?? []).join(', ') }
        : { parent_id: parentId ?? '', slug: '', name: { fa: '', en: '' }, description: { fa: '', en: '' }, keywords_fa: '', keywords_en: '', icon: '', is_sensitive: false, default_urgency: 'medium', sort_order: 0, is_active: true });
}
const split = (s) => s.split(/[,،\n]/).map((x) => x.trim()).filter(Boolean);
function save() {
    const tf = form.transform((d) => ({ ...d, parent_id: d.parent_id || null, keywords: { fa: split(d.keywords_fa), en: split(d.keywords_en) } }));
    editing.value?.id ? tf.put(route('admin.categories.update', { category: editing.value.id }), { onSuccess: () => (editing.value = null) }) : tf.post(route('admin.categories.store'), { onSuccess: () => (editing.value = null) });
}
</script>

<template>
    <AppLayout :title="$t('nav.categories')" :subtitle="$t('categories.subtitle')">
        <template #header-actions><Button size="sm" icon="plus" @click="open(null)">{{ $t('categories.add') }}</Button></template>
        <div class="space-y-4">
            <Card v-for="r in roots" :key="r.id" :padded="false">
                <div class="flex flex-wrap items-center gap-3 p-5">
                    <span class="grid size-10 place-items-center rounded-2xl bg-navy-950 text-white"><Icon :name="r.icon || 'sparkles'" :size="18" /></span>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink">{{ r.name[locale] }} <Badge v-if="r.is_sensitive" tone="red">{{ $t('analysis.sensitive') }}</Badge> <Badge v-if="!r.is_active" tone="gray">{{ $t('kpi.inactive') }}</Badge></p>
                        <p class="text-xs text-gray-500" dir="ltr">{{ r.slug }} · {{ (r.keywords.fa?.length ?? 0) + (r.keywords.en?.length ?? 0) }} keywords</p>
                    </div>
                    <Button size="sm" variant="ghost" icon="plus" @click="open(null, r.id)">{{ $t('categories.add_sub') }}</Button>
                    <Button size="sm" variant="light" icon="edit" @click="open(r)">{{ $t('common.edit') }}</Button>
                </div>
                <ul v-if="children(r.id).length" class="divide-y divide-[var(--border)] border-t border-[var(--border)]">
                    <li v-for="c in children(r.id)" :key="c.id" class="flex items-center gap-3 px-5 py-3 ps-16 text-sm">
                        <span class="flex-1">{{ c.name[locale] }} <span class="text-xs text-gray-400" dir="ltr">{{ c.slug }}</span></span>
                        <Badge tone="gray">{{ $t(`urgency.${c.default_urgency}`) }}</Badge>
                        <button type="button" class="text-navy-700 hover:underline" @click="open(c)">{{ $t('common.edit') }}</button>
                    </li>
                </ul>
            </Card>
        </div>
        <Modal :show="!!editing" :title="editing?.id ? $t('common.edit') : $t('categories.add')" width="max-w-2xl" @close="editing = null">
            <form id="cat-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="save">
                <Field v-model="form.name.fa" :label="$t('kpi.name_fa')" required :error="form.errors['name.fa']" />
                <Field v-model="form.name.en" :label="$t('kpi.name_en')" dir="ltr" required :error="form.errors['name.en']" />
                <Field v-model="form.slug" label="Slug" dir="ltr" required :error="form.errors.slug" />
                <Field v-model="form.parent_id" as="select" :label="$t('categories.parent')" :options="roots.map((r) => ({ value: r.id, label: r.name[locale] }))" :placeholder="$t('categories.root')" />
                <Field v-model="form.default_urgency" as="select" :label="$t('categories.default_urgency')" :options="urgencies.map((u) => ({ value: u, label: $t(`urgency.${u}`) }))" />
                <Field v-model="form.icon" :label="$t('categories.icon')" dir="ltr" />
                <Field v-model="form.keywords_fa" as="textarea" :rows="3" class="sm:col-span-2" :label="$t('categories.keywords_fa')" :hint="$t('categories.keywords_hint')" />
                <Field v-model="form.keywords_en" as="textarea" :rows="3" class="sm:col-span-2" dir="ltr" :label="$t('categories.keywords_en')" />
                <Checkbox v-model="form.is_sensitive" :label="$t('categories.sensitive')" :description="$t('categories.sensitive_hint')" />
                <Checkbox v-model="form.is_active" :label="$t('kpi.active')" />
            </form>
            <template #footer><Button type="submit" form="cat-form" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
