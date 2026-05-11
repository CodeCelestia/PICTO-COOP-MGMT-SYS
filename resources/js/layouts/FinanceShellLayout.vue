<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type FinanceTabId = 'overview' | 'funding-sources' | 'financial-records' | 'loans' | 'savings' | 'external-supports' | 'reports';

const props = defineProps<{
    activeTab: FinanceTabId;
    hideTabs?: boolean;
}>();

const financeTabs: LiftedTab[] = [
    { id: 'overview', label: 'Overview' },
    { id: 'funding-sources', label: 'Funding Sources' },
    { id: 'financial-records', label: 'Financial Records' },
    { id: 'loans', label: 'Loans' },
    { id: 'savings', label: 'Savings' },
    { id: 'external-supports', label: 'External Support' },
    { id: 'reports', label: 'Reports' },
];

// Detect coop context from URL path
const isFromCoopContext = computed(() =>
    window.location.pathname.startsWith('/cooperatives/')
);

const coopSlugFromUrl = computed(() => {
    const match = window.location.pathname.match(/^\/cooperatives\/([^\/]+)/);
    return match ? match[1] : null;
});

const getTabHref = (tabId: FinanceTabId): string => {
    const globalTabHref: Record<FinanceTabId, string> = {
        overview: '/finance',
        'funding-sources': '/finance/funding-sources',
        'financial-records': '/finance/financial-records',
        loans: '/finance/loans',
        savings: '/finance/savings',
        'external-supports': '/finance/external-supports',
        reports: '/finance/reports/statements',
    };

    const coopTabHref: Record<FinanceTabId, string> = {
        overview: '/finance',
        'funding-sources': `/cooperatives/${coopSlugFromUrl.value}/finance/funding-sources`,
        'financial-records': `/cooperatives/${coopSlugFromUrl.value}/finance/financial-records`,
        loans: `/cooperatives/${coopSlugFromUrl.value}/finance/loans`,
        savings: `/cooperatives/${coopSlugFromUrl.value}/finance/savings`,
        'external-supports': `/cooperatives/${coopSlugFromUrl.value}/finance/external-supports`,
        reports: '/finance/reports/statements',
    };

    return isFromCoopContext.value && coopSlugFromUrl.value
        ? coopTabHref[tabId]
        : globalTabHref[tabId];
};

const activeFinanceTab = ref<FinanceTabId>(props.activeTab);
const page = usePage();

const financeScopeLabel = computed(() => {
    const pageProps = page.props as {
        scopeLabel?: string;
        cooperative?: { name?: string } | null;
    };

    if (pageProps.scopeLabel) {
        return pageProps.scopeLabel;
    }

    if (pageProps.cooperative?.name) {
        return pageProps.cooperative.name;
    }

    return 'all cooperatives';
});

const financeSubtitle = computed(() => `Finance records scoped to ${financeScopeLabel.value}.`);

watch(
    () => props.activeTab,
    (tab) => {
        activeFinanceTab.value = tab;
    },
);

watch(activeFinanceTab, (tab) => {
    const href = getTabHref(tab);
    if (href) {
        router.visit(href);
    }
});
</script>

<template>
    <AppLayout>
        <div class="mx-auto w-full max-w-full space-y-6 px-4 py-4 sm:px-6 lg:px-8">
            <header class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                    Finance
                </h1>
                <p class="text-sm text-muted-foreground sm:text-base">
                    {{ financeSubtitle }}
                </p>
            </header>

            <div v-if="!hideTabs" class="overflow-x-auto whitespace-nowrap rounded-2xl border border-border bg-card/95 p-2 shadow-sm">
                <LiftedTabs v-model="activeFinanceTab" :tabs="financeTabs" />
            </div>

            <div class="rounded-2xl border border-border bg-card/80 p-4 shadow-sm sm:p-5 lg:p-6">
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
