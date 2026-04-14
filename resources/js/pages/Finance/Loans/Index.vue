<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Loan {
    id: number;
    purpose: string | null;
    status: string;
    created_at: string;
    member?: { first_name?: string; last_name?: string };
    loan_type?: { name?: string };
}

const props = defineProps<{
    loans: {
        data: Loan[];
    };
    statuses: string[];
    filters?: {
        status?: string;
    };
    permissions: {
        can_create: boolean;
        can_approve: boolean;
        can_disburse: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_record_payment: boolean;
    };
}>();

const selectedStatus = ref(props.filters?.status || '');

const applyFilter = () => {
    router.get('/finance/loans', {
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const deleteLoan = (loanId: number) => {
    if (!props.permissions.can_delete) {
        return;
    }

    if (!window.confirm('Are you sure you want to delete this loan?')) {
        return;
    }

    router.delete(`/finance/loans/${loanId}`);
};

</script>

<template>
    <Head title="Finance - Loans" />

    <FinanceShellLayout active-tab="loans">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Member Loans</h1>
                <p class="text-sm text-muted-foreground">Apply, approve, disburse, and monitor loan lifecycle.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/loans/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                New Loan
            </Link>
        </div>

        <div class="rounded-lg border bg-card p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                    <select v-model="selectedStatus" class="rounded-md border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                    </select>
                </div>
                <button class="rounded-md border px-3 py-2 text-sm" @click="applyFilter">Apply</button>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3 text-left">Loan Type</th>
                        <th class="px-4 py-3 text-left">Purpose</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loans.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No loans found.</td>
                    </tr>
                    <tr v-for="loan in loans.data" :key="loan.id" class="border-t">
                        <td class="px-4 py-3">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</td>
                        <td class="px-4 py-3">{{ loan.loan_type?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ loan.purpose || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ loan.status }}</td>
                        <td class="px-4 py-3">{{ formatDate(loan.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Link :href="`/finance/loans/${loan.id}`" class="text-primary hover:underline">View</Link>
                                <Link v-if="permissions.can_edit" :href="`/finance/loans/${loan.id}/edit`" class="text-primary hover:underline">Edit</Link>
                                <button
                                    v-if="permissions.can_delete"
                                    type="button"
                                    class="text-red-600 hover:underline"
                                    @click="deleteLoan(loan.id)"
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
