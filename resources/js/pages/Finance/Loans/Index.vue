<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

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
    cooperative?: { id: number; name: string } | null;
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

const currentUrl = window.location.pathname + window.location.search;

const isFromCoopContext = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

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

    void Swal.fire({
        title: 'Delete Loan?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        router.delete(`/finance/loans/${loanId}`);
    });
};

</script>

<template>
    <Head title="Finance - Loans" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isFromCoopContext">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div v-if="isFromCoopContext" class="mb-4 flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">{{ cooperative?.name || 'Cooperative' }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Loans</span>
                </div>
                <h1 class="text-2xl font-semibold">Member Loans</h1>
                <p class="text-sm text-muted-foreground">Apply, approve, disburse, and monitor loan lifecycle.</p>
                <p class="mt-2 text-xs text-muted-foreground">
                    Status guide: Pending = submitted and waiting review; Approved = cleared for release; Active = already released and being paid; Completed = fully paid; Defaulted = overdue with missed payments.
                </p>
            </div>
            <Link v-if="permissions.can_create" :href="isFromCoopContext && coopIdFromUrl ? `/finance/loans/create?coop_id=${coopIdFromUrl}` : (currentUrl ? `/finance/loans/create?return_to=${encodeURIComponent(currentUrl)}` : '/finance/loans/create')">
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
                    <select v-model="selectedStatus" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
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
                        <th class="px-4 py-3 text-center">Actions</th>
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
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(loan.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ loan.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">{{ formatDate(loan.created_at) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="isFromCoopContext && coopIdFromUrl ? `/finance/loans/${loan.id}?coop_id=${coopIdFromUrl}` : (currentUrl ? `/finance/loans/${loan.id}?return_to=${encodeURIComponent(currentUrl)}` : `/finance/loans/${loan.id}`)">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="permissions.can_edit && loan.status === 'Pending'" :href="isFromCoopContext && coopIdFromUrl ? `/finance/loans/${loan.id}/edit?coop_id=${coopIdFromUrl}` : (currentUrl ? `/finance/loans/${loan.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/loans/${loan.id}/edit`)">
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
