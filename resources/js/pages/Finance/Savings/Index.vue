<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, XCircle } from 'lucide-vue-next';
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

const currentUrl = window.location.pathname + window.location.search;

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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Savings Accounts</h1>
                <p class="text-sm text-muted-foreground">Manage member savings accounts, balances, and deposit or withdrawal activity in one place.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/savings/create">
                <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                    <Plus class="h-4 w-4" />
                    Open Savings Account
                </Button>
            </Link>
        </div>

        <div class="mt-6 rounded-lg border bg-card p-4">
            <div class="flex items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                    <select v-model="status" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="">All</option>
                        <option v-for="item in accountStatuses" :key="item" :value="item">{{ item }}</option>
                    </select>
                </div>
                <Button variant="outline" class="gap-2" @click="applyFilter">
                    <Filter class="h-4 w-4" />
                    Apply
                </Button>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3 text-left">Balance</th>
                        <th class="px-4 py-3 text-left">Interest</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
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
                        <td class="px-4 py-3">{{ formatPhilippinePeso(row.current_balance) }}</td>
                        <td class="px-4 py-3">{{ row.interest_rate }}%</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(row.account_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ row.account_status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="currentUrl ? `/finance/savings/${row.id}?return_to=${encodeURIComponent(currentUrl)}` : `/finance/savings/${row.id}`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="permissions.can_edit" :href="currentUrl ? `/finance/savings/${row.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/savings/${row.id}/edit`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    v-if="permissions.can_close && row.account_status !== 'Closed'"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                    @click="closeAccount(row.id)"
                                >
                                    <XCircle class="h-4 w-4" />
                                    Close
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
