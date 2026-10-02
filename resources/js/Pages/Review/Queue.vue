<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import Button from '@/Components/ui/Button.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import UrgencyBadge from '@/Components/domain/UrgencyBadge.vue';
import { route, useI18n } from '@/i18n';
defineProps({ reviews: Array, slaHours: Number });
const { percent, number } = useI18n();
const claim = (r) => router.post(route('review.claim', { review: r.id }));
</script>

<template>
    <AppLayout :title="$t('nav.review_queue')" :subtitle="$t('review.queue_subtitle', { h: slaHours })">
        <div v-if="reviews.length" class="space-y-3">
            <article v-for="r in reviews" :key="r.id" class="card card-hover flex flex-col gap-4 p-5 lg:flex-row lg:items-center">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-mono text-xs text-gray-400" dir="ltr">{{ r.case_number }}</span>
                        <Badge tone="amber">{{ $t(`review_reason.${r.reason}`) }}</Badge>
                        <UrgencyBadge :urgency="r.ai_urgency" />
                        <Badge v-if="r.is_sensitive" tone="red"><Icon name="shield" :size="12" />{{ $t('analysis.sensitive') }}</Badge>
                        <Badge v-if="r.sla_breached" tone="red"><Icon name="clock" :size="12" />{{ $t('review.sla_breached') }}</Badge>
                    </div>
                    <h3 class="mt-2 font-semibold text-ink">{{ r.title }}</h3>
                    <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ r.summary }}</p>
                    <p class="mt-2 text-xs text-gray-500">{{ r.business }} · {{ $t('review.waiting_hours', { h: number(r.waiting_hours) }) }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">{{ $t('review.ai_says') }}</p>
                        <p class="font-medium text-ink">{{ r.ai_category ?? '—' }}</p>
                        <p class="text-xs text-violet-700">{{ $t('analysis.confidence') }} {{ percent(Math.round((r.ai_confidence ?? 0) * 100)) }}</p>
                    </div>
                    <Button icon="arrow" @click="claim(r)">{{ $t('review.start') }}</Button>
                </div>
            </article>
        </div>
        <EmptyState v-else icon="check" :title="$t('review.empty')" :text="$t('review.empty_hint')" />
    </AppLayout>
</template>
