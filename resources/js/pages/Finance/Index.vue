<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CreditCard,
    FileText,
    HandCoins,
    PiggyBank,
    TrendingUp,
} from 'lucide-vue-next';

type Summary = {
    loans: {
        total: number;
        pending: number;
        active: number;
        completed: number;
        principal_total: number;
    };
    savings: {
        total_accounts: number;
        active_accounts: number;
        total_balance: number;
    };
    financial_records: {
        total: number;
        income: number;
        expense: number;
        net_surplus: number;
    };
    funding_sources: {
        total: number;
        allocated: number;
        released: number;
    };
    external_supports: {
        total: number;
        amount: number;
    };
};

const props = defineProps<{
    scopeLabel: string;
    summary: Summary;
}>();

const formatAmount = (value: number) => formatPhilippinePeso(value);

const sectionCards = [
    {
        title: 'Loans',
        description: 'Track member loans, approvals, disbursements, and payments.',
        href: '/finance/loans',
        icon: CreditCard,
        accent: 'from-slate-900 to-slate-700',
        stats: [
            { label: 'Total', value: props.summary.loans.total },
            { label: 'Pending', value: props.summary.loans.pending },
            { label: 'Active', value: props.summary.loans.active },
        ],
        amountLabel: 'Principal Portfolio',
        amountValue: formatAmount(props.summary.loans.principal_total),
    },
    {
        title: 'Savings',
        description: 'Open accounts, deposit activity, withdrawals, and interest flow.',
        href: '/finance/savings',
        icon: PiggyBank,
        accent: 'from-emerald-700 to-teal-600',
        stats: [
            { label: 'Accounts', value: props.summary.savings.total_accounts },
            { label: 'Active', value: props.summary.savings.active_accounts },
            { label: 'Balance', value: formatAmount(props.summary.savings.total_balance) },
        ],
        amountLabel: 'Total Savings Balance',
        amountValue: formatAmount(props.summary.savings.total_balance),
    },
    {
        title: 'Financial Records',
        description: 'See the ledger for income, expenses, and net surplus.',
        href: '/finance/financial-records',
        icon: FileText,
        accent: 'from-indigo-700 to-violet-600',
        stats: [
            { label: 'Entries', value: props.summary.financial_records.total },
            { label: 'Income', value: formatAmount(props.summary.financial_records.income) },
            { label: 'Expense', value: formatAmount(props.summary.financial_records.expense) },
        ],
        amountLabel: 'Net Surplus',
        amountValue: formatAmount(props.summary.financial_records.net_surplus),
    },
    {
        title: 'Funding Sources',
        description: 'Monitor funding partners, allocations, and releases.',
        href: '/finance/funding-sources',
        icon: HandCoins,
        accent: 'from-amber-700 to-orange-600',
        stats: [
            { label: 'Sources', value: props.summary.funding_sources.total },
            { label: 'Allocated', value: formatAmount(props.summary.funding_sources.allocated) },
            { label: 'Released', value: formatAmount(props.summary.funding_sources.released) },
        ],
        amountLabel: 'Released Amount',
        amountValue: formatAmount(props.summary.funding_sources.released),
    },
    {
        title: 'External Supports',
        description: 'Track grants and assistance from external providers.',
        href: '/finance/external-supports',
        icon: Building2,
        accent: 'from-rose-700 to-pink-600',
        stats: [
            { label: 'Records', value: props.summary.external_supports.total },
            { label: 'Total Amount', value: formatAmount(props.summary.external_supports.amount) },
        ],
        amountLabel: 'Support Amount',
        amountValue: formatAmount(props.summary.external_supports.amount),
    },
    {
        title: 'Reports',
        description: 'Open statements, portfolio summaries, and trend analysis.',
        href: '/finance/reports/statements',
        icon: TrendingUp,
        accent: 'from-cyan-700 to-sky-600',
        stats: [
            { label: 'Overview', value: 'Statements' },
            { label: 'Portfolio', value: 'Loans + Savings' },
            { label: 'Trend', value: 'Reports' },
        ],
        amountLabel: 'Report Hub',
        amountValue: 'Available',
    },
];
</script>

<template>
    <Head title="Finance" />

    <FinanceShellLayout active-tab="overview">
        <div class="space-y-6">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="card in sectionCards" :key="card.title" class="overflow-hidden border-border shadow-sm">
                    <CardHeader class="space-y-4">
                        <div :class="['flex h-12 w-12 items-center justify-center rounded-xl bg-linear-to-br text-white shadow-sm', card.accent]">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-xl">{{ card.title }}</CardTitle>
                            <CardDescription class="mt-1 text-sm">
                                {{ card.description }}
                            </CardDescription>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div v-for="stat in card.stats" :key="stat.label" class="rounded-xl border border-border bg-muted/40 px-3 py-2">
                                <p class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                                    {{ stat.label }}
                                </p>
                                <p class="mt-1 text-sm font-semibold text-foreground">
                                    {{ stat.value }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 rounded-xl bg-background px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">
                                    {{ card.amountLabel }}
                                </p>
                                <p class="text-lg font-semibold text-foreground">
                                    {{ card.amountValue }}
                                </p>
                            </div>

                            <Link :href="card.href" class="w-full sm:w-auto">
                                <Button class="w-full gap-2 bg-foreground text-background hover:bg-foreground/90 sm:w-auto">
                                    Open
                                    <ArrowRight class="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </FinanceShellLayout>
</template>