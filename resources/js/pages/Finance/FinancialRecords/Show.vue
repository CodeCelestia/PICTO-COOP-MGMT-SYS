<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
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
                <div class="flex items-center gap-2">
                    <Link
                        v-if="permissions.can_edit"
                        :href="`/finance/financial-records/${record.id}/edit`"
                        class="rounded-md border px-3 py-2 text-sm"
                    >
                        Edit
                    </Link>
                    <Link href="/finance/financial-records" class="rounded-md border px-3 py-2 text-sm">Back</Link>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Type</div>
                    <div class="mt-1 text-sm font-medium">{{ formatTypeLabel(record.type) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Amount</div>
                    <div class="mt-1 text-sm font-medium">{{ formatAmount(record.amount) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Cooperative</div>
                    <div class="mt-1 text-sm font-medium">{{ record.cooperative?.name || 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Date Recorded</div>
                    <div class="mt-1 text-sm font-medium">{{ formatDate(record.date_recorded) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Period</div>
                    <div class="mt-1 text-sm font-medium">{{ periodLabel(record.period) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Source</div>
                    <div class="mt-1 text-sm font-medium">{{ record.source ? formatTypeLabel(record.source) : 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 md:col-span-2">
                    <div class="text-xs text-muted-foreground">Description</div>
                    <div class="mt-1 text-sm font-medium">{{ record.purpose || 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Reference</div>
                    <div class="mt-1 text-sm font-medium">{{ record.reference_doc || 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4">
                    <div class="text-xs text-muted-foreground">Recorded By</div>
                    <div class="mt-1 text-sm font-medium">{{ record.recorded_by || 'N/A' }}</div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
