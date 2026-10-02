<script setup>
// Date / date-time input that shows the Jalali calendar for Persian and the Gregorian calendar for English.
// The user picks a wall-clock time in their own time zone; the model value is ISO-8601 UTC
// (`datetime` mode) or a Gregorian `YYYY-MM-DD` (`date` mode), so the server always receives one format.
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from '@/i18n';
import { jalaliMonthLength, pad, toGregorian, toJalali, zonedParts, zonedToUtc } from '@/lib/jalali';

const props = defineProps({
    modelValue: { type: String, default: '' },
    mode: { type: String, default: 'datetime' },
    label: String,
    hint: String,
    error: String,
    required: Boolean,
});
const emit = defineEmits(['update:modelValue']);
const { t, locale } = useI18n();
const page = usePage();
const timeZone = computed(() => page.props.auth?.user?.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Tehran');
const jalali = computed(() => locale.value === 'fa');
const withTime = computed(() => props.mode === 'datetime');

const parts = ref({ y: '', m: '', d: '', time: '' });

function fromModel(value) {
    if (!value) return { y: '', m: '', d: '', time: '' };
    let y; let m; let d; let time = '';
    if (withTime.value) {
        const z = zonedParts(new Date(value), timeZone.value);
        ({ y, m, d } = z);
        time = `${pad(z.h)}:${pad(z.mi)}`;
    } else {
        [y, m, d] = value.slice(0, 10).split('-').map(Number);
    }
    if (jalali.value) {
        const j = toJalali(y, m, d);
        return { y: j.jy, m: j.jm, d: j.jd, time };
    }
    return { y, m, d, time };
}

function toModel() {
    const { y, m, d, time } = parts.value;
    if (!y || !m || !d || (withTime.value && !time)) return '';
    const g = jalali.value ? toGregorian(Number(y), Number(m), Number(d)) : { gy: Number(y), gm: Number(m), gd: Number(d) };
    if (!withTime.value) return `${g.gy}-${pad(g.gm)}-${pad(g.gd)}`;
    const [h, mi] = time.split(':').map(Number);
    return zonedToUtc({ y: g.gy, m: g.gm, d: g.gd, h, mi }, timeZone.value).toISOString();
}

watch(() => [props.modelValue, locale.value], () => {
    if (props.modelValue !== toModel()) parts.value = fromModel(props.modelValue);
}, { immediate: true });

function update(key, value) {
    parts.value = { ...parts.value, [key]: value };
    const max = parts.value.y && parts.value.m ? daysIn(Number(parts.value.y), Number(parts.value.m)) : 31;
    if (Number(parts.value.d) > max) parts.value.d = max;
    emit('update:modelValue', toModel());
}

function daysIn(y, m) {
    return jalali.value ? jalaliMonthLength(y, m) : new Date(Date.UTC(y, m, 0)).getUTCDate();
}

const monthsFa = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
const monthsEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
const months = computed(() => (jalali.value ? monthsFa : monthsEn).map((label, i) => ({ value: i + 1, label })));
const days = computed(() => Array.from({ length: parts.value.y && parts.value.m ? daysIn(Number(parts.value.y), Number(parts.value.m)) : 31 }, (_, i) => i + 1));
const years = computed(() => {
    const now = new Date();
    const current = jalali.value ? toJalali(now.getFullYear(), now.getMonth() + 1, now.getDate()).jy : now.getFullYear();
    return Array.from({ length: 8 }, (_, i) => current - 2 + i);
});
const digits = (n) => (jalali.value ? new Intl.NumberFormat('fa-IR', { useGrouping: false }).format(n) : String(n));
const zoneLabel = computed(() => t('datetime.zone', { zone: timeZone.value }));
</script>

<template>
    <div>
        <label v-if="label" class="label">{{ label }}<span v-if="required" class="text-rose-500"> *</span></label>
        <div class="flex flex-wrap gap-2" role="group" :aria-label="label">
            <select class="input w-auto min-w-[5.5rem] flex-1 appearance-none" :value="parts.d" :aria-label="t('datetime.day')" :aria-invalid="!!error" @change="update('d', $event.target.value)">
                <option value="">{{ t('datetime.day') }}</option>
                <option v-for="d in days" :key="d" :value="d">{{ digits(d) }}</option>
            </select>
            <select class="input w-auto min-w-[8rem] flex-[2] appearance-none" :value="parts.m" :aria-label="t('datetime.month')" @change="update('m', $event.target.value)">
                <option value="">{{ t('datetime.month') }}</option>
                <option v-for="m in months" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
            <select class="input w-auto min-w-[6rem] flex-1 appearance-none" :value="parts.y" :aria-label="t('datetime.year')" @change="update('y', $event.target.value)">
                <option value="">{{ t('datetime.year') }}</option>
                <option v-for="y in years" :key="y" :value="y">{{ digits(y) }}</option>
            </select>
            <input v-if="withTime" type="time" dir="ltr" class="input w-auto min-w-[7rem] flex-1" :value="parts.time" :aria-label="t('datetime.time')" step="300" @input="update('time', $event.target.value)" />
        </div>
        <p v-if="error" class="mt-1.5 text-sm text-rose-600">{{ error }}</p>
        <p v-else class="mt-1.5 text-xs text-gray-500">{{ hint ?? (withTime ? zoneLabel : '') }}</p>
    </div>
</template>
