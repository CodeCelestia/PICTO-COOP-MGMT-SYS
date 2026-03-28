<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    cooperative: Cooperative;
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

const props = defineProps<Props>();

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
    const value = Number(amount);
    if (Number.isNaN(value)) return amount;
    return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Services Availed</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ member.first_name }} {{ member.last_name }} · {{ member.cooperative.name }}
                    </p>
                </div>
                <Link :href="`/members/${member.id}`">
                    <Button variant="outline">Back to Member</Button>
                </Link>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
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
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="services.length === 0">
                            <TableCell colspan="7" class="text-center text-gray-500">
                                No services availed recorded yet.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="service in services" :key="service.id">
                            <TableCell class="text-sm text-gray-900">{{ service.service_type }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ service.service_detail || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(service.date_availed) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatAmount(service.amount) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ service.status }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ service.reference_no || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ service.recorded_by || 'N/A' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
