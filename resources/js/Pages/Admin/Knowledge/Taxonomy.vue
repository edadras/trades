<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Field from '@/Components/ui/Field.vue';
import Modal from '@/Components/ui/Modal.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';

defineProps({ categories: Array, sources: Array });
const { t, locale } = useI18n();

const sourceTypes = ['internal', 'government', 'academic', 'industry', 'international', 'other'];
const reliabilityLevels = ['high', 'medium', 'low'];
const reliabilityTone = { high: 'green', medium: 'amber', low: 'red' };
const sourceTypeOptions = sourceTypes.map((type) => ({ value: type, label: t(`taxonomy.source_types.${type}`) }));
const reliabilityOptions = reliabilityLevels.map((level) => ({ value: level, label: t(`taxonomy.reliability_levels.${level}`) }));
const isHexColor = (value) => /^#[0-9a-fA-F]{6}$/.test(value ?? '');

const blankCategory = () => ({ slug: '', name: { fa: '', en: '' }, icon: '', color: '', sort_order: 0 });
const editingCategory = ref(null);
const categoryForm = useForm(blankCategory());
function openCategory(category) {
    editingCategory.value = category ?? {};
    categoryForm.clearErrors();
    Object.assign(categoryForm, category
        ? { slug: category.slug, name: { fa: category.name.fa ?? '', en: category.name.en ?? '' }, icon: category.icon ?? '', color: category.color ?? '', sort_order: category.sort_order ?? 0 }
        : blankCategory());
}
function saveCategory() {
    const payload = categoryForm.transform((data) => ({ ...data, icon: data.icon || null, color: data.color || null, sort_order: Number(data.sort_order) || 0 }));
    const options = { preserveScroll: true, onSuccess: () => (editingCategory.value = null) };
    editingCategory.value?.id
        ? payload.put(route('admin.taxonomy.categories.update', { category: editingCategory.value.slug }), options)
        : payload.post(route('admin.taxonomy.categories.store'), options);
}

const blankSource = () => ({ name: '', publisher: '', url: '', type: 'internal', reliability: 'high' });
const editingSource = ref(null);
const sourceForm = useForm(blankSource());
function openSource(source) {
    editingSource.value = source ?? {};
    sourceForm.clearErrors();
    Object.assign(sourceForm, source
        ? { name: source.name, publisher: source.publisher ?? '', url: source.url ?? '', type: source.type, reliability: source.reliability }
        : blankSource());
}
function saveSource() {
    const payload = sourceForm.transform((data) => ({ ...data, publisher: data.publisher || null, url: data.url || null }));
    const options = { preserveScroll: true, onSuccess: () => (editingSource.value = null) };
    editingSource.value?.id
        ? payload.put(route('admin.taxonomy.sources.update', { source: editingSource.value.id }), options)
        : payload.post(route('admin.taxonomy.sources.store'), options);
}
</script>

<template>
    <AppLayout :title="$t('nav.knowledge_taxonomy')" :subtitle="$t('taxonomy.subtitle')" wide>
        <div class="grid gap-6 xl:grid-cols-2">
            <Card :title="$t('taxonomy.categories')" :subtitle="$t('taxonomy.categories_hint')" :padded="false">
                <template #actions><Button size="sm" icon="plus" @click="openCategory(null)">{{ $t('taxonomy.add_category') }}</Button></template>
                <div v-if="!categories.length" class="p-5 sm:p-6"><EmptyState icon="layers" :title="$t('taxonomy.no_categories')" /></div>
                <ul v-else class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                    <li v-for="c in categories" :key="c.id" class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                        <span class="grid size-10 shrink-0 place-items-center rounded-2xl text-white" :class="isHexColor(c.color) ? '' : 'bg-navy-950'" :style="isHexColor(c.color) ? { backgroundColor: c.color } : {}">
                            <Icon :name="c.icon || 'book'" :size="18" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-ink">{{ c.name[locale] || c.name.fa || c.name.en }}</p>
                            <p class="truncate text-xs text-gray-500"><span dir="ltr">{{ c.slug }}</span> · {{ $t('taxonomy.sort_order') }} {{ c.sort_order }}</p>
                        </div>
                        <Badge tone="navy" class="hidden sm:inline-flex">{{ $t('taxonomy.articles_count', { count: c.articles_count ?? 0 }) }}</Badge>
                        <Button size="sm" variant="light" icon="edit" icon-only :aria-label="$t('common.edit')" @click="openCategory(c)" />
                    </li>
                </ul>
            </Card>

            <Card :title="$t('taxonomy.sources')" :subtitle="$t('taxonomy.sources_hint')" :padded="false">
                <template #actions><Button size="sm" icon="plus" @click="openSource(null)">{{ $t('taxonomy.add_source') }}</Button></template>
                <div v-if="!sources.length" class="p-5 sm:p-6"><EmptyState icon="book" :title="$t('taxonomy.no_sources')" /></div>
                <ul v-else class="mt-4 divide-y divide-[var(--border)] border-t border-[var(--border)]">
                    <li v-for="s in sources" :key="s.id" class="flex items-start gap-3 px-5 py-3.5 sm:px-6">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-ink">{{ s.name }}</p>
                            <p v-if="s.publisher" class="text-xs text-gray-500">{{ s.publisher }}</p>
                            <a v-if="s.url" :href="s.url" target="_blank" rel="noopener noreferrer" class="mt-0.5 block truncate text-xs text-navy-700 hover:underline" dir="ltr">{{ s.url }}</a>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <Badge tone="gray">{{ $t(`taxonomy.source_types.${s.type}`) }}</Badge>
                                <Badge :tone="reliabilityTone[s.reliability] ?? 'gray'" dot>{{ $t('taxonomy.reliability') }}: {{ $t(`taxonomy.reliability_levels.${s.reliability}`) }}</Badge>
                            </div>
                        </div>
                        <Button size="sm" variant="light" icon="edit" icon-only :aria-label="$t('common.edit')" @click="openSource(s)" />
                    </li>
                </ul>
            </Card>
        </div>

        <Modal :show="!!editingCategory" :title="editingCategory?.id ? $t('taxonomy.edit_category') : $t('taxonomy.add_category')" width="max-w-2xl" @close="editingCategory = null">
            <form id="category-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveCategory">
                <Field v-model="categoryForm.name.fa" :label="$t('taxonomy.name_fa')" required :error="categoryForm.errors['name.fa']" />
                <Field v-model="categoryForm.name.en" :label="$t('taxonomy.name_en')" dir="ltr" required :error="categoryForm.errors['name.en']" />
                <Field v-model="categoryForm.slug" :label="$t('taxonomy.slug')" :hint="$t('taxonomy.slug_hint')" dir="ltr" required :error="categoryForm.errors.slug" />
                <Field v-model="categoryForm.sort_order" type="number" min="0" dir="ltr" :label="$t('taxonomy.sort_order')" :error="categoryForm.errors.sort_order" />
                <Field v-model="categoryForm.icon" :label="$t('taxonomy.icon')" :hint="$t('taxonomy.icon_hint')" dir="ltr" :error="categoryForm.errors.icon" />
                <div class="flex items-start gap-3">
                    <Field v-model="categoryForm.color" class="flex-1" :label="$t('taxonomy.color')" :hint="$t('taxonomy.color_hint')" dir="ltr" placeholder="#1E3A8A" :error="categoryForm.errors.color" />
                    <span class="mt-7 grid size-11 shrink-0 place-items-center rounded-2xl text-white ring-1 ring-[var(--border)]" :class="isHexColor(categoryForm.color) ? '' : 'bg-navy-950'" :style="isHexColor(categoryForm.color) ? { backgroundColor: categoryForm.color } : {}">
                        <Icon :name="categoryForm.icon || 'book'" :size="18" />
                    </span>
                </div>
            </form>
            <template #footer><Button type="submit" form="category-form" icon="check" :loading="categoryForm.processing">{{ $t('common.save') }}</Button></template>
        </Modal>

        <Modal :show="!!editingSource" :title="editingSource?.id ? $t('taxonomy.edit_source') : $t('taxonomy.add_source')" width="max-w-2xl" @close="editingSource = null">
            <form id="source-form" class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveSource">
                <Field v-model="sourceForm.name" class="sm:col-span-2" :label="$t('taxonomy.source_name')" required :error="sourceForm.errors.name" />
                <Field v-model="sourceForm.publisher" :label="$t('taxonomy.publisher')" :error="sourceForm.errors.publisher" />
                <Field v-model="sourceForm.url" type="url" :label="$t('taxonomy.url')" dir="ltr" placeholder="https://" :error="sourceForm.errors.url" />
                <Field v-model="sourceForm.type" as="select" :label="$t('taxonomy.source_type')" :options="sourceTypeOptions" required :error="sourceForm.errors.type" />
                <Field v-model="sourceForm.reliability" as="select" :label="$t('taxonomy.reliability')" :options="reliabilityOptions" required :error="sourceForm.errors.reliability" />
            </form>
            <template #footer><Button type="submit" form="source-form" icon="check" :loading="sourceForm.processing">{{ $t('common.save') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
