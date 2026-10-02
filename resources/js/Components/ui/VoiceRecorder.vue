<script setup>
// Records a voice note with MediaRecorder, shows a live level meter, and emits a File + duration.
import { onBeforeUnmount, ref } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({ modelValue: { type: [Object, null], default: null }, compact: Boolean, maxSeconds: { type: Number, default: 300 } });
const emit = defineEmits(['update:modelValue', 'duration']);

const state = ref('idle'); // idle | recording | ready | unsupported | denied
const seconds = ref(0);
const level = ref(0);
const url = ref(null);
let recorder, stream, timer, ctx, analyser, raf, chunks = [];

async function start() {
    if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') return (state.value = 'unsupported');
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch {
        return (state.value = 'denied');
    }
    const type = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg'].find((t) => MediaRecorder.isTypeSupported?.(t)) || '';
    recorder = new MediaRecorder(stream, type ? { mimeType: type } : undefined);
    chunks = [];
    recorder.ondataavailable = (e) => e.data.size && chunks.push(e.data);
    recorder.onstop = () => {
        const mime = recorder.mimeType || 'audio/webm';
        const ext = mime.includes('mp4') ? 'm4a' : mime.includes('ogg') ? 'ogg' : 'webm';
        const file = new File(chunks, `voice-${Date.now()}.${ext}`, { type: mime.split(';')[0] });
        url.value = URL.createObjectURL(file);
        emit('update:modelValue', file);
        emit('duration', seconds.value);
        state.value = 'ready';
        cleanup();
    };
    recorder.start();
    state.value = 'recording';
    seconds.value = 0;
    timer = setInterval(() => { seconds.value++; if (seconds.value >= props.maxSeconds) stop(); }, 1000);
    ctx = new (window.AudioContext || window.webkitAudioContext)();
    analyser = ctx.createAnalyser();
    ctx.createMediaStreamSource(stream).connect(analyser);
    const data = new Uint8Array(analyser.frequencyBinCount);
    const tick = () => { analyser.getByteFrequencyData(data); level.value = Math.min(1, data.reduce((a, b) => a + b, 0) / data.length / 90); raf = requestAnimationFrame(tick); };
    tick();
}
function stop() { if (recorder?.state === 'recording') recorder.stop(); }
function cleanup() {
    clearInterval(timer); cancelAnimationFrame(raf);
    stream?.getTracks().forEach((t) => t.stop());
    ctx?.close?.();
}
function discard() { url.value && URL.revokeObjectURL(url.value); url.value = null; emit('update:modelValue', null); state.value = 'idle'; seconds.value = 0; }
const mmss = (s) => `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
onBeforeUnmount(() => { stop(); cleanup(); });
</script>

<template>
    <div class="flex items-center gap-3" :class="compact ? '' : 'rounded-3xl bg-[var(--surface-muted)] p-3 ring-1 ring-[var(--border)]'">
        <button v-if="state !== 'recording' && state !== 'ready'" type="button" class="group flex items-center gap-3 rounded-full bg-navy-950 py-1.5 ps-1.5 pe-4 text-sm font-medium text-white transition hover:bg-navy-900" @click="start">
            <span class="grid size-8 place-items-center rounded-full bg-white text-navy-950"><Icon name="mic" :size="16" /></span>
            <span v-if="!compact">{{ $t('voice.record') }}</span>
        </button>
        <template v-if="state === 'recording'">
            <button type="button" class="relative grid size-11 place-items-center rounded-full bg-rose-600 text-white" :aria-label="$t('voice.stop')" @click="stop">
                <span class="absolute inset-0 animate-ping rounded-full bg-rose-500/40" />
                <Icon name="stop" :size="16" />
            </button>
            <div class="flex h-8 flex-1 items-center gap-0.5" aria-hidden="true">
                <span v-for="i in 24" :key="i" class="w-1 rounded-full bg-navy-500 transition-all duration-75" :style="{ height: `${8 + Math.abs(Math.sin(i * 1.7 + seconds)) * level * 26}px` }" />
            </div>
            <span class="tabular-nums text-sm text-gray-600" dir="ltr">{{ mmss(seconds) }}</span>
        </template>
        <template v-if="state === 'ready'">
            <audio :src="url" controls class="h-10 min-w-0 flex-1" />
            <button type="button" class="grid size-9 place-items-center rounded-full text-gray-500 hover:bg-rose-50 hover:text-rose-600" :aria-label="$t('common.remove')" @click="discard"><Icon name="trash" :size="16" /></button>
        </template>
        <p v-if="state === 'unsupported'" class="text-xs text-gray-500">{{ $t('voice.unsupported') }}</p>
        <p v-if="state === 'denied'" class="text-xs text-rose-600">{{ $t('voice.denied') }}</p>
    </div>
</template>
