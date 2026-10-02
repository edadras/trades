<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ email: String });
const { t } = useI18n();
const send = useForm({ email: props.email ?? '' });
const verify = useForm({ email: props.email ?? '', code: '' });
</script>

<template>
    <AuthLayout :title="$t('auth.code_title')" :subtitle="$t('auth.code_subtitle')">
        <SeoHead :title="t('auth.code_title')" />
        <form v-if="!email" class="space-y-4" @submit.prevent="send.post(route('login.code.send'))">
            <Field v-model="send.email" :label="$t('fields.email')" type="email" dir="ltr" required :error="send.errors.email" />
            <Button type="submit" block size="lg" :loading="send.processing" icon="send">{{ $t('auth.send_code') }}</Button>
        </form>
        <form v-else class="space-y-4" @submit.prevent="verify.post(route('login.code.verify'))">
            <p class="rounded-2xl bg-navy-50 p-3 text-sm text-navy-900" dir="ltr">{{ email }}</p>
            <Field v-model="verify.code" :label="$t('auth.code')" inputmode="numeric" autocomplete="one-time-code" dir="ltr" required :error="verify.errors.code" />
            <Button type="submit" block size="lg" :loading="verify.processing" icon="key">{{ $t('auth.verify_code') }}</Button>
            <button type="button" class="w-full text-center text-sm text-navy-700 hover:underline" @click="send.email = email; send.post(route('login.code.send'))">{{ $t('auth.resend_code') }}</button>
        </form>
    </AuthLayout>
</template>
