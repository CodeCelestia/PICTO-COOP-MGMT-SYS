<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type FinanceTabId = 'funding-sources' | 'financial-records' | 'loans' | 'savings' | 'external-supports' | 'reports';

const props = defineProps<{
    activeTab: FinanceTabId;
    hideTabs?: boolean;
}>();

const financeTabs: LiftedTab[] = [
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
        'funding-sources': '/finance/funding-sources',
        'financial-records': '/finance/financial-records',
        loans: '/finance/loans',
        savings: '/finance/savings',
        'external-supports': '/finance/external-supports',
        reports: '/finance/reports/statements',
    };

    const coopTabHref: Record<FinanceTabId, string> = {
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
        <div class="space-y-6 p-4 sm:p-6">
            <div v-if="!hideTabs">
                <LiftedTabs v-model="activeFinanceTab" :tabs="financeTabs" />
            </div>

            <div>
                <slot />
            </div>
        </div>
    </AppLayout>
</template>
