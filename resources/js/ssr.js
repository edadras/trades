import { createSSRApp, h } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { ZiggyVue } from 'ziggy-js';
import { i18nPlugin } from '@/i18n';

const pages = import.meta.glob('./Pages/**/*.vue');

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => title || 'Hamyar',
        resolve: (name) => pages[`./Pages/${name}.vue`](),
        setup({ App, props, plugin }) {
            return createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, { ...page.props.ziggy, location: new URL(page.props.ziggy.location) })
                .use(i18nPlugin);
        },
    }),
);
