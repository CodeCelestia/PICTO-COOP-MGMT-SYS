<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    loan: {
        id: number;
        status: string;
        principal: string;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        created_at?: string | null;
        approved_at?: string | null;
        disbursement_date?: string | null;
        member?: { first_name?: string; last_name?: string };
        loan_type?: { name?: string };
    };
    repaymentSchedule: Array<{
        id: number;
        payment_number: number | null;
        due_date: string | null;
        total_due: string | null;
        status: string;
    }>;
    remainingBalance: number;
    permissions: {
        can_approve: boolean;
        can_disburse: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_record_payment: boolean;
    };
}>();

const approveForm = useForm({ remarks: '' });
const disburseForm = useForm({ amount: Number(props.loan.principal), disbursement_method: 'cash', remarks: '' });
const paymentForm = useForm({ amount: 0, paid_at: '', remarks: '' });

const approve = () => approveForm.post(`/finance/loans/${props.loan.id}/approve`);
const disburse = () => disburseForm.post(`/finance/loans/${props.loan.id}/disburse`);
const recordPayment = () => paymentForm.post(`/finance/loans/${props.loan.id}/payments`);

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const formatAmount = (value: string | number | null | undefined) => {
    if (value === null || value === undefined || value === '') return '0.00';
    const num = Number(value);
    if (Number.isNaN(num)) return String(value);
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head :title="`Finance - ${loan.member?.first_name || ''} ${loan.member?.last_name || ''} - Loan #${memberLoanCount}`" />

    <FinanceShellLayout active-tab="loans">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ loan.member?.first_name }} {{ loan.member?.last_name }} - Loan #{{ memberLoanCount }}</h1>
                <p class="text-sm text-muted-foreground">
                    Loan ID #{{ loan.id }} | {{ loan.status }}
                </p>
            </div>
            <div class="flex gap-2">
                <Link v-if="permissions.can_edit" :href="`/finance/loans/${loan.id}/edit`" class="rounded-md border px-3 py-2 text-sm">Edit</Link>
                <Link href="/finance/loans" class="rounded-md border px-3 py-2 text-sm">Back</Link>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Loan Amount</div>
                <div class="mt-1 text-lg font-semibold">{{ formatAmount(loan.principal) }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Loan Type</div>
                <div class="mt-1 text-lg font-semibold">{{ loan.loan_type?.name || 'N/A' }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Purpose</div>
                <div class="mt-1 text-lg font-semibold">{{ loan.purpose || 'N/A' }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Status</div>
                <div class="mt-1 text-lg font-semibold">{{ loan.status }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Remaining Balance</div>
                <div class="mt-1 text-lg font-semibold">{{ formatAmount(remainingBalance) }}</div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Created</div>
                <div class="mt-1 font-semibold">{{ formatDate(loan.created_at) }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Approved</div>
                <div class="mt-1 font-semibold">{{ formatDate(loan.approved_at) }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <div class="text-muted-foreground">Disbursed</div>
                <div class="mt-1 font-semibold">{{ formatDate(loan.disbursement_date) }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <form v-if="permissions.can_approve" class="rounded-lg border bg-card p-4" @submit.prevent="approve">
                <h2 class="font-semibold">Approve Loan</h2>
                <textarea v-model="approveForm.remarks" rows="3" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Approval remarks"></textarea>
                <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="approveForm.processing">Approve</button>
            </form>

            <form v-if="permissions.can_disburse" class="rounded-lg border bg-card p-4" @submit.prevent="disburse">
                <h2 class="font-semibold">Disburse</h2>
                <input v-model.number="disburseForm.amount" type="number" step="0.01" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Amount" />
                <select v-model="disburseForm.disbursement_method" class="mt-3 w-full rounded-md border px-3 py-2 text-sm">
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
                <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="disburseForm.processing">Disburse</button>
            </form>

            <form v-if="permissions.can_record_payment" class="rounded-lg border bg-card p-4" @submit.prevent="recordPayment">
                <h2 class="font-semibold">Record Payment</h2>
                <input v-model.number="paymentForm.amount" type="number" step="0.01" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Amount" />
                <input v-model="paymentForm.paid_at" type="date" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="paymentForm.processing">Save Payment</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <div class="border-b px-4 py-3 text-sm font-semibold">Repayment Schedule</div>
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Due Date</th>
                        <th class="px-4 py-2 text-left">Amount</th>
                        <th class="px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="repaymentSchedule.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No schedule generated yet.</td>
                    </tr>
                    <tr v-for="row in repaymentSchedule" :key="row.id" class="border-t">
                        <td class="px-4 py-2">{{ row.payment_number || '-' }}</td>
                        <td class="px-4 py-2">{{ formatDate(row.due_date) }}</td>
                        <td class="px-4 py-2">{{ row.total_due || '-' }}</td>
                        <td class="px-4 py-2">{{ row.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
