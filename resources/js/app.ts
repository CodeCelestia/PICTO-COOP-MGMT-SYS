import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import '../css/app.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const NAVIGATION_STUCK_TIMEOUT_MS = 8000;

let pendingNavigationUrl: string | null = null;
let navigationWatchdog: ReturnType<typeof setTimeout> | null = null;

const clearNavigationWatchdog = () => {
    pendingNavigationUrl = null;

    if (navigationWatchdog) {
        clearTimeout(navigationWatchdog);
        navigationWatchdog = null;
    }
};

router.on('start', (event) => {
    const startedUrl = event.detail.visit.url;
    pendingNavigationUrl =
        typeof startedUrl === 'string' ? startedUrl : startedUrl.toString();

    if (navigationWatchdog) {
        clearTimeout(navigationWatchdog);
    }

    navigationWatchdog = setTimeout(() => {
        if (pendingNavigationUrl) {
            window.location.assign(pendingNavigationUrl);
        }
    }, NAVIGATION_STUCK_TIMEOUT_MS);
});

router.on('finish', () => {
    clearNavigationWatchdog();
});

router.on('invalid', () => {
    clearNavigationWatchdog();
    window.location.reload();
});

router.on('exception', () => {
    if (pendingNavigationUrl) {
        window.location.assign(pendingNavigationUrl);
    }

    clearNavigationWatchdog();
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
