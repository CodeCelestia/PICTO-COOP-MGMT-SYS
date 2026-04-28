<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type FinanceTabId = 'funding-sources' | 'financial-records' | 'loans' | 'savings' | 'reports';

const props = defineProps<{
    activeTab: FinanceTabId;
    hideTabs?: boolean;
}>();

const financeTabs: LiftedTab[] = [
    { id: 'funding-sources', label: 'Funding Sources' },
    { id: 'financial-records', label: 'Financial Records' },
    { id: 'loans', label: 'Loans' },
    { id: 'savings', label: 'Savings' },
    { id: 'reports', label: 'Reports' },
];

const tabHref: Record<FinanceTabId, string> = {
    'funding-sources': '/finance/funding-sources',
    'financial-records': '/finance/financial-records',
    loans: '/finance/loans',
    savings: '/finance/savings',
    reports: '/finance/reports/statements',
};

const tabHeader: Record<FinanceTabId, { title: string; subtitle: string }> = {
    'funding-sources': {
        title: 'Funding Sources',
        subtitle: 'Track and manage all sources of cooperative funds',
    },
    'financial-records': {
        title: 'Financial Records',
        subtitle: 'View and record all cooperative financial transactions',
    },
    loans: {
        title: 'Loans',
        subtitle: 'Manage member loan applications, approvals, and payments',
    },
    savings: {
        title: 'Savings',
        subtitle: 'Manage member savings accounts and transactions',
    },
    reports: {
        title: 'Financial Reports',
        subtitle: 'View financial summaries, trends, and export reports',
    },
};

const activeFinanceTab = ref<FinanceTabId>(props.activeTab);
const activeHeader = computed(() => tabHeader[activeFinanceTab.value]);

watch(
    () => props.activeTab,
    (tab) => {
        activeFinanceTab.value = tab;
    },
);

watch(activeFinanceTab, (tab) => {
    const href = tabHref[tab];
    if (href) {
        router.visit(href);
    }
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <Card class="border-border bg-card/95 shadow-sm">
                <CardContent class="space-y-4 p-4 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ activeHeader.title }}</h1>
                            <p class="text-sm text-muted-foreground">{{ activeHeader.subtitle }}</p>
                        </div>
                        <div v-if="$slots['header-actions']" class="flex items-center gap-2">
                            <slot name="header-actions" />
                        </div>
                    </div>

                    <div v-if="!hideTabs">
                        <LiftedTabs v-model="activeFinanceTab" :tabs="financeTabs" />
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border bg-card/95 shadow-sm">
                <CardContent class="p-4 sm:p-5">
                    <slot />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
