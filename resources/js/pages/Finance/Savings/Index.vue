<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SavingsRow {
    id: number;
    account_number: string;
    account_status: string;
    current_balance: string;
    interest_rate: string;
    member?: { first_name?: string; last_name?: string };
}

const props = defineProps<{
    savings: { data: SavingsRow[] };
    accountStatuses: string[];
    filters?: { status?: string };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_close: boolean;
        can_record_transaction: boolean;
        can_calculate_interest: boolean;
    };
}>();

const status = ref(props.filters?.status || '');

const applyFilter = () => {
    router.get('/finance/savings', {
        status: status.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const closeAccount = (savingsId: number) => {
    if (!props.permissions.can_close) {
        return;
    }

    if (!window.confirm('Are you sure you want to close this savings account?')) {
        return;
    }

    router.delete(`/finance/savings/${savingsId}`);
};
</script>

<template>
    <Head title="Finance - Savings" />

    <FinanceShellLayout active-tab="savings">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Savings Accounts</h1>
                <p class="text-sm text-muted-foreground">Manage member savings accounts, balances, and deposit or withdrawal activity in one place.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/savings/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                Open Savings Account
            </Link>
        </div>

        <div class="rounded-lg border bg-card p-4">
            <div class="flex items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                    <select v-model="status" class="rounded-md border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="item in accountStatuses" :key="item" :value="item">{{ item }}</option>
                    </select>
                </div>
                <button class="rounded-md border px-3 py-2 text-sm" @click="applyFilter">Apply</button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3 text-left">Balance</th>
                        <th class="px-4 py-3 text-left">Interest</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="savings.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center">
                            <div class="space-y-1">
                                <p class="font-medium text-foreground">No savings accounts yet</p>
                                <p class="text-sm text-muted-foreground">Open a savings account for an active member to start tracking deposits, withdrawals, and interest.</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-for="row in savings.data" :key="row.id" class="border-t">
                        <td class="px-4 py-3">{{ row.account_number }}</td>
                        <td class="px-4 py-3">{{ row.member?.first_name }} {{ row.member?.last_name }}</td>
                        <td class="px-4 py-3">{{ row.current_balance }}</td>
                        <td class="px-4 py-3">{{ row.interest_rate }}%</td>
                        <td class="px-4 py-3">{{ row.account_status }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="`/finance/savings/${row.id}`" class="text-primary hover:underline">View</Link>
                                <Link v-if="permissions.can_edit" :href="`/finance/savings/${row.id}/edit`" class="text-primary hover:underline">Edit</Link>
                                <button
                                    v-if="permissions.can_close && row.account_status !== 'Closed'"
                                    type="button"
                                    class="text-red-600 hover:underline"
                                    @click="closeAccount(row.id)"
                                >
                                    Close
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
