<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import { Button } from '@/components/ui/button';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Download } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    summary: {
        total_accounts: number;
        active_accounts: number;
        total_balance: number;
        total_deposits: number;
        total_withdrawals: number;
        total_interest_credited: number;
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

const activeTab = ref('savings-summary');

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

const hasNoSavingsData = computed(() => {
    return (
        Number(props.summary.total_accounts ?? 0) === 0
        && Number(props.summary.active_accounts ?? 0) === 0
        && Number(props.summary.total_balance ?? 0) === 0
        && Number(props.summary.total_deposits ?? 0) === 0
        && Number(props.summary.total_withdrawals ?? 0) === 0
        && Number(props.summary.total_interest_credited ?? 0) === 0
    );
});
</script>

<template>
    <Head title="Finance - Savings Summary" />

    <FinanceShellLayout active-tab="reports">
        <div class="space-y-4 rounded-xl border border-border bg-card p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Savings Summary</h1>
                    <p class="text-sm text-muted-foreground">Deposits, withdrawals, and interest overview.</p>
                </div>
                <Link
                    v-if="canExportReports"
                    href="/finance/reports/savings-summary/export"
                >
                    <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                        <Download class="h-4 w-4" />
                        Export Savings Summary
                    </Button>
                </Link>
            </div>

            <LiftedTabs v-model="activeTab" :tabs="reportTabs" />
        </div>

        <div
            v-if="hasNoSavingsData"
            class="mt-6 rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground"
        >
            No savings data has been recorded yet. Start by creating savings accounts and posting transactions.
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Accounts</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.total_accounts }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Active Accounts</div>
                <div class="mt-1 text-xl font-semibold">{{ summary.active_accounts }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Balance</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.total_balance) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Deposits</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.total_deposits) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Withdrawals</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.total_withdrawals) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-card p-4">
                <div class="text-xs text-muted-foreground">Interest Credited</div>
                <div class="mt-1 text-xl font-semibold">{{ formatPeso(summary.total_interest_credited) }}</div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
