<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

const currentUrl = window.location.pathname + window.location.search;

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    cooperative?: { name?: string };
}

defineProps<{
    records: {
        data: FinancialRecord[];
    };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
}>();

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const recordDescription = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    if (description !== '') {
        return description;
    }

    return formatTypeLabel(record.type);
};

const deleteRecord = (recordId: number) => {
    if (!window.confirm('Are you sure you want to delete this financial record?')) {
        return;
    }

    router.delete(`/finance/financial-records/${recordId}`);
};
</script>

<template>
    <Head title="Finance - Financial Records" />

    <FinanceShellLayout active-tab="financial-records">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Financial Records</h1>
                <p class="text-sm text-muted-foreground">Loan and savings records are posted automatically. Use manual entries here for other finance transactions.</p>
            </div>
            <Link v-if="permissions.can_create" :href="currentUrl ? `/finance/financial-records/create?return_to=${encodeURIComponent(currentUrl)}` : '/finance/financial-records/create'">
                <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                    <Plus class="h-4 w-4" />
                    Add Manual Entry
                </Button>
            </Link>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Record</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Cooperative</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="7">No financial records found.</td>
                    </tr>
                    <tr v-for="record in records.data" :key="record.id" class="border-t">
                        <td class="px-4 py-3">{{ recordDescription(record) }}</td>
                        <td class="px-4 py-3">{{ formatTypeLabel(record.type) }}</td>
                        <td class="px-4 py-3">{{ record.cooperative?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ record.source ? formatTypeLabel(record.source) : 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(record.amount) }}</td>
                        <td class="px-4 py-3">{{ formatDate(record.date_recorded) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="currentUrl ? `/finance/financial-records/${record.id}?return_to=${encodeURIComponent(currentUrl)}` : `/finance/financial-records/${record.id}`">
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link
                                    v-if="permissions.can_edit"
                                    :href="currentUrl ? `/finance/financial-records/${record.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/financial-records/${record.id}/edit`"
                                >
                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button
                                    v-if="permissions.can_delete"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                    @click="deleteRecord(record.id)"
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
