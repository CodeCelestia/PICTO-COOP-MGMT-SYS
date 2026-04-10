<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
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
</script>

<template>
    <Head title="Finance - Financial Records" />

    <FinanceShellLayout active-tab="financial-records">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Financial Records</h1>
                <p class="text-sm text-muted-foreground">Ledger entries and health metric references.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/financial-records/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                New Ledger Entry
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Period</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Cooperative</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="7">No financial records found.</td>
                    </tr>
                    <tr v-for="record in records.data" :key="record.id" class="border-t">
                        <td class="px-4 py-3">{{ record.period }}</td>
                        <td class="px-4 py-3">{{ record.type }}</td>
                        <td class="px-4 py-3">{{ record.cooperative?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ record.source || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatAmount(record.amount) }}</td>
                        <td class="px-4 py-3">{{ record.date_recorded || 'N/A' }}</td>
                        <td class="px-4 py-3">
                            <Link :href="`/finance/financial-records/${record.id}`" class="text-primary hover:underline">Open</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
