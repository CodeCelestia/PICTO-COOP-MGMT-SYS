<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Calendar, Filter, RefreshCw, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { confirmAction, notifyError, notifySuccess } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

interface RecycleItem {
    id: number;
    type: string;
    type_label: string;
    title: string;
    cooperative_id: number | null;
    cooperative_name: string | null;
    deleted_at: string | null;
    deleted_by_id: number | null;
    deleted_by_name: string | null;
    supports_related_restore: boolean;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    items: Paginated<RecycleItem>;
    types: Array<{ value: string; label: string }>;
    cooperatives: Array<{ id: number; name: string }>;
    deletedByOptions: Array<{ id: number; name: string }>;
    filters: {
        search?: string;
        type?: string;
        coop_id?: string | number | null;
        deleted_by?: string | number | null;
        date_from?: string;
        date_to?: string;
        per_page?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recycle Bin', href: '/recycle-bin' },
];

const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || 'all');
const coopFilter = ref(props.filters.coop_id ? String(props.filters.coop_id) : 'all');
const deletedByFilter = ref(props.filters.deleted_by ? String(props.filters.deleted_by) : 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const typeOptions = computed(() => [
    { value: 'all', label: 'All Modules' },
    ...props.types,
]);

const applyFilters = () => {
    router.get('/recycle-bin', {
        search: search.value || undefined,
        type: typeFilter.value === 'all' ? undefined : typeFilter.value,
        coop_id: coopFilter.value === 'all' ? undefined : coopFilter.value,
        deleted_by: deletedByFilter.value === 'all' ? undefined : deletedByFilter.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, { preserveScroll: true, preserveState: true });
};

const clearFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    coopFilter.value = 'all';
    deletedByFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const formatDateTime = (value: string | null) => {
    if (!value) return 'N/A';
    const date = new Date(value);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
};

const restoreRecord = async (item: RecycleItem, restoreRelated: boolean) => {
    const confirmed = await confirmAction({
        title: restoreRelated ? 'Restore record with related data?' : 'Restore record?',
        text: restoreRelated
            ? 'This will restore the record and its related data.'
            : 'This will restore the selected record.',
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post('/recycle-bin/restore', {
        type: item.type,
        id: item.id,
        restore_related: restoreRelated,
    }, {
        preserveScroll: true,
        onSuccess: () => notifySuccess('Record restored successfully.'),
        onError: () => notifyError('Unable to restore the record.'),
    });
};

const promptRestore = async (item: RecycleItem) => {
    if (!item.supports_related_restore) {
        await restoreRecord(item, false);
        return;
    }

    const result = await Swal.fire({
        title: 'Restore record?',
        text: 'Choose how you want to restore this item.',
        icon: 'question',
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonText: 'Restore only',
        denyButtonText: 'Restore with related',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#0f172a',
        denyButtonColor: '#0f172a',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        await restoreRecord(item, false);
        return;
    }

    if (result.isDenied) {
        await restoreRecord(item, true);
    }
};

const deleteRecord = async (item: RecycleItem) => {
    const confirmed = await confirmAction({
        title: 'Permanently delete record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete permanently',
    });

    if (!confirmed) return;

    router.delete('/recycle-bin', {
        data: {
            type: item.type,
            id: item.id,
        },
        preserveScroll: true,
        onSuccess: () => notifySuccess('Record permanently deleted.'),
        onError: () => notifyError('Unable to delete the record.'),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Recycle Bin" />

        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Recycle Bin</h1>
                        <p class="text-sm text-muted-foreground">Review and restore soft-deleted records across modules.</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-lg border border-border/70 bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
                        <Trash2 class="h-4 w-4" />
                        {{ items.total }} deleted record(s)
                    </div>
                </div>

                <div class="mt-6 border-t border-border/60 pt-6">
                    <FilterPanel
                        title="Filters"
                        description="Filter deleted records by module, cooperative, date, and user."
                        showLabel="Show filters"
                        hideLabel="Hide filters"
                    >
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Search</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input v-model="search" placeholder="Name, title, reference..." class="pl-9" />
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Module</label>
                                <Select v-model="typeFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Modules" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
                                <Select v-model="coopFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Cooperatives" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Cooperatives</SelectItem>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="String(coop.id)">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Deleted By</label>
                                <Select v-model="deletedByFilter">
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Users" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Users</SelectItem>
                                        <SelectItem v-for="user in deletedByOptions" :key="user.id" :value="String(user.id)">
                                            {{ user.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Deleted From</label>
                                <Input v-model="dateFrom" type="date" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Deleted To</label>
                                <Input v-model="dateTo" type="date" />
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2">
                            <Button class="gap-2" @click="applyFilters">
                                <Filter class="h-4 w-4" />
                                Apply Filters
                            </Button>
                            <Button variant="outline" @click="clearFilters">Clear Filters</Button>
                        </div>
                    </FilterPanel>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Record</TableHead>
                                <TableHead>Module</TableHead>
                                <TableHead>Cooperative</TableHead>
                                <TableHead>Deleted At</TableHead>
                                <TableHead>Deleted By</TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="items.data.length === 0">
                                <TableCell colspan="6" class="h-28 text-center text-muted-foreground">
                                    No deleted records match your filters.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="item in items.data" :key="`${item.type}-${item.id}`">
                                <TableCell>
                                    <div class="font-medium text-foreground">{{ item.title }}</div>
                                    <div class="text-xs text-muted-foreground">ID #{{ item.id }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{{ item.type_label }}</Badge>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ item.cooperative_name || 'N/A' }}
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    <div class="flex items-center gap-2">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDateTime(item.deleted_at) }}
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ item.deleted_by_name || 'System' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Button variant="outline" size="sm" class="gap-2" @click="promptRestore(item)">
                                            <RefreshCw class="h-4 w-4" />
                                            Restore
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="gap-2 text-destructive hover:text-destructive"
                                            @click="deleteRecord(item)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Delete
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="items.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (items.current_page - 1) * items.per_page + 1 }} to
                            {{ Math.min(items.current_page * items.per_page, items.total) }} of
                            {{ items.total }} records
                        </div>
                        <div class="flex flex-wrap gap-2" aria-label="Recycle bin pagination">
                            <Button
                                v-for="page in items.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === items.current_page"
                                @click="router.get('/recycle-bin', { ...props.filters, page }, { preserveScroll: true, preserveState: true })"
                            >
                                {{ page }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
