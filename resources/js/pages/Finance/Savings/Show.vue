<script setup lang="ts">
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { useCreateBack } from '@/composables/useCreateBack';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    savings: {
        id: number;
        account_number: string;
        account_status: string;
        current_balance: string;
        interest_rate: string;
        member?: { first_name?: string; last_name?: string };
    };
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: string;
            balance_after: string;
            recorded_at: string | null;
        }>;
    };
    totalInterestEarned: number;
    permissions: {
        can_edit: boolean;
        can_close: boolean;
        can_record_transaction: boolean;
        can_calculate_interest: boolean;
    };
}>();

const { goBack } = useCreateBack({ fallbackHref: '/finance/savings' });
const currentUrl = window.location.pathname + window.location.search;

const txForm = useForm({
    type: 'Deposit',
    amount: 0,
    remarks: '',
});

const interestForm = useForm({});

const submitTransaction = () => {
    txForm.post(`/finance/savings/${props.savings.id}/transactions`);
};

const calculateInterest = () => {
    interestForm.post(`/finance/savings/${props.savings.id}/calculate-interest`);
};
</script>

<template>
    <Head :title="`Finance - Savings ${savings.account_number}`" />

    <FinanceShellLayout active-tab="savings">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Savings Account {{ savings.account_number }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{ savings.member?.first_name }} {{ savings.member?.last_name }} | {{ savings.account_status }}
                </p>
            </div>
            <div class="flex gap-2">
                <Link v-if="permissions.can_edit" :href="currentUrl ? `/finance/savings/${savings.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/savings/${savings.id}/edit`" class="rounded-md border px-3 py-2 text-sm">Edit</Link>
                <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="goBack">Back</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border bg-card p-4">
                <div class="text-xs text-muted-foreground">Current Balance</div>
                <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(savings.current_balance) }}</div>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <div class="text-xs text-muted-foreground">Interest Rate</div>
                <div class="mt-1 text-lg font-semibold">{{ savings.interest_rate }}%</div>
            </div>
            <div class="rounded-lg border bg-card p-4">
                <div class="text-xs text-muted-foreground">Total Interest Earned</div>
                <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(totalInterestEarned) }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <form v-if="permissions.can_record_transaction" class="rounded-lg border bg-card p-4" @submit.prevent="submitTransaction">
                <h2 class="font-semibold">Record Transaction</h2>
                <select v-model="txForm.type" class="mt-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                    <option value="Deposit">Deposit</option>
                    <option value="Withdrawal">Withdrawal</option>
                </select>
                <input v-model.number="txForm.amount" type="number" step="0.01" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Amount" />
                <textarea v-model="txForm.remarks" rows="2" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Remarks"></textarea>
                <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="txForm.processing">Save Transaction</button>
            </form>

            <div class="rounded-lg border bg-card p-4">
                <h2 class="font-semibold">Interest Calculation</h2>
                <p class="mt-2 text-sm text-muted-foreground">Apply monthly interest manually for this account.</p>
                <button v-if="permissions.can_calculate_interest" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="interestForm.processing" @click="calculateInterest">
                    Calculate Interest
                </button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <div class="border-b px-4 py-3 text-sm font-semibold">Transactions</div>
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="px-4 py-2 text-left">Amount</th>
                        <th class="px-4 py-2 text-left">Balance After</th>
                        <th class="px-4 py-2 text-left">Recorded At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="transactions.data.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No transactions recorded.</td>
                    </tr>
                    <tr v-for="tx in transactions.data" :key="tx.id" class="border-t">
                        <td class="px-4 py-2">{{ tx.type }}</td>
                        <td class="px-4 py-2">{{ formatPhilippinePeso(tx.amount) }}</td>
                        <td class="px-4 py-2">{{ formatPhilippinePeso(tx.balance_after) }}</td>
                        <td class="px-4 py-2">{{ tx.recorded_at || '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
