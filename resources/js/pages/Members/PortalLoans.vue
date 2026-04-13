<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    cooperative: Cooperative | null;
}

interface LoanEntry {
    id: number;
    principal: string | number;
    interest_rate: string | number;
    term_months: number;
    status: string;
    purpose: string | null;
    created_at: string | null;
    amount_disbursed: string | number | null;
    paid_amount: number;
    remaining_balance: number;
}

interface Props {
    member: Member;
    loans: LoanEntry[];
    permissions: {
        can_view_details: boolean;
    };
}

defineProps<Props>();

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatAmount = (amount: string | number | null) => {
    if (amount === null || amount === undefined) return 'N/A';
    const value = Number(amount);
    if (Number.isNaN(value)) return String(amount);
    return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Loans</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ member.first_name }} {{ member.last_name }}
                        <span v-if="member.cooperative">· {{ member.cooperative.name }}</span>
                    </p>
                </div>
                <Link href="/member-portal">
                    <Button variant="outline">Back to Dashboard</Button>
                </Link>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Principal</TableHead>
                            <TableHead>Interest Rate</TableHead>
                            <TableHead>Term</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Remaining</TableHead>
                            <TableHead>Created</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loans.length === 0">
                            <TableCell colspan="7" class="text-center text-gray-500">
                                No loan records found yet.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="loan in loans" :key="loan.id">
                            <TableCell class="text-sm text-gray-900">{{ formatAmount(loan.principal) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ loan.interest_rate }}%</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ loan.term_months }} months</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ loan.status }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatAmount(loan.remaining_balance) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(loan.created_at) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">
                                <Link
                                    v-if="permissions.can_view_details"
                                    :href="`/member-portal/loans/${loan.id}`"
                                    class="text-primary hover:underline"
                                >
                                    View
                                </Link>
                                <span v-else class="text-gray-400">View</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
