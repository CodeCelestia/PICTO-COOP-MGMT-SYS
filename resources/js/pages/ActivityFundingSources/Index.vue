<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { HandCoins, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface ActivityOption {
    id: number;
    title: string;
    coop_id: number;
}

interface ActivitySummary {
    id: number;
    title: string;
    cooperative?: Cooperative;
}

interface FundingSource {
    id: number;
    activity_id: number;
    coop_id: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string | null;
    amount_released: string | null;
    date_released: string | null;
    status: string;
    remarks: string | null;
    activity?: ActivitySummary;
    cooperative?: Cooperative;
}

interface Props {
    fundingSources: {
        data: FundingSource[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activities: ActivityOption[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        status?: string;
        funder_type?: string;
        activity_id?: string;
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
const status = ref(props.filters.status || 'all');
const funderType = ref(props.filters.funder_type || 'all');
const activityId = ref(props.filters.activity_id || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.fundingSources.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const statusOptions = ['Released', 'Pending', 'Partially Released'];
const funderTypes = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];

const applyFilters = () => {
    router.get('/activity-funding-sources', {
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        funder_type: funderType.value === 'all' ? '' : funderType.value,
        activity_id: activityId.value === 'all' ? '' : activityId.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    funderType.value = 'all';
    activityId.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/activity-funding-sources');
};

const deleteFundingSource = async (source: FundingSource) => {
    const confirmed = await confirmAction({
        title: 'Delete funding source?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/activity-funding-sources/${source.id}`, {
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
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Activity Funding Sources</h1>
                    <p class="mt-1 text-sm text-gray-500">Track funding sources per activity</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/activities" class="text-sm text-blue-600 hover:underline">
                        View Activities
                    </Link>
                    <Link v-if="canCreate" href="/activity-funding-sources/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Funding Source
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Funder or activity..."
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Activity</label>
                        <Select v-model="activityId">
                            <SelectTrigger id="activity_filter">
                                <SelectValue placeholder="All Activities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Activities</SelectItem>
                                <SelectItem v-for="activity in activities" :key="activity.id" :value="activity.id.toString()">
                                    {{ activity.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Funder Type</label>
                        <Select v-model="funderType">
                            <SelectTrigger id="funder_type_filter">
                                <SelectValue placeholder="All Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Types</SelectItem>
                                <SelectItem v-for="option in funderTypes" :key="option" :value="option">
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
                            <TableHead>Funder</TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Allocated</TableHead>
                            <TableHead>Released</TableHead>
                            <TableHead>Date Released</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="fundingSources.data.length === 0">
                            <TableCell :colspan="showActions ? 8 : 7" class="text-center text-gray-500">
                                No funding sources found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="source in fundingSources.data" :key="source.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                        <HandCoins class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ source.funder_name }}</div>
                                        <div class="text-xs text-gray-500">{{ source.funder_type }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ source.activity?.title || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ source.cooperative?.name || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatAmount(source.amount_allocated) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatAmount(source.amount_released) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(source.date_released) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ source.status }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="`/activity-funding-sources/${source.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteFundingSource(source)"
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

                <div v-if="fundingSources.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (fundingSources.current_page - 1) * fundingSources.per_page + 1 }} to
                            {{ Math.min(fundingSources.current_page * fundingSources.per_page, fundingSources.total) }} of
                            {{ fundingSources.total }} funding sources
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in fundingSources.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === fundingSources.current_page"
                                @click="router.get('/activity-funding-sources', { ...filters, page })"
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
