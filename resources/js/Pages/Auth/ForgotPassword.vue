<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
defineProps({ status: String });
const { t } = useI18n();
const form = useForm({ email: '' });
</script>

<template>
    <AuthLayout :title="$t('auth.forgot_title')" :subtitle="$t('auth.forgot_subtitle')">
        <SeoHead :title="t('auth.forgot_title')" />
        <p v-if="status" class="mb-4 rounded-2xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="form.post(route('password.email'))">
            <Field v-model="form.email" :label="$t('fields.email')" type="email" dir="ltr" required :error="form.errors.email" />
            <Button type="submit" block size="lg" :loading="form.processing" icon="send">{{ $t('auth.send_reset') }}</Button>
        </form>
    </AuthLayout>
</template>
