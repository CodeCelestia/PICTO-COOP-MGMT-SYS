import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import Swal from 'sweetalert2';
import '../css/app.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import { initializeTheme } from '@/composables/useAppearance';

const appName = 'COOP-SDN-MIS';
const NAVIGATION_STUCK_TIMEOUT_MS = 8000;
const ALERT_CONFIRM_COLOR = '#0e7ea0';

let pendingNavigationUrl: string | null = null;
let navigationWatchdog: ReturnType<typeof setTimeout> | null = null;
let lastResolvedComponentName = '';

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

const buildDocumentTitle = (rawTitle?: string) => {
    const normalizedTitle = rawTitle ? normalizeHeadTitle(rawTitle) : '';
    const shouldUseFallback = !normalizedTitle || /^index$/i.test(normalizedTitle);
    const pageTitle = shouldUseFallback ? toReadablePageName(lastResolvedComponentName) : normalizedTitle;

    return `${pageTitle} - ${appName}`;
};

const escapeHtml = (value: string) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

const getFieldLabel = (field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) => {
    if (field.id) {
        const associatedLabel = document.querySelector(`label[for="${field.id}"]`);

        if (associatedLabel?.textContent?.trim()) {
            return associatedLabel.textContent.trim();
        }
    }

    const wrappedLabel = field.closest('label');
    if (wrappedLabel?.textContent?.trim()) {
        return wrappedLabel.textContent.trim();
    }

    const ariaLabel = field.getAttribute('aria-label');
    if (ariaLabel?.trim()) {
        return ariaLabel.trim();
    }

    const placeholder = field.getAttribute('placeholder');
    if (placeholder?.trim()) {
        return placeholder.trim();
    }

    if (field.name?.trim()) {
        return field.name.trim().replace(/[_-]/g, ' ');
    }

    return 'Required field';
};

const isVisibleField = (field: HTMLElement) => {
    return field.offsetParent !== null || field.getClientRects().length > 0;
};

const getMissingRequiredFields = (form: HTMLFormElement) => {
    const requiredFields = Array.from(
        form.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>(
            'input[required], select[required], textarea[required]',
        ),
    );

    const missing: Array<{
        field: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
        label: string;
    }> = [];
    const processedRadioGroups = new Set<string>();

    requiredFields.forEach((field) => {
        if (field.disabled) {
            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'hidden') {
            return;
        }

        if (!isVisibleField(field)) {
            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'radio') {
            const groupName = field.name || field.id;
            if (!groupName || processedRadioGroups.has(groupName)) {
                return;
            }

            processedRadioGroups.add(groupName);
            const group = requiredFields.filter(
                (candidate): candidate is HTMLInputElement =>
                    candidate instanceof HTMLInputElement
                    && candidate.type === 'radio'
                    && candidate.name === field.name,
            );

            if (!group.some((radio) => radio.checked)) {
                missing.push({ field: group[0] ?? field, label: getFieldLabel(group[0] ?? field) });
            }

            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'checkbox') {
            if (!field.checked) {
                missing.push({ field, label: getFieldLabel(field) });
            }

            return;
        }

        if (field instanceof HTMLInputElement && field.type === 'file') {
            if (!field.files || field.files.length === 0) {
                missing.push({ field, label: getFieldLabel(field) });
            }

            return;
        }

        if (!field.value.trim()) {
            missing.push({ field, label: getFieldLabel(field) });
        }
    });

    return missing;
};

const registerRequiredFieldAlert = () => {
    document.addEventListener(
        'submit',
        (event) => {
            const form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            const missingFields = getMissingRequiredFields(form);
            if (!missingFields.length) {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            const uniqueLabels = [...new Set(missingFields.map(({ label }) => label))];
            const firstMissingField = missingFields[0]?.field;
            const fieldsList = uniqueLabels
                .slice(0, 8)
                .map((label) => `<li>${escapeHtml(label)}</li>`)
                .join('');
            const additionalCount = uniqueLabels.length > 8 ? uniqueLabels.length - 8 : 0;
            const additionalText =
                additionalCount > 0
                    ? `<p class="mt-2 text-sm">...and ${additionalCount} more required field(s).</p>`
                    : '';

            void Swal.fire({
                icon: 'warning',
                title: 'Missing Required Fields',
                html: `<p>Please fill in all required fields before continuing.</p><ul style="margin-top:8px;text-align:left;display:inline-block;max-width:100%;padding-left:20px;">${fieldsList}</ul>${additionalText}`,
                confirmButtonColor: ALERT_CONFIRM_COLOR,
            }).then(() => {
                if (firstMissingField) {
                    firstMissingField.focus();
                    firstMissingField.scrollIntoView({ block: 'center', behavior: 'smooth' });
                }
            });
        },
        true,
    );
};

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
    title: (title) => buildDocumentTitle(title),
    resolve: (name) => {
        lastResolvedComponentName = name;

        return resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        );
    },
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
registerRequiredFieldAlert();
