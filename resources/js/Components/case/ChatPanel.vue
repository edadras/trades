<script setup>
// Secure case conversation: text, voice notes, attachments, replies, @mentions and read receipts.
// Real-time via Reverb when configured, otherwise polling every 8 seconds.
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ChatBubble from '@/Components/domain/ChatBubble.vue';
import VoiceRecorder from '@/Components/ui/VoiceRecorder.vue';
import Uploader from '@/Components/ui/Uploader.vue';
import Button from '@/Components/ui/Button.vue';
import Icon from '@/Components/ui/Icon.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import { getEcho } from '@/echo';
import { route } from '@/i18n';

const props = defineProps({ item: Object });
const page = usePage();
const me = computed(() => page.props.auth.user.id);
const messages = ref([...(props.item.conversation?.messages ?? [])]);
const members = ref(props.item.conversation?.members ?? []);
const list = ref(null);
const replyTo = ref(null);
const mode = ref('text'); // text | voice | files
const form = useForm({ body: '', reply_to_id: null, attachments: [], voice: null, voice_duration: null });

const scroll = () => nextTick(() => list.value && (list.value.scrollTop = list.value.scrollHeight));
const lastId = () => messages.value.at(-1)?.id ?? 0;
const readBy = (m) => members.value.some((x) => x.id !== me.value && (x.last_read_message_id ?? 0) >= m.id);

async function poll() {
    try {
        const res = await fetch(route('cases.messages.since', { case: props.item.number, after: lastId() }), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) return;
        const data = await res.json();
        if (data.messages.length) { messages.value.push(...data.messages.filter((m) => !messages.value.some((x) => x.id === m.id))); scroll(); }
        members.value = members.value.map((m) => ({ ...m, last_read_message_id: data.members.find((x) => x.user_id === m.id)?.last_read_message_id ?? m.last_read_message_id }));
    } catch { /* offline: try again next tick */ }
}

let timer, channel;
onMounted(async () => {
    scroll();
    const echo = await getEcho(page.props.app?.reverb);
    if (echo && props.item.conversation) {
        channel = echo.private(`conversations.${props.item.conversation.id}`).listen('.message.sent', (e) => { if (!messages.value.some((m) => m.id === e.message.id)) { messages.value.push(e.message); scroll(); } });
    }
    timer = setInterval(poll, channel ? 30000 : 8000);
});
onBeforeUnmount(() => { clearInterval(timer); channel && getEcho().then((e) => e?.leave(`conversations.${props.item.conversation.id}`)); });

function send() {
    form.reply_to_id = replyTo.value?.id ?? null;
    form.post(route('cases.messages.store', { case: props.item.number }), {
        forceFormData: true, preserveScroll: true, preserveState: true,
        onSuccess: () => { form.reset(); replyTo.value = null; mode.value = 'text'; poll(); },
    });
}
const mention = (name) => (form.body = `${form.body}@${name.replace(/\s+/g, '_')} `);
</script>

<template>
    <EmptyState v-if="!item.conversation" icon="chat" :title="$t('chat.unavailable')" :text="$t('chat.unavailable_hint')" />
    <section v-else class="card flex h-[70vh] min-h-[480px] flex-col overflow-hidden">
        <header class="flex items-center gap-2 border-b border-[var(--border)] px-4 py-3">
            <Icon name="lock" :size="16" class="text-emerald-600" />
            <p class="text-sm font-medium text-ink">{{ $t('chat.title') }}</p>
            <div class="ms-auto flex -space-x-1.5 rtl:space-x-reverse">
                <button v-for="m in members" :key="m.id" type="button" class="grid size-7 place-items-center rounded-full bg-navy-100 text-[10px] font-semibold text-navy-800 ring-2 ring-white" :title="m.name" @click="m.name && mention(m.name)">{{ (m.name ?? '?').slice(0, 1) }}</button>
            </div>
        </header>
        <div ref="list" class="flex-1 space-y-4 overflow-y-auto bg-[var(--surface-muted)] p-4">
            <p v-if="!messages.length" class="py-10 text-center text-sm text-gray-400">{{ $t('chat.empty') }}</p>
            <ChatBubble v-for="m in messages" :key="m.id" :message="m" :mine="m.user?.id === me" :read="readBy(m)" @reply="replyTo = $event" />
        </div>
        <form v-if="item.can.participate" class="border-t border-[var(--border)] bg-white p-3" @submit.prevent="send">
            <div v-if="replyTo" class="mb-2 flex items-center gap-2 rounded-2xl bg-navy-50 px-3 py-2 text-xs text-navy-800">
                <Icon name="reply" :size="14" /><span class="min-w-0 flex-1 truncate">{{ replyTo.user?.name }}: {{ replyTo.body }}</span>
                <button type="button" @click="replyTo = null"><Icon name="x" :size="14" /></button>
            </div>
            <VoiceRecorder v-if="mode === 'voice'" v-model="form.voice" class="mb-2" @duration="form.voice_duration = $event" />
            <Uploader v-if="mode === 'files'" v-model="form.attachments" compact class="mb-2" />
            <div class="flex items-end gap-1.5">
                <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full hover:bg-gray-100" :class="mode === 'files' ? 'text-navy-700' : 'text-gray-500'" :aria-label="$t('intake.attach')" @click="mode = mode === 'files' ? 'text' : 'files'"><Icon name="paperclip" :size="18" /></button>
                <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full hover:bg-gray-100" :class="mode === 'voice' ? 'text-navy-700' : 'text-gray-500'" :aria-label="$t('voice.record')" @click="mode = mode === 'voice' ? 'text' : 'voice'"><Icon name="mic" :size="18" /></button>
                <textarea v-model="form.body" rows="1" class="input max-h-36 min-h-10 flex-1 resize-none py-2" :placeholder="$t('chat.placeholder')" @keydown.enter.exact.prevent="(form.body || form.voice || form.attachments.length) && send()" />
                <Button type="submit" icon-only icon="send" :loading="form.processing" :disabled="!form.body && !form.voice && !form.attachments.length" :aria-label="$t('common.send')" />
            </div>
            <p v-if="form.errors.body || form.errors.voice" class="mt-1 text-xs text-rose-600">{{ form.errors.body || form.errors.voice }}</p>
        </form>
    </section>
</template>
