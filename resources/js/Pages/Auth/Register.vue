<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Field from '@/Components/ui/Field.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import { route, useI18n } from '@/i18n';
const props = defineProps({ type: String });
const { t } = useI18n();
const form = useForm({ account_type: props.type === 'supporter' ? 'supporter' : 'business', name: '', company_name: '', email: '', password: '', password_confirmation: '', terms: false, marketing: false });
const submit = () => form.post(route('register.store'), { onFinish: () => form.reset('password', 'password_confirmation') });
</script>

<template>
    <AuthLayout :title="$t('auth.register_title')" :subtitle="$t('auth.register_subtitle')">
        <SeoHead :title="t('auth.register_title')" />
        <div class="mb-6 grid grid-cols-2 gap-2 rounded-3xl bg-[var(--surface-muted)] p-1.5 ring-1 ring-[var(--border)]">
            <button v-for="opt in [['business', 'briefcase'], ['supporter', 'users']]" :key="opt[0]" type="button" class="flex flex-col items-start gap-2 rounded-[20px] p-4 text-start transition" :class="form.account_type === opt[0] ? 'bg-white shadow ring-1 ring-navy-200' : 'hover:bg-white/60'" @click="form.account_type = opt[0]">
                <Icon :name="opt[1]" :class="form.account_type === opt[0] ? 'text-navy-800' : 'text-gray-400'" />
                <span class="text-sm font-semibold">{{ $t(`auth.type_${opt[0]}`) }}</span>
                <span class="text-xs text-gray-500">{{ $t(`auth.type_${opt[0]}_hint`) }}</span>
            </button>
        </div>
        <form class="space-y-4" @submit.prevent="submit">
            <Field v-model="form.name" :label="$t('fields.full_name')" autocomplete="name" required :error="form.errors.name" />
            <Field v-if="form.account_type === 'business'" v-model="form.company_name" :label="$t('fields.company_name')" required :error="form.errors.company_name" />
            <Field v-model="form.email" :label="$t('fields.email')" type="email" autocomplete="email" dir="ltr" required :error="form.errors.email" />
            <div class="grid gap-4 sm:grid-cols-2">
                <Field v-model="form.password" :label="$t('fields.password')" type="password" autocomplete="new-password" dir="ltr" required :error="form.errors.password" :hint="$t('auth.password_hint')" />
                <Field v-model="form.password_confirmation" :label="$t('fields.password_confirmation')" type="password" autocomplete="new-password" dir="ltr" required />
            </div>
            <Checkbox v-model="form.terms" :error="form.errors.terms">
                <span>{{ $t('auth.accept_terms_prefix') }} <Link :href="route('terms')" class="text-navy-700 underline">{{ $t('footer.terms') }}</Link> {{ $t('common.and') }} <Link :href="route('privacy')" class="text-navy-700 underline">{{ $t('footer.privacy') }}</Link></span>
            </Checkbox>
            <Checkbox v-model="form.marketing" :label="$t('auth.marketing')" />
            <Button type="submit" block size="lg" :loading="form.processing" icon="arrow">{{ $t('auth.create_account') }}</Button>
        </form>
        <p class="mt-8 text-center text-sm text-gray-500">{{ $t('auth.have_account') }} <Link :href="route('login')" class="font-medium text-navy-700 hover:underline">{{ $t('auth.login') }}</Link></p>
    </AuthLayout>
</template>
