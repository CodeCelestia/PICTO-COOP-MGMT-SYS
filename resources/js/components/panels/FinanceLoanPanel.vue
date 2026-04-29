<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Landmark, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import Swal from 'sweetalert2';

interface Cooperative {
    id: number;
    name: string;
}

interface FinanceLoan {
    id: number;
    principal: string;
    status: string;
    created_at?: string | null;
    member?: { first_name?: string; last_name?: string };
    loanType?: { name?: string };
}

const props = defineProps<{
    cooperative: Cooperative;
    loans: FinanceLoan[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('create finance-member-loans') || permissions.value.includes('apply-own finance-member-loans'));
const canEdit = computed(() => permissions.value.includes('update finance-member-loans'));
const canDelete = computed(() => permissions.value.includes('delete finance-member-loans'));

const createHref = computed(() => `/cooperatives/${props.cooperative.id}/finance/loans/create`);
const viewHref = (loanId: number) => `/cooperatives/${props.cooperative.id}/finance/loans/${loanId}`;
const editHref = (loanId: number) => `/cooperatives/${props.cooperative.id}/finance/loans/${loanId}/edit`;

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const deleteLoan = (loanId: number) => {
    if (!canDelete.value) return;

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

        router.delete(`/cooperatives/${props.cooperative.id}/finance/loans/${loanId}`, {
            preserveScroll: true,
        });
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Loans</h2>
                <p class="text-sm text-muted-foreground">Loans for {{ cooperative.name }} only.</p>
            </div>
            <Link v-if="canCreate" :href="createHref">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Add Loan
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3 text-left">Loan Type</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Created</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loans.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No loans found for this cooperative.</td>
                    </tr>
                    <tr v-for="loan in loans" :key="loan.id" class="border-t">
                        <td class="px-4 py-3">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</td>
                        <td class="px-4 py-3">{{ loan.loanType?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(loan.principal) }}</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(loan.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ loan.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">{{ formatDate(loan.created_at) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="viewHref(loan.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="canEdit" :href="editHref(loan.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button v-if="canDelete" type="button" variant="ghost" size="sm" class="gap-2 text-destructive hover:text-destructive" @click="deleteLoan(loan.id)">
                                    <Trash2 class="h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>