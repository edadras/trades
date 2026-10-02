<script setup>
import { router, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
defineProps({ status: String });
const { t } = useI18n();
const form = useForm({});
</script>

<template>
    <AuthLayout :title="$t('auth.verify_title')" :subtitle="$t('auth.verify_subtitle')">
        <SeoHead :title="t('auth.verify_title')" />
        <p v-if="status === 'verification-link-sent'" class="mb-4 rounded-2xl bg-emerald-50 p-3 text-sm text-emerald-700">{{ $t('auth.verify_sent') }}</p>
        <div class="flex flex-wrap gap-3">
            <Button :loading="form.processing" icon="send" @click="form.post(route('verification.send'))">{{ $t('auth.verify_resend') }}</Button>
            <Button variant="ghost" no-icon @click="router.post(route('logout'))">{{ $t('nav.logout') }}</Button>
        </div>
    </AuthLayout>
</template>
