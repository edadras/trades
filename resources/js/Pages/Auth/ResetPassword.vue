<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ token: String, email: String });
const { t } = useI18n();
const form = useForm({ token: props.token, email: props.email ?? '', password: '', password_confirmation: '' });
</script>

<template>
    <AuthLayout :title="$t('auth.reset_title')">
        <SeoHead :title="t('auth.reset_title')" />
        <form class="space-y-4" @submit.prevent="form.post(route('password.update'))">
            <Field v-model="form.email" :label="$t('fields.email')" type="email" dir="ltr" required :error="form.errors.email" />
            <Field v-model="form.password" :label="$t('fields.password')" type="password" dir="ltr" required :error="form.errors.password" :hint="$t('auth.password_hint')" />
            <Field v-model="form.password_confirmation" :label="$t('fields.password_confirmation')" type="password" dir="ltr" required />
            <Button type="submit" block size="lg" :loading="form.processing" icon="key">{{ $t('auth.reset') }}</Button>
        </form>
    </AuthLayout>
</template>
