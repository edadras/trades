<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
const { t } = useI18n();
const form = useForm({ password: '' });
</script>

<template>
    <AuthLayout :title="$t('auth.confirm_title')" :subtitle="$t('auth.confirm_subtitle')">
        <SeoHead :title="t('auth.confirm_title')" />
        <form class="space-y-4" @submit.prevent="form.post(route('password.confirm.store'), { onFinish: () => form.reset() })">
            <Field v-model="form.password" :label="$t('fields.password')" type="password" dir="ltr" required :error="form.errors.password" />
            <Button type="submit" block size="lg" :loading="form.processing" icon="lock">{{ $t('common.confirm') }}</Button>
        </form>
    </AuthLayout>
</template>
