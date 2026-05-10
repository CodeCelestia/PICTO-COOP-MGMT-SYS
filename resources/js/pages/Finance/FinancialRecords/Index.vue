<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2, ArrowLeft, Search, AlertCircle } from 'lucide-vue-next';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed, ref } from 'vue';
import { confirmAction } from '@/lib/alerts';

interface FinancialRecord {
    id: number;
    period: string;
    type: string;
    origin?: string | null;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    cooperative?: { id: number; name: string };
}

interface Cooperative {
    id: number;
    name: string;
    status?: string;
}

interface Props {
    records: {
        data: FinancialRecord[];
        current_page?: number;
        last_page?: number;
        per_page?: number;
        total?: number;
        links?: any;
    };
    cooperative?: Cooperative | null;
    cooperatives?: Cooperative[];
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
    };
    filters?: {
        search?: string;
        type?: string;
        coop_id?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');
const currentUrl = computed(() => `${window.location.pathname}${window.location.search}`);

const isFromCoopContext = computed(() => window.location.pathname.startsWith('/cooperatives/'));

const selectedCoop = ref<Cooperative | null>((() => {
    if (props.cooperative) return props.cooperative;
    const param = new URLSearchParams(window.location.search).get('coop_id');
    if (!param) return null;
    return props.cooperatives?.find(c => c.id === parseInt(param)) ?? null;
})());

const activeCoop = computed(() => selectedCoop.value);
const showCooperativesList = computed(() => !isFromCoopContext.value && !activeCoop.value);
const showRecordsList = computed(() => isFromCoopContext.value || !!activeCoop.value);

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || 'all');

const typeOptions = ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'];

const statusBadgeClass = (type: string | null | undefined) => {
    if (!type) return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200';
    const typeLower = (type || '').toLowerCase();
    if (['income', 'grant'].includes(typeLower)) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (['expense', 'loan'].includes(typeLower)) return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
};

const applyFilters = () => {
    const baseUrl = isFromCoopContext.value
        ? `/cooperatives/${coopSlug.value}?tab=finance&subtab=financial-records`
        : '/finance/financial-records';
    
    const params = new URLSearchParams();
    if (search.value) params.append('search', search.value);
    if (typeFilter.value !== 'all') params.append('type', typeFilter.value);
    if (activeCoop.value && !isFromCoopContext.value) params.append('coop_id', activeCoop.value.id.toString());

    const queryString = params.toString();
    router.get(baseUrl + (queryString ? `&${queryString}` : ''), {}, { preserveScroll: true });
};

const resetFilters = () => {
    search.value = '';
    typeFilter.value = 'all';
    router.get(isFromCoopContext.value
        ? `/cooperatives/${coopSlug.value}?tab=finance&subtab=financial-records`
        : '/finance/financial-records'
    );
};

const selectCoop = (coop: Cooperative) => {
    selectedCoop.value = coop;
    router.get('/finance/financial-records', { coop_id: coop.id }, { preserveState: false });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    router.get('/finance/financial-records', {}, { preserveState: false });
};

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatDate = (value: string | null) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const recordDescription = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    return description || `Financial Record #${record.id}`;
};

const deleteRecord = async (recordId: number) => {
    if (!props.permissions.can_delete) return;
    const confirmed = await confirmAction({
        title: 'Delete financial record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });
    if (!confirmed) return;
    router.delete(isFromCoopContext.value
        ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}`
        : `/finance/financial-records/${recordId}`,
        { preserveScroll: true }
    );
};

const viewHref = (recordId: number) => isFromCoopContext.value
    ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}`
    : `/finance/financial-records/${recordId}`;

const editHref = (recordId: number) => isFromCoopContext.value
    ? `/cooperatives/${coopSlug.value}/finance/financial-records/${recordId}/edit`
    : `/finance/financial-records/${recordId}/edit?return_to=${encodeURIComponent(currentUrl.value)}`;

const createHref = computed(() => {
    if (isFromCoopContext.value) {
        return `/cooperatives/${coopSlug.value}/finance/financial-records/create`;
    }
    return activeCoop.value
        ? `/finance/financial-records/create?coop_id=${activeCoop.value.id}&return_to=${encodeURIComponent(currentUrl.value)}`
        : '/finance/financial-records/create';
});
</script>

<template>
    <Head title="Finance - Financial Records" />

    <FinanceShellLayout active-tab="financial-records" :hide-tabs="isFromCoopContext">
        <div class="space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="space-y-4">
                <div v-if="isFromCoopContext" class="text-sm flex items-center gap-2">
                    <Link href="/cooperatives" class="text-primary hover:underline">Cooperatives</Link>
                    <span class="text-muted-foreground">/</span>
                    <Link :href="`/cooperatives/${coopSlug}`" class="text-primary hover:underline">{{ activeCoop?.name || 'Cooperative' }}</Link>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Financial Records</span>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Financial Records</h1>
                        <p class="text-sm text-muted-foreground">Manual finance entries and ledger records</p>
                    </div>
                    <Link v-if="props.permissions.can_create" :href="createHref">
                        <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Plus class="h-4 w-4" />
                            Add Record
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Cooperatives Selection Grid (Global Mode) -->
            <div v-if="showCooperativesList" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Select a Cooperative</CardTitle>
                        <CardDescription>Choose a cooperative to view and manage its financial records</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div
                                v-for="coop in (cooperatives || [])"
                                :key="coop.id"
                                class="cursor-pointer rounded-lg border border-border bg-card p-4 transition hover:border-primary hover:bg-primary/5"
                                @click="selectCoop(coop)"
                            >
                                <h3 class="font-medium text-foreground">{{ coop.name }}</h3>
                                <p class="mt-1 text-xs text-muted-foreground">Click to view records</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters Card (shown when viewing records) -->
            <div v-if="showRecordsList" class="space-y-4">
                <!-- Back to Coops Button (Global Mode) -->
                <div v-if="!isFromCoopContext && activeCoop" class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-2" @click="backToCooperatives">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <span class="text-sm font-medium">{{ activeCoop.name }}</span>
                </div>

                <!-- Filters Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filters</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="search">Search</Label>
                                <div class="relative">
                                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="search"
                                        v-model="search"
                                        type="text"
                                        placeholder="Period, source, purpose..."
                                        class="pl-8"
                                        @keyup.enter="applyFilters"
                                    />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="type">Type</Label>
                                <Select v-model="typeFilter">
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="All types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All types</SelectItem>
                                        <SelectItem v-for="type in typeOptions" :key="type" :value="type">
                                            {{ type }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button @click="applyFilters" class="bg-foreground text-background hover:bg-foreground/90">
                                Apply Filters
                            </Button>
                            <Button variant="outline" @click="resetFilters">
                                Reset
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Records Table -->
                <Card class="overflow-hidden">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow class="bg-muted/40 hover:bg-muted/40">
                                    <TableHead class="font-semibold">Type</TableHead>
                                    <TableHead class="font-semibold">Period</TableHead>
                                    <TableHead class="font-semibold">Description</TableHead>
                                    <TableHead class="font-semibold">Source</TableHead>
                                    <TableHead class="text-right font-semibold">Amount</TableHead>
                                    <TableHead class="font-semibold">Date Recorded</TableHead>
                                    <TableHead class="text-center font-semibold">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!props.records.data || props.records.data.length === 0">
                                    <TableCell colspan="7" class="text-center py-8 text-muted-foreground">
                                        <div class="flex flex-col items-center gap-2">
                                            <AlertCircle class="h-5 w-5" />
                                            <span>No financial records found</span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="record in props.records.data" :key="record.id" class="hover:bg-muted/50">
                                    <TableCell class="font-medium">
                                        <Badge :class="statusBadgeClass(record.type)" class="w-fit">
                                            {{ formatTypeLabel(record.type) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ record.period }}</TableCell>
                                    <TableCell class="text-sm max-w-xs truncate">{{ recordDescription(record) }}</TableCell>
                                    <TableCell class="text-sm">{{ record.source || '—' }}</TableCell>
                                    <TableCell class="text-right font-mono text-sm">
                                        {{ formatPhilippinePeso(record.amount) }}
                                    </TableCell>
                                    <TableCell class="text-sm">{{ formatDate(record.date_recorded) }}</TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex justify-center gap-1">
                                            <Link :href="viewHref(record.id)">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Link v-if="props.permissions.can_edit" :href="editHref(record.id)">
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Pencil class="h-4 w-4" />
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="props.permissions.can_delete"
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                                @click="deleteRecord(record.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </Card>
            </div>
        </div>
    </FinanceShellLayout>
</template>
