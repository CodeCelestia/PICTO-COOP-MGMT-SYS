<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2, ArrowLeft } from 'lucide-vue-next';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed, ref } from 'vue';

const isFromCoopContext = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const currentUrl = window.location.pathname + window.location.search;

const isGlobalMode = computed(() => !coopIdFromUrl.value);
const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showRecordsList = computed(() => isGlobalMode.value ? !!selectedCoop.value : true);

const selectCoop = (coop: Cooperative) => {
    selectedCoop.value = coop;
    router.get('/finance/financial-records', {
        coop_id: coop.id,
    }, {
        preserveState: false,
        preserveScroll: false,
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    window.scrollTo(0, 0);
};

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

interface Cooperative {
    id: number;
    name: string;
}

defineProps<{
    records: {
        data: FinancialRecord[];
    };
    cooperative?: { id: number; name: string } | null;
    cooperatives?: Array<{ id: number; name: string; status: string }>;
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
}>();

const selectedCoop = ref<Cooperative | null>(null);

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

    <FinanceShellLayout active-tab="financial-records" :hide-tabs="isFromCoopContext">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                    <div v-if="isFromCoopContext" class="mb-4 flex items-center gap-2 text-sm">
                        <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                        <span class="text-muted-foreground">/</span>
                        <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">{{ cooperative?.name || 'Cooperative' }}</a>
                        <span class="text-muted-foreground">/</span>
                        <span class="text-foreground">Financial Records</span>
                    </div>
                <h1 class="text-2xl font-semibold">Financial Records</h1>
                <p class="text-sm text-muted-foreground">Loan and savings records are posted automatically. Use manual entries here for other finance transactions.</p>
            </div>
            <Link v-if="permissions.can_create" :href="isFromCoopContext && coopIdFromUrl ? `/finance/financial-records/create?coop_id=${coopIdFromUrl}` : (currentUrl ? `/finance/financial-records/create?return_to=${encodeURIComponent(currentUrl)}` : '/finance/financial-records/create')">
                <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                    <Plus class="h-4 w-4" />
                    Add Manual Entry
                </Button>
            </Link>
        </div>

        <!-- Global Mode: Cooperatives List -->
        <div v-if="showCooperativesList" class="mt-6">
            <h2 class="mb-4 text-lg font-semibold">Select a Cooperative</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="coop in cooperatives" :key="coop.id" class="cursor-pointer rounded-lg border bg-card p-4 transition hover:border-primary hover:bg-primary/5" @click="selectCoop(coop)">
                    <h3 class="font-medium text-foreground">{{ coop.name }}</h3>
                    <p class="mt-1 text-xs text-muted-foreground">Click to view records</p>
                </div>
            </div>
            <div v-if="!cooperatives || cooperatives.length === 0" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                No cooperatives available.
            </div>
        </div>

        <!-- Records List (shown in coop context or after coop selection in global mode) -->
        <div v-if="showRecordsList" class="mt-6">
            <div v-if="isGlobalMode && selectedCoop" class="mb-4 flex items-center gap-2">
                <Button variant="outline" size="sm" @click="backToCooperatives" class="gap-2">
                    <ArrowLeft class="h-4 w-4" />
                    Back to Cooperatives
                </Button>
                <h2 class="text-lg font-semibold">Records for {{ selectedCoop.name }}</h2>
            </div>

            <div class="overflow-hidden rounded-lg border bg-card">
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
        </div>
    </FinanceShellLayout>
</template>
