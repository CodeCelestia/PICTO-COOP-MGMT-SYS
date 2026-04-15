import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

const appName = 'COOP-SDN-MIS';

const toReadableSegment = (segment: string) =>
    segment
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[-_]/g, ' ')
        .trim();

const toReadablePageName = (componentName: string) => {
    const segments = componentName
        .split('/')
        .map((segment) => toReadableSegment(segment))
        .filter(Boolean);

    if (!segments.length) {
        return 'Page';
    }

    for (let i = segments.length - 1; i >= 0; i -= 1) {
        if (!/^index$/i.test(segments[i])) {
            return segments[i];
        }
    }

    return segments[segments.length - 1] || 'Page';
};

const normalizeHeadTitle = (title: string) => {
    return title
        .replace(/\s*-\s*Coop System\s*$/i, '')
        .trim();
};

createServer(
    (page) =>
        createInertiaApp({
            page,
            render: renderToString,
            title: (title) => {
                const normalizedTitle = title ? normalizeHeadTitle(title) : '';
                const shouldUseFallback = !normalizedTitle || /^index$/i.test(normalizedTitle);
                const pageTitle = shouldUseFallback ? toReadablePageName(page.component) : normalizedTitle;

                return `${pageTitle} - ${appName}`;
            },
            resolve: (name) =>
                resolvePageComponent(
                    `./pages/${name}.vue`,
                    import.meta.glob<DefineComponent>('./pages/**/*.vue'),
                ),
            setup: ({ App, props, plugin }) =>
                createSSRApp({ render: () => h(App, props) }).use(plugin),
        }),
    { cluster: true },
);
