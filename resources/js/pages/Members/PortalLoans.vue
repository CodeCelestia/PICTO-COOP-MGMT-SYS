<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
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
    return formatPhilippinePeso(amount);
};
</script>

<template>
    <Head title="My Loans" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <Card class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">My Loans</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ member.first_name }} {{ member.last_name }}
                            <span v-if="member.cooperative">· {{ member.cooperative.name }}</span>
                        </p>
                    </div>
                    <Link href="/member-portal">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Dashboard
                        </Button>
                    </Link>
                </div>
            </Card>

            <Card class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <CardHeader class="px-6 pb-3">
                    <CardTitle class="text-base font-semibold text-foreground">Loan History</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">Your loan records and current balances.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Principal</TableHead>
                                    <TableHead>Interest Rate</TableHead>
                                    <TableHead>Term</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Remaining</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="loans.length === 0">
                                    <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                                        No loan records found yet.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="loan in loans" :key="loan.id">
                                    <TableCell class="text-sm text-foreground">{{ formatAmount(loan.principal) }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ loan.interest_rate }}%</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ loan.term_months }} months</TableCell>
                                    <TableCell>
                                        <Badge :class="[getFinanceStatusBadgeClass(loan.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                            {{ loan.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatAmount(loan.remaining_balance) }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(loan.created_at) }}</TableCell>
                                    <TableCell class="text-center">
                                        <Link
                                            v-if="permissions.can_view_details"
                                            :href="`/member-portal/loans/${loan.id}`"
                                        >
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Button v-else variant="ghost" size="sm" class="table-action-btn table-action-view gap-2" disabled>
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
