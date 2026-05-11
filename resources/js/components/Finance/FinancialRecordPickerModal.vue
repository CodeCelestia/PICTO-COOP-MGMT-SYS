<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { CalendarRange, ListFilter, Loader2, Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface FinancialRecordItem {
    id: number;
    coop_id: number;
    title: string;
    period: string;
    type: string;
    amount: number | string | null;
    date_recorded: string | null;
    source?: string | null;
    purpose?: string | null;
}

interface PaginatedResponse {
    data: FinancialRecordItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    modelValue: boolean;
    cooperativeId?: string | number | null;
    excludeIds?: number[];
}

const props = withDefaults(defineProps<Props>(), {
    cooperativeId: null,
    excludeIds: () => [],
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'select', value: FinancialRecordItem): void;
}>();

const search = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const typeFilter = ref('all');
const loading = ref(false);
const errorMessage = ref('');

const records = ref<FinancialRecordItem[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);

let filterTimer: ReturnType<typeof setTimeout> | null = null;

const normalizedCoopId = computed(() => {
    if (props.cooperativeId === null || props.cooperativeId === undefined || props.cooperativeId === '') {
        return null;
    }
    return String(props.cooperativeId);
});

const typeOptions = ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'];

const open = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
});

const formatAmount = (value: number | string | null) => {
    const amount = Number(value ?? 0);
    if (!Number.isFinite(amount)) {
        return '₱0.00';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
};

const formatDate = (value?: string | null) => {
    if (!value) {
        return '—';
    }

    const d = new Date(value);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const closeModal = () => {
    emit('update:modelValue', false);
};

const fetchRecords = async (page = 1) => {
    if (!normalizedCoopId.value || !open.value) {
        records.value = [];
        currentPage.value = 1;
        lastPage.value = 1;
        total.value = 0;
        return;
    }

    loading.value = true;
    errorMessage.value = '';

    try {
        const params = new URLSearchParams();
        params.set('page', String(page));
        params.set('per_page', '10');

        if (search.value.trim()) params.set('search', search.value.trim());
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value) params.set('date_to', dateTo.value);
        if (typeFilter.value !== 'all') params.set('type', typeFilter.value);
        props.excludeIds.forEach((id) => params.append('exclude_ids[]', String(id)));

        const response = await fetch(`/cooperatives/${normalizedCoopId.value}/finance/external-supports/financial-records?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Failed to load financial records.');
        }

        const payload = (await response.json()) as PaginatedResponse;
        records.value = Array.isArray(payload.data) ? payload.data : [];
        currentPage.value = payload.current_page || 1;
        lastPage.value = payload.last_page || 1;
        total.value = payload.total || 0;
    } catch (error) {
        records.value = [];
        currentPage.value = 1;
        lastPage.value = 1;
        total.value = 0;
        errorMessage.value = error instanceof Error ? error.message : 'Failed to load financial records.';
    } finally {
        loading.value = false;
    }
};

const selectRecord = (record: FinancialRecordItem) => {
    emit('select', record);
    emit('update:modelValue', false);
};

watch(
    () => open.value,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        fetchRecords(1);
    }
);

watch([search, dateFrom, dateTo, typeFilter, normalizedCoopId], () => {
    if (!open.value) {
        return;
    }

    if (filterTimer) {
        clearTimeout(filterTimer);
    }

    filterTimer = setTimeout(() => {
        fetchRecords(1);
    }, 250);
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="flex max-h-[88vh] w-[98vw] max-w-[98vw] flex-col overflow-hidden xl:max-w-6xl 2xl:max-w-7xl">
            <DialogHeader>
                <DialogTitle>Select Financial Record</DialogTitle>
                <DialogDescription>
                    Search and filter financial records, then select one to link.
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-3 border-b pb-3 md:grid-cols-2 lg:grid-cols-4">
                <div class="relative md:col-span-2">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="search" class="pl-9" placeholder="Search title, source, purpose, date..." />
                </div>

                <div>
                    <Select v-model="typeFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Types</SelectItem>
                            <SelectItem v-for="option in typeOptions" :key="option" :value="option">
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="hidden items-center justify-end text-xs text-muted-foreground lg:flex">
                    <ListFilter class="mr-1 h-3.5 w-3.5" />
                    Filters
                </div>

                <div class="md:col-span-1">
                    <Input v-model="dateFrom" type="date" aria-label="From date" />
                </div>
                <div class="md:col-span-1">
                    <Input v-model="dateTo" type="date" aria-label="To date" />
                </div>
                <div class="md:col-span-2 flex items-center text-xs text-muted-foreground">
                    <CalendarRange class="mr-1.5 h-3.5 w-3.5" />
                    Date range filters apply together with search and type.
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-auto">
                <Table>
                    <TableHeader class="sticky top-0 z-10 bg-background">
                        <TableRow>
                            <TableHead class="w-[45%]">Title</TableHead>
                            <TableHead class="whitespace-nowrap">Date</TableHead>
                            <TableHead class="whitespace-nowrap">Amount</TableHead>
                            <TableHead class="whitespace-nowrap">Type</TableHead>
                            <TableHead class="whitespace-nowrap text-right">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="loading">
                            <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                                <div class="inline-flex items-center gap-2">
                                    <Loader2 class="h-4 w-4 animate-spin" />
                                    Loading financial records...
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-else-if="errorMessage">
                            <TableCell colspan="5" class="py-10 text-center text-sm text-red-500">
                                {{ errorMessage }}
                            </TableCell>
                        </TableRow>

                        <TableRow v-else-if="records.length === 0">
                            <TableCell colspan="5" class="py-10 text-center text-muted-foreground">
                                No financial records found.
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="record in records"
                            v-else
                            :key="record.id"
                            class="cursor-pointer"
                            @click="selectRecord(record)"
                        >
                            <TableCell>
                                <div class="font-medium">{{ record.title }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ record.period }}
                                </div>
                            </TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatDate(record.date_recorded) }}</TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatAmount(record.amount) }}</TableCell>
                            <TableCell class="whitespace-nowrap">{{ record.type }}</TableCell>
                            <TableCell class="whitespace-nowrap text-right">
                                <Button type="button" size="sm" variant="outline" @click.stop="selectRecord(record)">
                                    Select
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <DialogFooter class="flex items-center justify-between gap-2 border-t pt-3 sm:justify-between">
                <p class="text-xs text-muted-foreground">
                    {{ total }} total record{{ total === 1 ? '' : 's' }}
                </p>

                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="loading || currentPage <= 1"
                        @click="fetchRecords(currentPage - 1)"
                    >
                        Previous
                    </Button>
                    <span class="text-xs text-muted-foreground">Page {{ currentPage }} of {{ lastPage }}</span>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="loading || currentPage >= lastPage"
                        @click="fetchRecords(currentPage + 1)"
                    >
                        Next
                    </Button>
                    <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
