<script setup lang="ts">
import { Button } from '@/components/ui/button';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, Trash2 } from 'lucide-vue-next';
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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Member Loans</h1>
                <p class="text-sm text-muted-foreground">Apply, approve, disburse, and monitor loan lifecycle.</p>
                <p class="mt-2 text-xs text-muted-foreground">
                    Status guide: Pending = submitted and waiting review; Approved = cleared for release; Active = already released and being paid; Completed = fully paid; Defaulted = overdue with missed payments.
                </p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/loans/create">
                <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                    <Plus class="h-4 w-4" />
                    New Loan
                </Button>
            </Link>
        </div>

        <div class="mt-6 rounded-lg border bg-card p-4">
            <h2 class="mb-3 text-sm font-semibold text-foreground">Filter Loans</h2>
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                    <select v-model="selectedStatus" class="rounded-md border px-3 py-2 text-sm">
                        <option value="">All</option>
                        <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
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
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="`/finance/loans/${loan.id}`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="permissions.can_edit && loan.status === 'Pending'" :href="`/finance/loans/${loan.id}/edit`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    v-if="permissions.can_delete && loan.status === 'Pending'"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                    @click="deleteLoan(loan.id)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
