<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/ui/Card.vue';
import Icon from '@/Components/ui/Icon.vue';
import CaseCard from '@/Components/domain/CaseCard.vue';
import { route, useI18n } from '@/i18n';
defineProps({ business: Object, cases: Array });
const { option } = useI18n();
const fields = ['legal_name', 'registration_number', 'founded_year', 'website', 'contact_name', 'contact_email', 'contact_phone', 'address'];
</script>

<template>
    <AppLayout :title="business.trade_name" :back="route('admin.businesses.index')" wide>
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
            <div class="space-y-6">
                <Card :title="$t('nav.business_profile')">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.industry') }}</dt><dd>{{ option('industries', business.industry) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.size') }}</dt><dd>{{ option('sizes', business.size) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t('fields.province') }}</dt><dd>{{ option('provinces', business.province) || business.province }}</dd></div>
                        <div v-for="f in fields" :key="f" class="flex justify-between gap-3"><dt class="text-gray-500">{{ $t(`fields.${f}`) }}</dt><dd class="text-end break-all">{{ business[f] ?? '—' }}</dd></div>
                    </dl>
                    <p class="mt-4 flex items-center gap-2 text-xs text-gray-500"><Icon name="eye" :size="14" />{{ $t('admin.view_audited') }}</p>
                </Card>
                <Card :title="$t('onboarding.steps.documents')">
                    <ul class="space-y-2 text-sm"><li v-for="d in business.documents" :key="d.id"><a v-if="d.url" :href="d.url" class="flex items-center gap-2 hover:underline"><Icon name="file" :size="16" />{{ d.name }}</a></li></ul>
                </Card>
            </div>
            <div class="grid content-start gap-4 md:grid-cols-2"><CaseCard v-for="c in cases" :key="c.id" :item="c" :href="route('review.cases.show', { case: c.number })" /></div>
        </div>
    </AppLayout>
</template>
