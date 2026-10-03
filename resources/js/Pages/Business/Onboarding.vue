<script setup>
// 9-step registration wizard: basics → company → industry → size → region → contact → needs → documents → privacy.
import { computed, ref } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import SeoHead from '@/Components/ui/SeoHead.vue';
import Logo from '@/Components/ui/Logo.vue';
import Field from '@/Components/ui/Field.vue';
import Button from '@/Components/ui/Button.vue';
import ChoiceChips from '@/Components/ui/ChoiceChips.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import Uploader from '@/Components/ui/Uploader.vue';
import Icon from '@/Components/ui/Icon.vue';
import LocaleSwitch from '@/Components/ui/LocaleSwitch.vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Drawer from '@/Components/ui/Drawer.vue';
import MobileTabBar from '@/Components/ui/MobileTabBar.vue';
import { route, useI18n } from '@/i18n';

const props = defineProps({ business: Object, step: Number, steps: Number });
const page = usePage();
const { t, number } = useI18n();
const opts = computed(() => page.props.options);
const keys = ['basics', 'company', 'industry', 'size', 'region', 'contact', 'needs', 'documents', 'privacy'];
const current = computed(() => Math.min(Math.max(props.step, 1), props.steps));
const b = props.business;

const form = useForm({
    step: current.value,
    trade_name: b.trade_name ?? '', legal_name: b.legal_name ?? '',
    registration_number: b.registration_number ?? '', founded_year: b.founded_year ?? '', website: b.website ?? '', description: b.description ?? '', products_services: b.products_services ?? '',
    industry: b.industry ?? '',
    size: b.size ?? '', employees_range: b.employees_range ?? '',
    country: b.country ?? 'IR', province: b.province ?? '', city: b.city ?? '', address: b.address ?? '',
    contact_name: b.contact_name ?? '', contact_email: b.contact_email ?? '', contact_phone: b.contact_phone ?? '', preferred_language: b.preferred_language ?? 'fa',
    main_needs: b.main_needs ?? [],
    documents: [], document_type: 'registration',
    privacy: { ...b.privacy },
    consent_data_processing: false, consent_ai_processing: false,
});
const submit = () => {
    form.step = current.value;
    form.post(route('onboarding.store'), { forceFormData: current.value === 8, preserveScroll: true, onSuccess: () => form.reset('documents') });
};
const levels = ['private', 'case_team', 'verified_experts', 'public'];
const menu = ref(false);
const logout = () => router.post(route('logout'));
// Until onboarding is finished the business panel is not available, so the mobile bar offers what is.
const tabs = computed(() => [
    { label: t('onboarding.title'), short: t('nav.dashboard'), icon: 'briefcase', href: route('onboarding.show'), match: 'onboarding.show' },
    { label: t('nav.support'), icon: 'info', href: route('support.index'), match: 'support.index' },
    { label: t('nav.settings'), icon: 'settings', href: route('settings.profile'), match: 'settings.*' },
    { label: t('nav.home'), icon: 'home', href: route('home'), match: 'home' },
]);
const privacyFields = Object.keys(b.privacy);
</script>

<template>
    <div class="min-h-screen bg-[var(--surface-muted)]">
        <SeoHead :title="$t('onboarding.title')" />
        <header class="flex items-center justify-between gap-2 px-4 py-4 sm:px-10 sm:py-5">
            <Logo />
            <div class="flex items-center gap-1.5">
                <LocaleSwitch />
                <button type="button" class="grid size-11 place-items-center rounded-full bg-white text-ink ring-1 ring-[var(--border)] lg:hidden" :aria-label="$t('nav.menu')" :aria-expanded="menu" @click="menu = true"><Icon name="menu" /></button>
            </div>
        </header>
        <div class="mx-auto grid grid-cols-1 max-w-6xl gap-8 px-4 pb-32 sm:px-10 lg:pb-16 lg:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="lg:sticky lg:top-6 lg:self-start">
                <p class="text-sm text-gray-500">{{ $t('onboarding.progress', { n: number(current), total: number(steps) }) }}</p>
                <div class="mt-2 h-2 rounded-full bg-navy-100"><div class="h-full rounded-full bg-navy-600 transition-all duration-500" :style="{ width: `${(current / steps) * 100}%` }" /></div>
                <ol class="mt-6 hidden space-y-1 lg:block">
                    <li v-for="(k, i) in keys" :key="k">
                        <Link :href="i + 1 <= business.onboarding_step ? route('onboarding.show', { step: i + 1 }) : '#'" class="flex items-center gap-3 rounded-2xl px-3 py-2 text-sm" :class="i + 1 === current ? 'bg-white font-semibold text-ink shadow-sm ring-1 ring-[var(--border)]' : i + 1 < business.onboarding_step ? 'text-gray-700 hover:bg-white' : 'pointer-events-none text-gray-400'">
                            <span class="grid size-6 place-items-center rounded-full text-[11px]" :class="i + 1 < business.onboarding_step ? 'bg-navy-950 text-white' : 'bg-gray-200'"><Icon v-if="i + 1 < business.onboarding_step" name="check" :size="12" /><template v-else>{{ number(i + 1) }}</template></span>
                            {{ $t(`onboarding.steps.${k}`) }}
                        </Link>
                    </li>
                </ol>
            </aside>

            <main class="card p-6 sm:p-10">
                <p class="text-sm font-medium text-navy-600">{{ $t('onboarding.step_label', { n: number(current) }) }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight text-ink sm:text-3xl">{{ $t(`onboarding.steps.${keys[current - 1]}`) }}</h1>
                <p class="mt-2 text-gray-500">{{ $t(`onboarding.hints.${keys[current - 1]}`) }}</p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <template v-if="current === 1">
                        <Field v-model="form.trade_name" :label="$t('fields.trade_name')" required :error="form.errors.trade_name" />
                        <Field v-model="form.legal_name" :label="$t('fields.legal_name')" :error="form.errors.legal_name" />
                    </template>
                    <template v-else-if="current === 2">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field v-model="form.registration_number" :label="$t('fields.registration_number')" dir="ltr" :error="form.errors.registration_number" :hint="$t('onboarding.encrypted')" />
                            <Field v-model="form.founded_year" :label="$t('fields.founded_year')" type="number" dir="ltr" :error="form.errors.founded_year" />
                        </div>
                        <Field v-model="form.website" :label="$t('fields.website')" type="url" dir="ltr" placeholder="https://" :error="form.errors.website" />
                        <Field v-model="form.description" as="textarea" :label="$t('fields.description')" :error="form.errors.description" />
                        <Field v-model="form.products_services" as="textarea" :rows="3" :label="$t('fields.products_services')" :error="form.errors.products_services" />
                    </template>
                    <template v-else-if="current === 3">
                        <ChoiceChips v-model="form.industry" :options="opts.industries" />
                        <p v-if="form.errors.industry" class="text-sm text-rose-600">{{ form.errors.industry }}</p>
                    </template>
                    <template v-else-if="current === 4">
                        <div><p class="label">{{ $t('fields.size') }}</p><ChoiceChips v-model="form.size" :options="opts.sizes" /><p v-if="form.errors.size" class="mt-1 text-sm text-rose-600">{{ form.errors.size }}</p></div>
                        <div><p class="label">{{ $t('fields.employees_range') }}</p><ChoiceChips v-model="form.employees_range" :options="opts.employee_ranges" /><p v-if="form.errors.employees_range" class="mt-1 text-sm text-rose-600">{{ form.errors.employees_range }}</p></div>
                    </template>
                    <template v-else-if="current === 5">
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field v-model="form.country" as="select" :options="opts.countries" :label="$t('fields.country')" required :error="form.errors.country" />
                            <Field v-if="form.country === 'IR'" v-model="form.province" as="select" :options="opts.provinces" :label="$t('fields.province')" :error="form.errors.province" />
                            <Field v-else v-model="form.province" :label="$t('fields.province')" :error="form.errors.province" />
                        </div>
                        <Field v-model="form.city" :label="$t('fields.city')" :error="form.errors.city" />
                        <Field v-model="form.address" as="textarea" :rows="2" :label="$t('fields.address')" :error="form.errors.address" :hint="$t('onboarding.encrypted')" />
                    </template>
                    <template v-else-if="current === 6">
                        <Field v-model="form.contact_name" :label="$t('fields.contact_name')" required :error="form.errors.contact_name" />
                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <Field v-model="form.contact_email" :label="$t('fields.contact_email')" type="email" dir="ltr" required :error="form.errors.contact_email" />
                            <Field v-model="form.contact_phone" :label="$t('fields.contact_phone')" type="tel" dir="ltr" required :error="form.errors.contact_phone" />
                        </div>
                        <div><p class="label">{{ $t('fields.preferred_language') }}</p><ChoiceChips v-model="form.preferred_language" :options="opts.languages" /></div>
                    </template>
                    <template v-else-if="current === 7">
                        <ChoiceChips v-model="form.main_needs" :options="opts.needs" multiple />
                        <p v-if="form.errors.main_needs" class="text-sm text-rose-600">{{ form.errors.main_needs }}</p>
                    </template>
                    <template v-else-if="current === 8">
                        <Field v-model="form.document_type" as="select" :label="$t('fields.document_type')" :options="['registration', 'license', 'financial', 'other'].map((v) => ({ value: v, label: $t(`doc_types.${v}`) }))" />
                        <Uploader v-model="form.documents" :error="form.errors.documents || form.errors['documents.0']" />
                        <ul v-if="business.documents.length" class="space-y-2">
                            <li v-for="d in business.documents" :key="d.id" class="flex items-center gap-2 text-sm text-gray-600"><Icon name="file" :size="16" />{{ d.name }}</li>
                        </ul>
                        <p class="text-sm text-gray-500">{{ $t('onboarding.documents_optional') }}</p>
                    </template>
                    <template v-else-if="current === 9">
                        <div class="overflow-hidden rounded-3xl ring-1 ring-[var(--border)]">
                            <div v-for="field in privacyFields" :key="field" class="flex flex-col gap-3 border-b border-[var(--border)] p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                                <span class="text-sm font-medium text-ink">{{ $t(`fields.${field}`) }}</span>
                                <select v-model="form.privacy[field]" class="input sm:w-64">
                                    <option v-for="l in levels" :key="l" :value="l">{{ $t(`privacy_levels.${l}`) }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="text-xs leading-6 text-gray-500">{{ $t('onboarding.privacy_note') }}</p>
                        <Checkbox v-model="form.consent_data_processing" :label="$t('onboarding.consent_data')" :error="form.errors.consent_data_processing" />
                        <Checkbox v-model="form.consent_ai_processing" :label="$t('onboarding.consent_ai')" :description="$t('onboarding.consent_ai_hint')" :error="form.errors.consent_ai_processing" />
                    </template>

                    <div class="flex items-center justify-between gap-3 border-t border-[var(--border)] pt-6">
                        <Button v-if="current > 1" :href="route('onboarding.show', { step: current - 1 })" variant="ghost" icon="arrow-left">{{ $t('common.back') }}</Button>
                        <span v-else />
                        <Button type="submit" :loading="form.processing" :icon="current === steps ? 'check' : 'arrow'">{{ current === steps ? $t('onboarding.finish') : $t('common.continue') }}</Button>
                    </div>
                </form>
            </main>
        </div>
        <Drawer :show="menu" @close="menu = false">
            <template #header><Logo /></template>
            <div class="space-y-6 p-4">
                <div>
                    <p class="mb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $t('onboarding.title') }}</p>
                    <component :is="i + 1 <= business.onboarding_step ? Link : 'span'" v-for="(k, i) in keys" :key="k" :href="i + 1 <= business.onboarding_step ? route('onboarding.show', { step: i + 1 }) : undefined" class="flex items-center gap-3 rounded-2xl px-3 py-3 text-[15px]" :class="i + 1 === current ? 'bg-navy-950 text-white' : i + 1 <= business.onboarding_step ? 'hover:bg-navy-50' : 'text-gray-400'">
                        <span class="grid size-6 place-items-center rounded-full text-[11px]" :class="i + 1 === current ? 'bg-white text-navy-950' : i + 1 < business.onboarding_step ? 'bg-navy-950 text-white' : 'bg-gray-200 text-gray-600'"><Icon v-if="i + 1 < business.onboarding_step && i + 1 !== current" name="check" :size="12" /><template v-else>{{ number(i + 1) }}</template></span>
                        {{ $t(`onboarding.steps.${k}`) }}
                    </component>
                </div>
                <div class="border-t border-[var(--border)] pt-4">
                    <Link :href="route('settings.profile')" class="flex items-center gap-3 rounded-2xl px-3 py-3 hover:bg-navy-50"><Icon name="settings" :size="18" />{{ $t('nav.settings') }}</Link>
                    <Link :href="route('support.index')" class="flex items-center gap-3 rounded-2xl px-3 py-3 hover:bg-navy-50"><Icon name="info" :size="18" />{{ $t('nav.support') }}</Link>
                    <button type="button" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-rose-600 hover:bg-rose-50" @click="logout"><Icon name="logout" :size="18" />{{ $t('nav.logout') }}</button>
                </div>
            </div>
        </Drawer>
        <MobileTabBar :items="tabs" :more-label="$t('nav.menu')" :more-open="menu" @more="menu = true" />
        <FlashToast />
    </div>
</template>
