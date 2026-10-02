import '../css/app.css';
import { createApp, createSSRApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import { i18nPlugin } from '@/i18n';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => title || 'Hamyar',
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return page();
    },
    setup({ el, App, props, plugin }) {
        const create = el.innerHTML.trim() !== '' ? createSSRApp : createApp;
        create({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, props.initialPage.props.ziggy)
            .use(i18nPlugin)
            .mount(el);
    },
    progress: { color: '#3f68cc', showSpinner: false },
});
