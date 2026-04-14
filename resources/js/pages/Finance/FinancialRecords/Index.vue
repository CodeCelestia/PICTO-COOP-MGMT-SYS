<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    cooperative?: { name?: string };
}

defineProps<{
    records: {
        data: FinancialRecord[];
    };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
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

const recordDescription = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    if (description !== '') {
        return description;
    }

    return formatTypeLabel(record.type);
};

const deleteRecord = (recordId: number) => {
    if (!window.confirm('Are you sure you want to delete this financial record?')) {
        return;
    }

    router.delete(`/finance/financial-records/${recordId}`);
};
</script>

<template>
    <Head title="Finance - Financial Records" />

    <FinanceShellLayout active-tab="financial-records">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Financial Records</h1>
                <p class="text-sm text-muted-foreground">Loan and savings records are posted automatically. Use manual entries here for other finance transactions.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/financial-records/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                Add Manual Entry
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Record</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Cooperative</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="7">No financial records found.</td>
                    </tr>
                    <tr v-for="record in records.data" :key="record.id" class="border-t">
                        <td class="px-4 py-3">{{ recordDescription(record) }}</td>
                        <td class="px-4 py-3">{{ formatTypeLabel(record.type) }}</td>
                        <td class="px-4 py-3">{{ record.cooperative?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ record.source ? formatTypeLabel(record.source) : 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatAmount(record.amount) }}</td>
                        <td class="px-4 py-3">{{ formatDate(record.date_recorded) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="`/finance/financial-records/${record.id}`" class="text-primary hover:underline">View</Link>
                                <Link
                                    v-if="permissions.can_edit"
                                    :href="`/finance/financial-records/${record.id}/edit`"
                                    class="text-primary hover:underline"
                                >
                                    Edit
                                </Link>
                                <button
                                    v-if="permissions.can_delete"
                                    type="button"
                                    class="text-red-600 hover:underline"
                                    @click="deleteRecord(record.id)"
                                >
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
