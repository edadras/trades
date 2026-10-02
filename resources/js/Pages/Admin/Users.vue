<script setup>
import { reactive, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import DataTable from '@/Components/ui/DataTable.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import Badge from '@/Components/ui/Badge.vue';
import Button from '@/Components/ui/Button.vue';
import Modal from '@/Components/ui/Modal.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ users: Object, filters: Object, roles: Array });
const { t, relative } = useI18n();
const f = reactive({ q: props.filters.q ?? '', role: props.filters.role ?? '' });
let timer;
watch(f, () => { clearTimeout(timer); timer = setTimeout(() => router.get(route('admin.users.index'), Object.fromEntries(Object.entries(f).filter(([, v]) => v)), { preserveState: true, replace: true }), 300); });
const editing = ref(null);
const form = useForm({ roles: [], status: 'active' });
const open = (u) => { editing.value = u; form.roles = [...u.roles]; form.status = u.status; };
const save = () => form.put(route('admin.users.update', { user: editing.value.id }), { preserveScroll: true, onSuccess: () => (editing.value = null) });
const columns = [{ key: 'name', label: t('table.name') }, { key: 'roles', label: t('table.roles') }, { key: 'status', label: t('table.status') }, { key: 'last', label: t('table.last_login') }, { key: 'actions', label: '' }];
</script>

<template>
    <AppLayout :title="$t('nav.users')" wide>
        <Card class="mb-6"><div class="grid gap-3 sm:grid-cols-2">
            <input v-model="f.q" type="search" class="input" :placeholder="$t('table.search')" />
            <select v-model="f.role" class="input"><option value="">{{ $t('table.any_role') }}</option><option v-for="r in roles" :key="r" :value="r">{{ $t(`roles.${r}`) }}</option></select>
        </div></Card>
        <Card :padded="false"><div class="p-2 sm:p-4">
            <DataTable :columns="columns" :rows="users.data">
                <template #cell-name="{ row }"><div><p class="font-medium">{{ row.name }}</p><p class="text-xs text-gray-500" dir="ltr">{{ row.email }}</p></div></template>
                <template #cell-roles="{ row }"><span class="flex flex-wrap gap-1"><Badge v-for="r in row.roles" :key="r" tone="navy">{{ $t(`roles.${r}`) }}</Badge></span></template>
                <template #cell-status="{ row }"><span class="flex gap-1"><Badge :tone="row.status === 'active' ? 'green' : 'red'">{{ $t(`user_status.${row.status}`) }}</Badge><Badge v-if="row.two_factor" tone="sky"><Icon name="lock" :size="11" />2FA</Badge></span></template>
                <template #cell-last="{ row }"><span class="text-xs text-gray-500">{{ row.last_login_at ? relative(row.last_login_at) : '—' }}</span></template>
                <template #cell-actions="{ row }"><Button size="sm" variant="light" icon="key" @click="open(row)">{{ $t('users.roles') }}</Button></template>
            </DataTable>
        </div></Card>
        <Pagination :meta="users" />
        <Modal :show="!!editing" :title="editing?.name" @close="editing = null">
            <div class="grid gap-1 sm:grid-cols-2"><Checkbox v-for="r in roles" :key="r" v-model="form.roles" :value="r" :label="$t(`roles.${r}`)" /></div>
            <Field v-model="form.status" as="select" class="mt-4" :label="$t('table.status')" :options="[{ value: 'active', label: $t('user_status.active') }, { value: 'suspended', label: $t('user_status.suspended') }]" />
            <p v-if="Object.keys(form.errors).length" class="mt-2 text-sm text-rose-600">{{ Object.values(form.errors)[0] }}</p>
            <template #footer><Button icon="check" :loading="form.processing" @click="save">{{ $t('common.save') }}</Button></template>
        </Modal>
    </AppLayout>
</template>
