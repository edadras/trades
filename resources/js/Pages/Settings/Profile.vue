<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import SettingsNav from './SettingsNav.vue';
import { route } from '@/i18n';
const props = defineProps({ profile: Object });
const form = useForm({ ...props.profile });
const password = useForm({ current_password: '', password: '', password_confirmation: '' });
</script>

<template>
    <AppLayout :title="$t('nav.settings')">
        <SettingsNav current="profile" />
        <div class="grid gap-6 lg:grid-cols-2">
            <Card :title="$t('settings.profile')">
                <form class="space-y-4" @submit.prevent="form.put(route('user-profile-information.update'), { preserveScroll: true, errorBag: 'updateProfileInformation' })">
                    <Field v-model="form.name" :label="$t('fields.full_name')" required :error="form.errors.name" />
                    <Field v-model="form.email" :label="$t('fields.email')" type="email" dir="ltr" required :error="form.errors.email" />
                    <Field v-model="form.phone" :label="$t('fields.phone')" dir="ltr" :error="form.errors.phone" :hint="$t('settings.phone_hint')" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Field v-model="form.locale" as="select" :label="$t('fields.preferred_language')" :options="$page.props.options.languages" />
                        <Field v-model="form.timezone" as="select" :label="$t('settings.timezone')" :options="['Asia/Tehran', 'Asia/Dubai', 'Europe/Istanbul', 'Europe/Berlin', 'Europe/London', 'America/Toronto', 'UTC'].map((z) => ({ value: z, label: z }))" />
                    </div>
                    <Button type="submit" icon="check" :loading="form.processing">{{ $t('common.save') }}</Button>
                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-700">{{ $t('settings.saved') }}</p>
                </form>
            </Card>
            <Card :title="$t('settings.password')">
                <form class="space-y-4" @submit.prevent="password.put(route('user-password.update'), { preserveScroll: true, errorBag: 'updatePassword', onSuccess: () => password.reset() })">
                    <Field v-model="password.current_password" :label="$t('settings.current_password')" type="password" dir="ltr" :error="password.errors.current_password" />
                    <Field v-model="password.password" :label="$t('fields.password')" type="password" dir="ltr" :error="password.errors.password" :hint="$t('auth.password_hint')" />
                    <Field v-model="password.password_confirmation" :label="$t('fields.password_confirmation')" type="password" dir="ltr" />
                    <Button type="submit" icon="key" :loading="password.processing">{{ $t('settings.update_password') }}</Button>
                    <p v-if="password.recentlySuccessful" class="text-sm text-emerald-700">{{ $t('settings.saved') }}</p>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>
