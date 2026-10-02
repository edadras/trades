<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';
import Stepper from '@/Components/ui/Stepper.vue';
import StatusBadge from './StatusBadge.vue';
import UrgencyBadge from './UrgencyBadge.vue';
import { useI18n } from '@/i18n';

defineProps({ item: { type: Object, required: true }, href: String });
const { relative } = useI18n();
</script>

<template>
    <Link :href="href" class="card card-hover group block p-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs text-gray-400" dir="ltr">{{ item.number }}</span>
            <StatusBadge :status="item.status" />
            <UrgencyBadge :urgency="item.urgency" />
        </div>
        <h3 class="mt-3 line-clamp-2 text-[16px] font-semibold leading-7 text-ink">{{ item.title || $t('case.untitled') }}</h3>
        <p v-if="item.category" class="mt-1 text-sm text-gray-500">{{ item.category.name }}<template v-if="item.subcategory"> · {{ item.subcategory.name }}</template></p>
        <Stepper v-if="item.stepper" :steps="item.stepper" compact class="mt-4" />
        <div v-if="item.next_action" class="mt-4 flex items-start gap-2 rounded-2xl bg-navy-50 p-3 text-sm">
            <Icon name="flag" :size="16" class="mt-0.5 text-navy-700" />
            <div class="min-w-0">
                <p class="text-xs text-navy-700">{{ $t('case.next_action') }}<template v-if="item.next_action_owner"> · {{ $t(`roles_short.${item.next_action_owner}`) }}</template></p>
                <p class="line-clamp-2 text-ink">{{ item.next_action }}</p>
            </div>
        </div>
        <div class="mt-4 flex items-center justify-between text-xs text-gray-400">
            <span>{{ $t('common.updated') }} {{ relative(item.updated_at) }}</span>
            <Icon name="arrow" :size="16" class="text-navy-700 transition group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" />
        </div>
    </Link>
</template>
