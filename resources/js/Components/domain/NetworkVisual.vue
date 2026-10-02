<script setup>
// Hero illustration: businesses → AI engine → experts, drawn as an animated network (no external assets).
const left = [[60, 70], [40, 160], [70, 250]];
const right = [[440, 60], [470, 150], [445, 245]];
</script>

<template>
    <svg viewBox="0 0 520 310" class="w-full" role="img" :aria-label="$t('home.visual_label')">
        <defs>
            <linearGradient id="nv-core" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#ffffff" /><stop offset="1" stop-color="#c9d7f5" /></linearGradient>
            <radialGradient id="nv-glow"><stop offset="0" stop-color="#ffffff" stop-opacity=".55" /><stop offset="1" stop-color="#ffffff" stop-opacity="0" /></radialGradient>
        </defs>
        <circle cx="260" cy="155" r="120" fill="url(#nv-glow)" />
        <g stroke="#ffffff" stroke-opacity=".55" stroke-width="1.5" fill="none" stroke-dasharray="4 6" class="nv-flow">
            <path v-for="(p, i) in left" :key="`l${i}`" :d="`M${p[0] + 22},${p[1]} C${p[0] + 110},${p[1]} 180,155 222,155`" />
            <path v-for="(p, i) in right" :key="`r${i}`" :d="`M298,155 C340,155 ${p[0] - 110},${p[1]} ${p[0] - 22},${p[1]}`" />
        </g>
        <g v-for="(p, i) in left" :key="`bl${i}`">
            <rect :x="p[0] - 22" :y="p[1] - 22" width="44" height="44" rx="14" fill="#ffffff" fill-opacity=".92" />
            <path :transform="`translate(${p[0] - 10},${p[1] - 10}) scale(.83)`" d="M3 21V10l6 4V10l6 4V6l6 3v12H3z" stroke="#1a326f" stroke-width="1.8" fill="none" stroke-linejoin="round" />
        </g>
        <g v-for="(p, i) in right" :key="`br${i}`">
            <circle :cx="p[0]" :cy="p[1]" r="22" fill="#ffffff" fill-opacity=".92" />
            <circle :cx="p[0]" :cy="p[1] - 5" r="5.5" fill="none" stroke="#1a326f" stroke-width="1.8" />
            <path :d="`M${p[0] - 10},${p[1] + 12} a10,9 0 0 1 20,0`" fill="none" stroke="#1a326f" stroke-width="1.8" />
        </g>
        <g class="nv-pulse">
            <rect x="214" y="107" width="92" height="96" rx="30" fill="url(#nv-core)" stroke="#ffffff" stroke-width="2" />
            <path d="M260 128l3.6 9.4 9.4 3.6-9.4 3.6L260 154l-3.6-9.4-9.4-3.6 9.4-3.6z" fill="#1a326f" />
            <text x="260" y="182" text-anchor="middle" font-size="12" font-weight="600" fill="#1a326f">AI</text>
        </g>
        <text x="60" y="296" text-anchor="middle" font-size="12" fill="#ffffff" fill-opacity=".9">{{ $t('home.visual_businesses') }}</text>
        <text x="260" y="236" text-anchor="middle" font-size="12" fill="#ffffff" fill-opacity=".9">{{ $t('home.visual_ai') }}</text>
        <text x="452" y="296" text-anchor="middle" font-size="12" fill="#ffffff" fill-opacity=".9">{{ $t('home.visual_experts') }}</text>
    </svg>
</template>

<style scoped>
.nv-flow { animation: nv-dash 2.4s linear infinite; }
.nv-pulse { transform-origin: 260px 155px; animation: nv-pulse 3s ease-in-out infinite; }
@keyframes nv-dash { to { stroke-dashoffset: -40; } }
@keyframes nv-pulse { 50% { transform: scale(1.04); } }
@media (prefers-reduced-motion: reduce) { .nv-flow, .nv-pulse { animation: none; } }
</style>
