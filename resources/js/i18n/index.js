import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';
import common from './messages/common';
import publicSite from './messages/public';
import app from './messages/app';
import cases from './messages/cases';
import admin from './messages/admin';
import pilot from './messages/pilot';
import governance from './messages/governance';
import lifecycle from './messages/lifecycle';
import account from './messages/account';

const bundles = [common, publicSite, app, cases, admin, pilot, governance, lifecycle, account];
const dictionaries = { fa: {}, en: {} };
for (const bundle of bundles) {
    for (const locale of Object.keys(dictionaries)) Object.assign(dictionaries[locale], bundle[locale]);
}

function lookup(dict, key) {
    return key.split('.').reduce((node, part) => (node && typeof node === 'object' ? node[part] : undefined), dict);
}

export function translate(locale, key, params = {}) {
    let value = lookup(dictionaries[locale] ?? {}, key) ?? lookup(dictionaries.en, key) ?? key;
    if (typeof value !== 'string') return key;
    for (const [k, v] of Object.entries(params)) value = value.replaceAll(`:${k}`, v ?? '');
    return value;
}

/** Route helper that always uses the current page's Ziggy config (locale defaults change between pages). */
export function route(name, params, absolute = false) {
    const page = usePage();
    const config = page?.props?.ziggy;
    if (name === undefined) return ziggyRoute(undefined, undefined, absolute, config);
    return ziggyRoute(name, params, absolute, config);
}

export function useI18n() {
    const page = usePage();
    const locale = computed(() => page.props.app?.locale ?? 'fa');
    const dir = computed(() => page.props.app?.dir ?? 'rtl');
    const t = (key, params) => translate(locale.value, key, params);
    const intlLocale = computed(() => (locale.value === 'fa' ? 'fa-IR' : 'en-GB'));

    const number = (value, options = {}) => (value === null || value === undefined ? '—' : new Intl.NumberFormat(intlLocale.value, options).format(value));
    const date = (value, options = { dateStyle: 'medium' }) => (value ? new Intl.DateTimeFormat(intlLocale.value + (locale.value === 'fa' ? '-u-ca-persian' : ''), options).format(new Date(value)) : '—');
    const percent = (value) => (value === null || value === undefined ? '—' : `${number(value)}${locale.value === 'fa' ? '٪' : '%'}`);
    const dateTime = (value) => date(value, { dateStyle: 'medium', timeStyle: 'short' });
    const relative = (value) => {
        if (!value) return '—';
        const diff = (new Date(value).getTime() - Date.now()) / 1000;
        const rtf = new Intl.RelativeTimeFormat(intlLocale.value, { numeric: 'auto' });
        const units = [['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60]];
        for (const [unit, secs] of units) if (Math.abs(diff) >= secs) return rtf.format(Math.round(diff / secs), unit);
        return rtf.format(Math.round(diff), 'second');
    };
    const option = (group, value) => page.props.options?.[group]?.find((o) => o.value === value)?.label ?? value;

    return { t, locale, dir, number, percent, date, dateTime, relative, option, isRtl: computed(() => dir.value === 'rtl') };
}

/** Swap the locale segment of the current URL (/fa/... ↔ /en/...). */
export function localeSwitchUrl(target) {
    const page = usePage();
    const url = new URL(page.props.ziggy?.location ?? 'http://localhost/');
    const parts = url.pathname.split('/');
    parts[1] = target;
    return parts.join('/') + url.search;
}

export const i18nPlugin = {
    install(app) {
        app.config.globalProperties.$t = (key, params) => translate(usePage().props.app?.locale ?? 'fa', key, params);
        app.config.globalProperties.route = route;
    },
};
