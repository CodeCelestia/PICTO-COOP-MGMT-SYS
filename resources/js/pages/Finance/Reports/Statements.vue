<script setup lang="ts">
import LiftedTabs, { type LiftedTab } from '@/components/LiftedTabs.vue';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type TrendRow = {
    period: string;
    total_amount: number | string;
};

type ReportTotals = {
    income?: number;
    expense?: number;
    grants?: number;
    loans?: number;
    assets?: number;
    liabilities?: number;
    net_surplus?: number;
    funders?: number;
    allocated?: number;
    released?: number;
    trend_rows?: TrendRow[];
};

const props = defineProps<{
    totals: ReportTotals;
    mode?: string;
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

const initialTab = (): string => {
    if (props.mode === 'funder-accountability') return 'funder-accountability';
    if (props.mode === 'trends') return 'trends';
    return 'statements';
};

const activeTab = ref(initialTab());

watch(activeTab, (tab) => {
    const href = tabHref[tab];
    if (href) {
        router.visit(href);
    }
});

const modeTitle = computed(() => {
    if (props.mode === 'funder-accountability') return 'Funder Accountability';
    if (props.mode === 'trends') return 'Trend Analysis';
    return 'Financial Statements';
});

const modeDescription = computed(() => {
    if (props.mode === 'funder-accountability') return 'Track committed and released support across funding partners.';
    if (props.mode === 'trends') return 'Monitor period-by-period financial movement trends.';
    return 'Income, expenses, grants, loans, assets, liabilities, and net surplus overview.';
});

const exportHref = computed(() => {
    if (props.mode === 'funder-accountability') return '/finance/reports/funder-accountability/export';
    if (props.mode === 'trends') return '/finance/reports/trends/export';
    return '/finance/reports/statements/export';
});

const exportLabel = computed(() => {
    if (props.mode === 'funder-accountability') return 'Export Funder Accountability';
    if (props.mode === 'trends') return 'Export Trend Analysis';
    return 'Export Statements';
});

const toNumber = (value: unknown): number => {
    const amount = Number(value ?? 0);
    return Number.isFinite(amount) ? amount : 0;
};

const formatPeso = (value: unknown): string => {
    return `₱ ${toNumber(value).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })}`;
};

const statementRows = computed(() => [
    { key: 'income', label: 'Income', value: toNumber(props.totals.income) },
    { key: 'expense', label: 'Expense', value: toNumber(props.totals.expense) },
    { key: 'grants', label: 'Grants', value: toNumber(props.totals.grants) },
    { key: 'loans', label: 'Loans', value: toNumber(props.totals.loans) },
    { key: 'assets', label: 'Assets', value: toNumber(props.totals.assets) },
    { key: 'liabilities', label: 'Liabilities', value: toNumber(props.totals.liabilities) },
]);

const netSurplus = computed(() => toNumber(props.totals.net_surplus));
const isStatementMode = computed(() => !props.mode);
const hasNoStatementData = computed(() => {
    if (!isStatementMode.value) return false;

    return statementRows.value.every((row) => row.value === 0) && netSurplus.value === 0;
});

const funderRows = computed(() => [
    { label: 'Total Funders', value: toNumber(props.totals.funders), isCount: true },
    { label: 'Total Allocated', value: toNumber(props.totals.allocated), isCount: false },
    { label: 'Total Released', value: toNumber(props.totals.released), isCount: false },
]);

const trendRows = computed<TrendRow[]>(() => {
    const rows = props.totals.trend_rows;
    return Array.isArray(rows) ? rows : [];
});
</script>

<template>
    <Head title="Finance - Statements" />

    <FinanceShellLayout active-tab="reports">
        <div class="space-y-4 rounded-xl border border-border bg-card p-4 sm:p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ modeTitle }}</h1>
                    <p class="text-sm text-muted-foreground">{{ modeDescription }}</p>
                </div>
                <Link
                    v-if="canExportReports"
                    :href="exportHref"
                    class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
                >
                    {{ exportLabel }}
                </Link>
            </div>

            <LiftedTabs v-model="activeTab" :tabs="reportTabs" />
        </div>

        <div
            v-if="hasNoStatementData"
            class="rounded-lg border border-border bg-muted/50 px-4 py-3 text-sm text-muted-foreground"
        >
            No financial data has been recorded yet. Start by adding financial records, loans, or savings transactions.
        </div>

        <div v-if="isStatementMode" class="rounded-lg border border-border bg-card">
            <div class="grid grid-cols-[1fr_auto] gap-2 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:px-6">
                <span>Category</span>
                <span>Amount</span>
            </div>
            <div class="border-t border-border">
                <div
                    v-for="row in statementRows"
                    :key="row.key"
                    class="grid grid-cols-[1fr_auto] items-center px-4 py-3 text-sm sm:px-6"
                >
                    <span class="text-foreground">{{ row.label }}</span>
                    <span class="font-medium text-foreground">{{ formatPeso(row.value) }}</span>
                </div>

                <div class="mx-4 border-t border-dashed border-border sm:mx-6" />

                <div
                    class="grid grid-cols-[1fr_auto] items-center px-4 py-3 text-sm font-semibold sm:px-6"
                    :class="{
                        'bg-emerald-50/70 text-emerald-700': netSurplus > 0,
                        'bg-rose-50/70 text-rose-700': netSurplus < 0,
                        'bg-muted/60 text-foreground': netSurplus === 0,
                    }"
                >
                    <span>Net Surplus</span>
                    <span>{{ formatPeso(netSurplus) }}</span>
                </div>
            </div>
        </div>

        <div v-else-if="props.mode === 'funder-accountability'" class="rounded-lg border border-border bg-card">
            <div class="grid grid-cols-[1fr_auto] gap-2 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:px-6">
                <span>Metric</span>
                <span>Value</span>
            </div>
            <div class="border-t border-border">
                <div
                    v-for="row in funderRows"
                    :key="row.label"
                    class="grid grid-cols-[1fr_auto] items-center px-4 py-3 text-sm sm:px-6"
                >
                    <span class="text-foreground">{{ row.label }}</span>
                    <span class="font-medium text-foreground">{{ row.isCount ? row.value : formatPeso(row.value) }}</span>
                </div>
            </div>
        </div>

        <div v-else class="space-y-3 rounded-lg border border-border bg-card p-4 sm:p-6">
            <div class="flex items-center justify-between border-b border-border pb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                <span>Period</span>
                <span>Total Amount</span>
            </div>

            <div v-if="trendRows.length === 0" class="rounded-md border border-border bg-muted/40 px-4 py-3 text-sm text-muted-foreground">
                No trend data available yet. Add financial records with period values to generate trend analysis.
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="row in trendRows"
                    :key="row.period"
                    class="grid grid-cols-[1fr_auto] items-center rounded-md border border-border/70 px-4 py-3 text-sm"
                >
                    <span class="font-medium text-foreground">{{ row.period }}</span>
                    <span class="font-semibold text-foreground">{{ formatPeso(row.total_amount) }}</span>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
