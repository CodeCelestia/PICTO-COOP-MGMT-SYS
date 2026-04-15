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
const PHONE_PLACEHOLDER = '09XX-XXX-XXXX';
const PHONE_INVALID_MESSAGE = 'Please enter a valid Philippine phone number (11 digits).';
const PHONE_FIELD_PATTERN = /(phone|mobile|cell(?:phone)?|telephone|tel|contact(?:[_\s-]*(?:no|number))?)/i;
const PHONE_CONTROL_KEYS = new Set([
    'Backspace',
    'Delete',
    'Tab',
    'Escape',
    'Enter',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Home',
    'End',
]);

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

const getPhoneLabelText = (field: HTMLInputElement) => {
    if (field.id) {
        const associatedLabel = document.querySelector(`label[for="${field.id}"]`);
        if (associatedLabel?.textContent) {
            return associatedLabel.textContent;
        }
    }

    const wrappedLabel = field.closest('label');
    if (wrappedLabel?.textContent) {
        return wrappedLabel.textContent;
    }

    const parentLabel = field.parentElement?.querySelector('label');
    if (parentLabel?.textContent) {
        return parentLabel.textContent;
    }

    return '';
};

const isPhoneInputField = (field: HTMLInputElement) => {
    if (field.dataset.phoneField === 'true') {
        return true;
    }

    if (field.type === 'tel') {
        return true;
    }

    const signature = [
        field.name,
        field.id,
        field.autocomplete,
        field.getAttribute('aria-label') || '',
        field.placeholder,
        getPhoneLabelText(field),
    ]
        .filter(Boolean)
        .join(' ');

    return PHONE_FIELD_PATTERN.test(signature);
};

const toDigits = (value: string) => value.replace(/\D/g, '');

const normalizeToLocalPhoneDigits = (value: string) => {
    let digits = toDigits(value);

    if (digits.startsWith('63')) {
        digits = `0${digits.slice(2)}`;
    } else if (digits.startsWith('9')) {
        digits = `0${digits}`;
    }

    return digits.slice(0, 11);
};

const formatPhoneDisplay = (value: string) => {
    const raw = value.trim();
    const digits = toDigits(raw);

    if (!digits.length) {
        return raw.startsWith('+') ? '+63 ' : '';
    }

    const wantsInternational =
        raw.startsWith('+63')
        || raw.startsWith('+')
        || (!raw.startsWith('0') && digits.startsWith('63'));

    if (wantsInternational) {
        let intlDigits = digits;

        if (intlDigits.startsWith('0')) {
            intlDigits = `63${intlDigits.slice(1)}`;
        } else if (intlDigits.startsWith('9')) {
            intlDigits = `63${intlDigits}`;
        } else if (!intlDigits.startsWith('63')) {
            intlDigits = `63${intlDigits}`;
        }

        intlDigits = intlDigits.slice(0, 12);
        const subscriberNumber = intlDigits.slice(2, 12);

        let formatted = '+63';
        if (subscriberNumber.length > 0) {
            formatted += ` ${subscriberNumber.slice(0, 3)}`;
        }
        if (subscriberNumber.length > 3) {
            formatted += `-${subscriberNumber.slice(3, 6)}`;
        }
        if (subscriberNumber.length > 6) {
            formatted += `-${subscriberNumber.slice(6, 10)}`;
        }

        return formatted;
    }

    let localDigits = digits;
    if (localDigits.startsWith('63')) {
        localDigits = `0${localDigits.slice(2)}`;
    } else if (localDigits.startsWith('9')) {
        localDigits = `0${localDigits}`;
    }

    localDigits = localDigits.slice(0, 11);

    let formatted = localDigits.slice(0, 4);
    if (localDigits.length > 4) {
        formatted += `-${localDigits.slice(4, 7)}`;
    }
    if (localDigits.length > 7) {
        formatted += `-${localDigits.slice(7, 11)}`;
    }

    return formatted;
};

const applyPhoneFieldDefaults = (field: HTMLInputElement) => {
    field.dataset.phoneField = 'true';
    field.placeholder = PHONE_PLACEHOLDER;
    field.inputMode = 'numeric';

    if (!field.autocomplete) {
        field.autocomplete = 'tel';
    }
};

const applyPhoneFormattingToField = (field: HTMLInputElement, dispatchInput = false) => {
    if (!isPhoneInputField(field)) {
        return;
    }

    applyPhoneFieldDefaults(field);

    const formattedValue = formatPhoneDisplay(field.value);
    if (field.value === formattedValue) {
        return;
    }

    field.value = formattedValue;

    if (dispatchInput) {
        field.dispatchEvent(new Event('input', { bubbles: true }));
    } else {
        try {
            field.setSelectionRange(formattedValue.length, formattedValue.length);
        } catch {
            // Keep behavior safe for inputs that don't support text selection.
        }
    }
};

const applyPhoneFormattingToDocument = (root: ParentNode = document) => {
    root.querySelectorAll<HTMLInputElement>('input').forEach((field) => {
        if (isPhoneInputField(field)) {
            applyPhoneFormattingToField(field);
        }
    });
};

const getInvalidPhoneFields = (form: HTMLFormElement) => {
    const invalidFields: HTMLInputElement[] = [];

    form.querySelectorAll<HTMLInputElement>('input').forEach((field) => {
        if (!isPhoneInputField(field) || field.disabled || !isVisibleField(field)) {
            return;
        }

        applyPhoneFormattingToField(field);

        const value = field.value.trim();
        if (!value) {
            return;
        }

        const localDigits = normalizeToLocalPhoneDigits(value);
        if (!/^09\d{9}$/.test(localDigits)) {
            invalidFields.push(field);
            return;
        }

        if (field.value !== localDigits) {
            field.value = localDigits;
            field.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    return invalidFields;
};

const registerPhilippinePhoneFormatting = () => {
    applyPhoneFormattingToDocument();

    document.addEventListener(
        'focusin',
        (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement)) {
                return;
            }

            applyPhoneFormattingToField(target);
        },
        true,
    );

    document.addEventListener(
        'keydown',
        (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !isPhoneInputField(target)) {
                return;
            }

            applyPhoneFieldDefaults(target);

            if (event.ctrlKey || event.metaKey) {
                return;
            }

            if (PHONE_CONTROL_KEYS.has(event.key)) {
                return;
            }

            if (/^\d$/.test(event.key)) {
                return;
            }

            if (
                event.key === '+'
                && (target.selectionStart ?? 0) === 0
                && !target.value.includes('+')
            ) {
                return;
            }

            event.preventDefault();
        },
        true,
    );

    document.addEventListener(
        'input',
        (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !isPhoneInputField(target)) {
                return;
            }

            applyPhoneFormattingToField(target);
        },
        true,
    );

    document.addEventListener(
        'paste',
        (event) => {
            const target = event.target;
            if (!(target instanceof HTMLInputElement) || !isPhoneInputField(target)) {
                return;
            }

            event.preventDefault();

            const pastedText = event.clipboardData?.getData('text') || '';
            target.value = formatPhoneDisplay(pastedText);
            target.dispatchEvent(new Event('input', { bubbles: true }));
        },
        true,
    );

    const observer = new MutationObserver(() => {
        applyPhoneFormattingToDocument();
    });

    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    router.on('finish', () => {
        window.requestAnimationFrame(() => {
            applyPhoneFormattingToDocument();
        });
    });
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
                const invalidPhoneFields = getInvalidPhoneFields(form);

                if (!invalidPhoneFields.length) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const firstInvalidPhoneField = invalidPhoneFields[0];

                void Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Phone Number',
                    text: PHONE_INVALID_MESSAGE,
                    confirmButtonColor: ALERT_CONFIRM_COLOR,
                }).then(() => {
                    if (firstInvalidPhoneField) {
                        firstInvalidPhoneField.focus();
                        firstInvalidPhoneField.scrollIntoView({ block: 'center', behavior: 'smooth' });
                    }
                });

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
registerPhilippinePhoneFormatting();
registerRequiredFieldAlert();
