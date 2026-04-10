<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    summary: {
        total_loans: number;
        pending: number;
        active: number;
        completed: number;
        defaulted: number;
        principal_total: number;
        outstanding_balance: number;
    };
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canExportReports = computed(() => permissions.value.includes('export finance-reports'));

const reportTabs: LiftedTab[] = [
    { id: 'statements', label: 'Financial Statements' },
    { id: 'loan-portfolio', label: 'Loan Portfolio' },
    { id: 'savings-summary', label: 'Savings Summary' },
    { id: 'funder-accountability', label: 'Funder Accountability' },
    { id: 'trends', label: 'Trend Analysis' },
];

const tabHref: Record<string, string> = {
    statements: '/finance/reports/statements',
    'loan-portfolio': '/finance/reports/loan-portfolio',
    'savings-summary': '/finance/reports/savings-summary',
    'funder-accountability': '/finance/reports/funder-accountability',
    trends: '/finance/reports/trends',
};

const activeTab = ref('loan-portfolio');

watch(activeTab, (tab) => {
    const href = tabHref[tab];
    if (href) {
        router.visit(href);
    }
});

const formatPeso = (value: number | null | undefined): string => {
    const amount = Number(value ?? 0);
    const safeAmount = Number.isFinite(amount) ? amount : 0;
    return `₱ ${safeAmount.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const hasNoLoanData = computed(() => {
    return (
        Number(props.summary.total_loans ?? 0) === 0
        && Number(props.summary.pending ?? 0) === 0
        && Number(props.summary.active ?? 0) === 0
        && Number(props.summary.completed ?? 0) === 0
        && Number(props.summary.defaulted ?? 0) === 0
        && Number(props.summary.principal_total ?? 0) === 0
        && Number(props.summary.outstanding_balance ?? 0) === 0
    );
});
</script>

<template>
    <Head title="Finance - Loan Portfolio" />

    <FinanceShellLayout active-tab="reports">
        <div class="space-y-4 rounded-xl border border-border bg-card p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Loan Portfolio</h1>
                    <p class="text-sm text-muted-foreground">Delinquencies, balances, and status distribution.</p>
                </div>
                <Link
                    v-if="canExportReports"
                    href="/finance/reports/loan-portfolio/export"
                    class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                >
                    Export Loan Portfolio
                </Link>
            </div>

            <LiftedTabs v-model="activeTab" :tabs="reportTabs" />
        </div>

        <div
            v-if="hasNoLoanData"
            class="rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground"
        >
            No loan data has been recorded yet. Start by creating and processing member loans to populate this report.
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Loans</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.total_loans }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Pending</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.pending }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Active</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.active }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Completed</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.completed }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Defaulted</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.defaulted }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 md:col-span-2">
                <div class="text-xs text-muted-foreground">Principal Total</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.principal_total) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4 md:col-span-2">
                <div class="text-xs text-muted-foreground">Outstanding Balance</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.outstanding_balance) }}</div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
