<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    loan: {
        id: number;
        status: string;
        principal: string;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        created_at: string | null;
    };
    repaymentSchedule: Array<{
        id: number;
        payment_number: number | null;
        due_date: string | null;
        total_due: string | null;
        status: string;
    }>;
    remainingBalance: number;
}>();

const formatAmount = (value: string | number) => {
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="`My Loan #${props.loan.id}`" />

    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Loan #{{ props.loan.id }}</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Status: {{ props.loan.status }}
                    </p>
                </div>
                <Link href="/member-portal/loans" class="rounded-md border px-3 py-2 text-sm">Back</Link>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border bg-white p-4 text-sm shadow-sm">
                    <div class="text-gray-500">Principal</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatAmount(props.loan.principal) }}</div>
                </div>
                <div class="rounded-lg border bg-white p-4 text-sm shadow-sm">
                    <div class="text-gray-500">Interest Rate</div>
                    <div class="mt-1 text-lg font-semibold">{{ props.loan.interest_rate }}%</div>
                </div>
                <div class="rounded-lg border bg-white p-4 text-sm shadow-sm">
                    <div class="text-gray-500">Term</div>
                    <div class="mt-1 text-lg font-semibold">{{ props.loan.term_months }} months</div>
                </div>
                <div class="rounded-lg border bg-white p-4 text-sm shadow-sm">
                    <div class="text-gray-500">Remaining Balance</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatAmount(props.remainingBalance) }}</div>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-lg border bg-white">
                <div class="border-b px-4 py-3 text-sm font-semibold">Repayment Schedule</div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Due Date</th>
                            <th class="px-4 py-2 text-left">Amount</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="props.repaymentSchedule.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">No schedule generated yet.</td>
                        </tr>
                        <tr v-for="row in props.repaymentSchedule" :key="row.id" class="border-t">
                            <td class="px-4 py-2">{{ row.payment_number || '-' }}</td>
                            <td class="px-4 py-2">{{ row.due_date || '-' }}</td>
                            <td class="px-4 py-2">{{ row.total_due || '-' }}</td>
                            <td class="px-4 py-2">{{ row.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
