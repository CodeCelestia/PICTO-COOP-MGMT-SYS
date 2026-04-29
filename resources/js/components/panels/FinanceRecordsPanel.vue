<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Eye, FileText, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Cooperative {
    id: number;
    name: string;
}

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
    date_recorded: string | null;
}

const props = defineProps<{
    cooperative: Cooperative;
    records: FinancialRecord[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('create finance-ledger-entries'));
const canEdit = computed(() => permissions.value.includes('update finance-ledger-entries'));
const canDelete = computed(() => permissions.value.includes('delete finance-ledger-entries'));

const createHref = computed(() => `/cooperatives/${props.cooperative.id}/finance/financial-records/create`);
const viewHref = (recordId: number) => `/cooperatives/${props.cooperative.id}/finance/financial-records/${recordId}`;
const editHref = (recordId: number) => `/cooperatives/${props.cooperative.id}/finance/financial-records/${recordId}/edit`;

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const deleteRecord = (recordId: number) => {
    if (!canDelete.value) return;

    if (!window.confirm('Delete this financial record?')) {
        return;
    }

    router.delete(`/cooperatives/${props.cooperative.id}/finance/financial-records/${recordId}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Financial Records</h2>
                <p class="text-sm text-muted-foreground">Manual finance entries for {{ cooperative.name }} only.</p>
            </div>
            <Link v-if="canCreate" :href="createHref">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Add Entry
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Period</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Source</th>
                        <th class="px-4 py-3 text-left">Recorded</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="records.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No financial records found for this cooperative.</td>
                    </tr>
                    <tr v-for="record in records" :key="record.id" class="border-t">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                <span>{{ formatTypeLabel(record.type) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ record.period }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(record.amount) }}</td>
                        <td class="px-4 py-3">{{ record.source || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatDate(record.date_recorded) }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="viewHref(record.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="canEdit" :href="editHref(record.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button v-if="canDelete" type="button" variant="ghost" size="sm" class="gap-2 text-destructive hover:text-destructive" @click="deleteRecord(record.id)">
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