import type { InertiaLinkProps } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, readonly } from 'vue';
import { toUrl } from '@/lib/utils';

export type UseCurrentUrlReturn = {
    currentUrl: DeepReadonly<ComputedRef<string>>;
    isCurrentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
        startsWith?: boolean,
    ) => boolean;
    isCurrentOrParentUrl: (
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) => boolean;
    whenCurrentUrl: <T, F = null>(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: T,
        ifFalse?: F,
    ) => T | F;
};

const page = usePage();
const normalizePath = (path: string) => {
    if (!path) {
        return '/';
    }

    const trimmed = path.trim();

    if (trimmed === '/') {
        return '/';
    }

    return trimmed.replace(/\/+$/, '');
};

const currentUrlReactive = computed(() => {
    try {
        return normalizePath(new URL(page.url, window?.location.origin).pathname);
    } catch {
        return '/';
    }
});

export function useCurrentUrl(): UseCurrentUrlReturn {
    function isCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
        startsWith: boolean = false,
    ) {
        const urlToCompare = normalizePath(currentUrl ?? currentUrlReactive.value);
        const urlString = toUrl(urlToCheck);

        if (!urlString) {
            return false;
        }

        const comparePath = (path: string): boolean => {
            const normalizedPath = normalizePath(path);

            return startsWith
                ? normalizedPath === '/'
                    ? urlToCompare === '/'
                    : urlToCompare === normalizedPath || urlToCompare.startsWith(`${normalizedPath}/`)
                : normalizedPath === urlToCompare;
        };

        if (!urlString.startsWith('http')) {
            return comparePath(urlString);
        }

        try {
            const absoluteUrl = new URL(urlString);

            return comparePath(absoluteUrl.pathname);
        } catch {
            return false;
        }
    }

    function isCurrentOrParentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        currentUrl?: string,
    ) {
        return isCurrentUrl(urlToCheck, currentUrl, true);
    }

    function whenCurrentUrl(
        urlToCheck: NonNullable<InertiaLinkProps['href']>,
        ifTrue: any,
        ifFalse: any = null,
    ) {
        return isCurrentUrl(urlToCheck) ? ifTrue : ifFalse;
    }

    return {
        currentUrl: readonly(currentUrlReactive),
        isCurrentUrl,
        isCurrentOrParentUrl,
        whenCurrentUrl,
    };
}
