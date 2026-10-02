<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
const { t } = useI18n();
const recovery = ref(false);
const form = useForm({ code: '', recovery_code: '' });
</script>

<template>
    <AuthLayout :title="$t('auth.two_factor_title')" :subtitle="recovery ? $t('auth.two_factor_recovery_hint') : $t('auth.two_factor_hint')">
        <SeoHead :title="t('auth.two_factor_title')" />
        <form class="space-y-4" @submit.prevent="form.post(route('two-factor.login.store'))">
            <Field v-if="!recovery" v-model="form.code" :label="$t('auth.code')" inputmode="numeric" autocomplete="one-time-code" dir="ltr" :error="form.errors.code" />
            <Field v-else v-model="form.recovery_code" :label="$t('auth.recovery_code')" dir="ltr" :error="form.errors.recovery_code" />
            <Button type="submit" block size="lg" :loading="form.processing" icon="shield-check">{{ $t('auth.verify_code') }}</Button>
            <button type="button" class="w-full text-center text-sm text-navy-700 hover:underline" @click="recovery = !recovery">{{ recovery ? $t('auth.use_code') : $t('auth.use_recovery') }}</button>
        </form>
    </AuthLayout>
</template>
