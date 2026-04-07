<script setup lang="ts">
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface MemberSummary {
    id: number;
    full_name: string;
}

interface ServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string | null;
    amount: string | number | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
    member?: MemberSummary | null;
}

const props = defineProps<{
    services: ServiceAvailed[];
    showMember?: boolean;
}>();

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatAmount = (amount: string | number | null) => {
    if (amount === null || amount === undefined || amount === '') return 'N/A';
    const value = Number(amount);
    if (Number.isNaN(value)) return String(amount);
    return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <div class="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-if="showMember">Member</TableHead>
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
                        <TableCell :colspan="showMember ? 8 : 7" class="py-10 text-center text-muted-foreground">
                            No services availed recorded yet.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="service in services" :key="service.id">
                        <TableCell v-if="showMember" class="text-sm text-foreground">
                            {{ service.member?.full_name || 'N/A' }}
                        </TableCell>
                        <TableCell class="text-sm text-foreground">{{ service.service_type }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.service_detail || 'N/A' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(service.date_availed) }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ formatAmount(service.amount) }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.status }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.reference_no || 'N/A' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.recorded_by || 'N/A' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </section>
</template>
