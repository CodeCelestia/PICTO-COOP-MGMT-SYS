<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Separator } from '@/components/ui/separator';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    reference_doc: string | null;
    recorded_by: string | null;
    created_at: string | null;
    updated_at: string | null;
    cooperative?: { name?: string };
}

defineProps<{
    record: FinancialRecord;
    permissions: {
        can_edit: boolean;
    };
}>();

const formatAmount = (value: string | null) => {
    if (!value) return '0.00';
    const num = Number(value);
    if (Number.isNaN(num)) return value;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const periodLabel = (value: string | null | undefined) => {
    if (!value) return 'N/A';

    if (/^\d{4}-\d{2}$/.test(value)) {
        const [yearText, monthText] = value.split('-');
        const year = Number(yearText);
        const month = Number(monthText) - 1;
        if (!Number.isNaN(year) && !Number.isNaN(month) && month >= 0 && month <= 11) {
            return new Date(year, month, 1).toLocaleDateString('en-US', {
                month: 'long',
                year: 'numeric',
            });
        }
    }

    return value;
};

const displayTitle = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    if (description !== '') {
        return description;
    }

    return `Financial Record #${record.id}`;
};
</script>

<template>
    <Head :title="`Finance - ${displayTitle(record)}`" />

    <FinanceShellLayout active-tab="financial-records">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ displayTitle(record) }}</h1>
                    <p class="text-sm text-muted-foreground">Read-only ledger entry details.</p>
                </div>
                <Link href="/finance/financial-records" class="rounded-md border px-3 py-2 text-sm">Back</Link>
            </div>

            <div class="space-y-6">
                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-muted-foreground">Basic Information</h2>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Type</p>
                            <p class="text-base font-medium text-foreground">{{ formatTypeLabel(record.type) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Amount</p>
                            <p class="text-base font-medium text-foreground">{{ formatAmount(record.amount) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Cooperative</p>
                            <p class="text-base font-medium text-foreground">{{ record.cooperative?.name || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Date Recorded</p>
                            <p class="text-base font-medium text-foreground">{{ formatDate(record.date_recorded) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Period</p>
                            <p class="text-base font-medium text-foreground">{{ periodLabel(record.period) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Source</p>
                            <p class="text-base font-medium text-foreground">{{ record.source ? formatTypeLabel(record.source) : 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-muted-foreground">Record Details</h2>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5 md:col-span-2">
                            <p class="text-sm text-muted-foreground">Description</p>
                            <p class="text-base font-medium text-foreground">{{ record.purpose || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Reference</p>
                            <p class="text-base font-medium text-foreground">{{ record.reference_doc || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Recorded By</p>
                            <p class="text-base font-medium text-foreground">{{ record.recorded_by || 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
