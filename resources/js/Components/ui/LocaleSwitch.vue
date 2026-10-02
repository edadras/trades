<script setup>
import { localeSwitchUrl, useI18n } from '@/i18n';
import Icon from './Icon.vue';
defineProps({ light: Boolean });
const { locale } = useI18n();
</script>

<template>
    <a :href="localeSwitchUrl(locale === 'fa' ? 'en' : 'fa')" class="inline-flex h-10 items-center gap-1.5 rounded-full px-3 text-sm font-medium transition" :class="light ? 'text-white/90 hover:bg-white/10' : 'text-gray-700 hover:bg-gray-100'" :hreflang="locale === 'fa' ? 'en' : 'fa'">
        <Icon name="globe" :size="16" />{{ locale === 'fa' ? 'EN' : 'فا' }}
    </a>
</template>
