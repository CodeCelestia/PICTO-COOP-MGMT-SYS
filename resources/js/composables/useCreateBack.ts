import { computed, unref, type MaybeRef } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

interface CreateBackOptions {
    fallbackHref: string;
    cooperativeId?: MaybeRef<string | number | null | undefined>;
    cooperativeTab?: string;
}

const normalizeReturnTo = (value: string | null): string => {
    if (!value) return '';

    if (!value.startsWith('/') || value.startsWith('//')) return '';

    return value;
};

export function useCreateBack(options: CreateBackOptions) {
    const page = usePage();

    const queryParams = computed(() => {
        const [, queryString = ''] = (page.url || '').split('?');
        return new URLSearchParams(queryString);
    });

    const returnToHref = computed(() => normalizeReturnTo(queryParams.value.get('return_to')));

    const cooperativeId = computed(() => {
        const value = unref(options.cooperativeId);
        if (value === null || value === undefined || value === '') return '';
        return String(value);
    });

    const backHref = computed(() => {
        if (returnToHref.value) return returnToHref.value;

        if (options.cooperativeTab && cooperativeId.value) {
            return `/cooperatives/${cooperativeId.value}?tab=${options.cooperativeTab}`;
        }

        return options.fallbackHref;
    });

    const goBack = () => {
        router.get(backHref.value);
    };

    return {
        backHref,
        goBack,
        returnToHref,
    };
}
