<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
defineProps({ status: String });
const { t } = useI18n();
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login.store'), { onFinish: () => form.reset('password') });
</script>

<template>
    <AuthLayout :title="$t('auth.login_title')" :subtitle="$t('auth.login_subtitle')">
        <SeoHead :title="t('auth.login_title')" />
        <p v-if="status" class="mb-4 rounded-2xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ status }}</p>
        <form class="space-y-4" @submit.prevent="submit">
            <Field v-model="form.email" :label="$t('fields.email')" type="email" autocomplete="username" dir="ltr" required :error="form.errors.email" />
            <Field v-model="form.password" :label="$t('fields.password')" type="password" autocomplete="current-password" dir="ltr" required :error="form.errors.password" />
            <div class="flex items-center justify-between">
                <Checkbox v-model="form.remember" :label="$t('auth.remember')" />
                <Link :href="route('password.request')" class="text-sm text-navy-700 hover:underline">{{ $t('auth.forgot') }}</Link>
            </div>
            <Button type="submit" block size="lg" :loading="form.processing" icon="arrow">{{ $t('auth.login') }}</Button>
        </form>
        <div class="my-6 flex items-center gap-3 text-xs text-gray-400"><span class="h-px flex-1 bg-gray-200" />{{ $t('common.or') }}<span class="h-px flex-1 bg-gray-200" /></div>
        <Button :href="route('login.code')" variant="light" block size="lg" icon="mail">{{ $t('auth.login_with_code') }}</Button>
        <p class="mt-8 text-center text-sm text-gray-500">{{ $t('auth.no_account') }} <Link :href="route('register')" class="font-medium text-navy-700 hover:underline">{{ $t('auth.register') }}</Link></p>
    </AuthLayout>
</template>
