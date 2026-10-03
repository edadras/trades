<script setup>
import Avatar from '@/Components/ui/Avatar.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useI18n } from '@/i18n';
defineProps({ message: { type: Object, required: true }, mine: Boolean, read: Boolean });
defineEmits(['reply']);

const { dateTime, date } = useI18n();
const time = (v) => date(v, { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <div class="group flex items-end gap-2" :class="mine ? 'flex-row-reverse' : ''">
        <Avatar v-if="!mine" :name="message.user?.name" size="xs" />
        <div class="max-w-[82%] sm:max-w-[70%]">
            <p v-if="!mine" class="mb-1 ps-3 text-xs text-gray-500">{{ message.user?.name }}</p>
            <div class="rounded-3xl px-4 py-2.5 text-[15px] leading-7 shadow-sm" :class="mine ? 'rounded-ee-md bg-navy-950 text-white' : 'rounded-es-md bg-white text-ink ring-1 ring-[var(--border)]'">
                <div v-if="message.reply_to" class="mb-2 rounded-2xl border-s-2 px-3 py-1.5 text-xs" :class="mine ? 'border-white/50 bg-white/10' : 'border-navy-400 bg-navy-50'">
                    <span class="font-medium">{{ message.reply_to.user }}</span> — {{ message.reply_to.body }}
                </div>
                <p v-if="message.body" class="whitespace-pre-line break-words">{{ message.body }}</p>
                <div v-for="a in message.attachments" :key="a.id" class="mt-2">
                    <audio v-if="message.type === 'voice' && a.inline_url" :src="a.inline_url" controls class="h-10 w-60 max-w-full" />
                    <a v-else-if="a.url" :href="a.url" class="flex items-center gap-2 rounded-2xl px-3 py-2 text-sm" :class="mine ? 'bg-white/10 hover:bg-white/20' : 'bg-navy-50 hover:bg-navy-100'">
                        <Icon name="file" :size="16" /><span class="truncate">{{ a.original_name }}</span><Icon name="download" :size="14" class="ms-auto" />
                    </a>
                    <span v-else class="flex items-center gap-2 text-xs opacity-70"><Icon name="shield" :size="14" />{{ $t('files.scanning') }}</span>
                </div>
            </div>
            <div class="mt-1 flex items-center gap-2 px-2 text-[11px] text-gray-400" :class="mine ? 'justify-end' : ''">
                <time :title="dateTime(message.created_at)">{{ time(message.created_at) }}</time>
                <Icon v-if="mine" :name="read ? 'check' : 'clock'" :size="12" :class="read ? 'text-navy-500' : ''" />
                <button type="button" class="opacity-0 transition group-hover:opacity-100 focus:opacity-100" :aria-label="$t('chat.reply')" @click="$emit('reply', message)"><Icon name="reply" :size="13" /></button>
            </div>
        </div>
    </div>
</template>
