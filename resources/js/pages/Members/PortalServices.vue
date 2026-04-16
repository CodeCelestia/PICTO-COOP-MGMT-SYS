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

interface ServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string | null;
    amount: string | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
}

interface Props {
    member: Member;
    services: ServiceAvailed[];
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

const formatAmount = (amount: string | null) => {
    if (!amount) return 'N/A';
    return formatPhilippinePeso(amount);
};
</script>

<template>
    <Head title="My Services Availed" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <Card class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">My Services Availed</h1>
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
                    <CardTitle class="text-base font-semibold text-foreground">Service History</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">Your recorded services and transactions.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Service</TableHead>
                                    <TableHead>Details</TableHead>
                                    <TableHead>Date Availed</TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Reference No</TableHead>
                                    <TableHead>Recorded By</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="services.length === 0">
                                    <TableCell colspan="8" class="py-8 text-center text-muted-foreground">
                                        No services availed recorded yet.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="service in services" :id="`service-${service.id}`" :key="service.id">
                                    <TableCell class="text-sm text-foreground">{{ service.service_type }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ service.service_detail || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(service.date_availed) }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatAmount(service.amount) }}</TableCell>
                                    <TableCell>
                                        <Badge :class="[getFinanceStatusBadgeClass(service.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                            {{ service.status || 'N/A' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ service.reference_no || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ service.recorded_by || 'N/A' }}</TableCell>
                                    <TableCell class="text-center">
                                        <a :href="`#service-${service.id}`" class="inline-flex">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </a>
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
