<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { LifeBuoy, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { confirmAction } from '@/lib/alerts';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Cooperative {
    id: number;
    name: string;
}

interface FinancialRecordOption {
    id: number;
    period: string;
    type: string;
    coop_id: number;
}

interface ExternalSupport {
    id: number;
    coop_id: number;
    support_type: string;
    provider_name: string;
    amount: string | null;
    date_granted: string | null;
    date_completed: string | null;
    status: string;
    remarks: string | null;
    cooperative: Cooperative;
    financial_record?: FinancialRecordOption | null;
}

interface Props {
    supports: {
        data: ExternalSupport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    financialRecords: FinancialRecordOption[];
    filters: {
        search?: string;
        support_type?: string;
        status?: string;
        coop_id?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);

const page = usePage();
const auth = computed(() => page.props.auth as { roles?: string[]; isCoopAdmin?: boolean; user?: { account_type?: string } } | undefined);
const roles = computed<string[]>(() => auth.value?.roles || []);
const accountType = computed(() => auth.value?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const canCreate = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEdit = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDelete = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const supportType = ref(props.filters.support_type || 'all');
const status = ref(props.filters.status || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.supports.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const supportTypes = ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'];
const statusOptions = ['Ongoing', 'Completed', 'Pending'];

const applyFilters = () => {
    router.get('/external-supports', {
        search: search.value,
        support_type: supportType.value === 'all' ? '' : supportType.value,
        status: status.value === 'all' ? '' : status.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    supportType.value = 'all';
    status.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/external-supports');
};

const deleteSupport = async (support: ExternalSupport) => {
    const confirmed = await confirmAction({
        title: 'Delete external support?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/external-supports/${support.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatAmount = (value: string | null) => {
    if (!value) return 'N/A';
    const numberValue = Number(value);
    if (Number.isNaN(numberValue)) return value;
    return numberValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const recordLabel = (record?: FinancialRecordOption | null) => {
    if (!record) return 'Unlinked';
    return `${record.period} · ${record.type}`;
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">External Supports</h1>
                    <p class="mt-1 text-sm text-gray-500">Track government and external support</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/financial-records" class="text-sm text-blue-600 hover:underline">
                        View Financial Records
                    </Link>
                    <Link v-if="canCreate" href="/external-supports/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Support
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Provider name..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger id="coop_filter">
                                <SelectValue placeholder="All Cooperatives" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Cooperatives</SelectItem>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                    {{ coop.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Support Type</label>
                        <Select v-model="supportType">
                            <SelectTrigger id="support_type_filter">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem v-for="option in supportTypes" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                        <Select v-model="status">
                            <SelectTrigger id="status_filter">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Rows Per Page</label>
                        <div class="flex gap-2">
                            <Select v-model="perPage">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select size" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">5</SelectItem>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
                                    <SelectItem value="custom">Custom</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="perPage === 'custom'"
                                v-model="customPerPage"
                                type="number"
                                min="1"
                                max="500"
                                placeholder="Enter"
                                class="w-28"
                            />
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">Clear Filters</Button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Provider</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Support Type</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Granted</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Linked Record</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="supports.data.length === 0">
                            <TableCell :colspan="showActions ? 8 : 7" class="text-center text-gray-500">
                                No external support records found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="support in supports.data" :key="support.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                                        <LifeBuoy class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ support.provider_name }}</div>
                                        <div class="text-xs text-gray-500">{{ support.support_type }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ support.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ support.support_type }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatAmount(support.amount) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(support.date_granted) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ support.status }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ recordLabel(support.financial_record) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="`/external-supports/${support.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteSupport(support)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="supports.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (supports.current_page - 1) * supports.per_page + 1 }} to
                            {{ Math.min(supports.current_page * supports.per_page, supports.total) }} of
                            {{ supports.total }} supports
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in supports.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === supports.current_page"
                                @click="router.get('/external-supports', { ...filters, page })"
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
