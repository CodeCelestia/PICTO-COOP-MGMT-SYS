<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, XCircle, ArrowLeft, Building2, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SavingsRow {
    id: number;
    account_number: string;
    account_status: string;
    current_balance: string;
    interest_rate: string;
    member?: { first_name?: string; last_name?: string };
}

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string;
    status: string;
    classification?: string;
    members_count?: number;
    city_municipality?: string | null;
    province?: string | null;
    date_established?: string | null;
    types?: Array<{ id: number; name: string }>;
    latest_accreditation?: {
        id: number;
        cooperative_id: number;
        level: string;
        date_granted: string | null;
    } | null;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    coop_id: number;
}

const props = defineProps<{
    savings: { data: SavingsRow[] };
    cooperative?: { id: number; name: string } | null;
    cooperatives?: Cooperative[];
    accountStatuses: string[];
    filters?: { status?: string; coop_search?: string; coop_status?: string; coop_type?: string; coop_classification?: string };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_close: boolean;
        can_record_transaction: boolean;
        can_calculate_interest: boolean;
    };
}>();

const currentUrl = window.location.pathname + window.location.search;
const pathName = window.location.pathname;

const selectedCoop = ref<Cooperative | null>(null);
const selectedMember = ref<Member | null>(null);
const coopMembers = ref<Member[]>([]);
const loadingMembers = ref(false);

// Cooperative list filters
const coopSearchFilter = ref<string>(props.filters?.coop_search || '');
const coopStatusFilter = ref<string>('');
const coopTypeFilter = ref<string>('');
const coopClassificationFilter = ref<string>('');

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId, 10) : null;
});

const isFromCoopContext = computed(() => pathName.startsWith('/cooperatives/') || !!coopIdFromUrl.value);
const coopContextId = computed(() => props.cooperative?.id || coopIdFromUrl.value || null);
const indexBasePath = computed(() => {
    if (pathName.startsWith('/cooperatives/') && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings`;
    }

    return '/finance/savings';
});

const isGlobalMode = computed(() => !props.cooperative?.id && !coopIdFromUrl.value);
const activeCoop = computed(() => selectedCoop.value);
const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showMembersList = computed(() => isGlobalMode.value && selectedCoop.value && !selectedMember.value);
const showSavingsList = computed(() => isGlobalMode.value ? !!selectedMember.value : true);

// Get unique cooperative statuses, types, and classifications for filtering
const availableCoopStatuses = computed(() => {
    const statuses = new Set<string>();
    props.cooperatives?.forEach(coop => {
        if (coop.status) statuses.add(coop.status);
    });
    return Array.from(statuses).sort();
});

const availableCoopTypes = computed(() => {
    const types = new Set<string>();
    props.cooperatives?.forEach(coop => {
        coop.types?.forEach(type => {
            types.add(type.name);
        });
    });
    return Array.from(types).sort();
});

// Get unique cooperative classifications
const availableCoopClassifications = computed(() => {
    const classifications = new Set<string>();
    props.cooperatives?.forEach(coop => {
        if (coop.classification) classifications.add(coop.classification);
    });
    return Array.from(classifications).sort();
});

// Filter cooperatives by status, type, and classification
const filteredCooperatives = computed(() => {
    let filtered = props.cooperatives || [];

    if (coopSearchFilter.value.trim()) {
        const searchTerm = coopSearchFilter.value.trim().toLowerCase();
        filtered = filtered.filter(coop =>
            coop.name.toLowerCase().includes(searchTerm)
            || (coop.registration_number || '').toLowerCase().includes(searchTerm)
        );
    }
    
    if (coopStatusFilter.value) {
        filtered = filtered.filter(coop => coop.status === coopStatusFilter.value);
    }
    
    if (coopTypeFilter.value) {
        filtered = filtered.filter(coop => 
            coop.types?.some(type => type.name === coopTypeFilter.value)
        );
    }
    
    if (coopClassificationFilter.value) {
        filtered = filtered.filter(coop => coop.classification === coopClassificationFilter.value);
    }
    
    return filtered;
});

const applyFilters = () => {
    const params: Record<string, any> = {};
    if (coopSearchFilter.value) params.coop_search = coopSearchFilter.value;
    if (coopStatusFilter.value) params.status = coopStatusFilter.value;
    if (coopTypeFilter.value) params.type = coopTypeFilter.value;
    if (coopClassificationFilter.value) params.classification = coopClassificationFilter.value;
    
    router.get('/finance/savings', params, { preserveScroll: true });
};

const resetFilters = () => {
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get('/finance/savings', {}, { preserveScroll: true });
};

const selectCoop = async (coop: Cooperative) => {
    selectedCoop.value = coop;
    selectedMember.value = null;
    loadingMembers.value = true;
    
    try {
        const response = await fetch(`/api/cooperatives/${coop.id}/members`);
        coopMembers.value = await response.json();
    } catch (error) {
        console.error('Failed to load members:', error);
        coopMembers.value = [];
    } finally {
        loadingMembers.value = false;
    }
};

const selectMember = (member: Member) => {
    selectedMember.value = member;
    router.get(indexBasePath.value, {
        member_id: member.id,
        status: status.value || undefined,
    }, {
        preserveState: false,
        preserveScroll: false,
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    selectedMember.value = null;
    coopMembers.value = [];
    coopSearchFilter.value = '';
    coopStatusFilter.value = '';
    coopTypeFilter.value = '';
    coopClassificationFilter.value = '';
    router.get('/finance/savings');
};

const backToMembers = () => {
    selectedMember.value = null;
    window.scrollTo(0, 0);
};

const status = ref(props.filters?.status || '');

const applyFilter = () => {
    const params: Record<string, any> = {
        status: status.value || undefined,
    };
    
    if (selectedMember.value) {
        params.member_id = selectedMember.value.id;
    }
    
    router.get(indexBasePath.value, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    status.value = '';
    const params: Record<string, string | number | undefined> = {};
    if (selectedMember.value) {
        params.member_id = selectedMember.value.id;
    }

    router.get(indexBasePath.value, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const createHref = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/create?return_to=${encodeURIComponent(currentUrl)}`;
    }

    return currentUrl
        ? `/finance/savings/create?return_to=${encodeURIComponent(currentUrl)}`
        : '/finance/savings/create';
});

const viewHref = (savingsId: number) => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}`;
    }

    return currentUrl
        ? `/finance/savings/${savingsId}?return_to=${encodeURIComponent(currentUrl)}`
        : `/finance/savings/${savingsId}`;
};

const editHref = (savingsId: number) => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}/edit?return_to=${encodeURIComponent(currentUrl)}`;
    }

    return currentUrl
        ? `/finance/savings/${savingsId}/edit?return_to=${encodeURIComponent(currentUrl)}`
        : `/finance/savings/${savingsId}/edit`;
};

const closeAccount = (savingsId: number) => {
    if (!props.permissions.can_close) {
        return;
    }

    if (!window.confirm('Are you sure you want to close this savings account?')) {
        return;
    }

    const url = isFromCoopContext.value && coopContextId.value
        ? `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}`
        : `/finance/savings/${savingsId}`;

    router.delete(url);
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'Active':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'Inactive':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        case 'Dormant':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
        default:
            return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200';
    }
};
</script>

<template>
    <Head title="Finance - Savings" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="w-full space-y-6">
            <!-- Breadcrumb & Header -->
            <div class="space-y-4">
                <div v-if="isFromCoopContext" class="text-sm flex items-center gap-2">
                    <Link href="/cooperatives" class="text-primary hover:underline">Cooperatives</Link>
                    <span class="text-muted-foreground">/</span>
                    <Link :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ activeCoop?.name || 'Cooperative' }}</Link>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Savings</span>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Savings Accounts</h1>
                        <p class="text-sm text-muted-foreground">Manage member balances, withdrawals, deposits, and interest.</p>
                    </div>
                    <Link v-if="permissions.can_create" :href="createHref">
                        <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Plus class="h-4 w-4" />
                            Open Savings Account
                        </Button>
                    </Link>
                </div>
            </div>

            <div v-if="showCooperativesList" class="space-y-4">
                <!-- Filter Section -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filter Cooperatives</CardTitle>
                        <CardDescription>Narrow cooperatives by status, type, and classification to quickly find what you need.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
                                <div class="lg:col-span-2">
                                    <label class="mb-1 block text-xs font-medium text-muted-foreground">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                        <input
                                            v-model="coopSearchFilter"
                                            type="text"
                                            placeholder="Search cooperative name or registration no..."
                                            class="w-full rounded-md border border-input bg-background py-2 pl-8 pr-3 text-sm text-foreground"
                                            @keyup.enter="applyFilters"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                                    <select v-model="coopStatusFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Statuses</option>
                                        <option v-for="status in availableCoopStatuses" :key="status" :value="status">{{ status }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Type</label>
                                    <select v-model="coopTypeFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Types</option>
                                        <option v-for="type in availableCoopTypes" :key="type" :value="type">{{ type }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-foreground">Classification</label>
                                    <select v-model="coopClassificationFilter" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                        <option value="">All Classifications</option>
                                        <option v-for="classification in availableCoopClassifications" :key="classification" :value="classification">{{ classification }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button variant="default" class="gap-2" @click="applyFilters">
                                    <Filter class="h-4 w-4" />
                                    Apply Filters
                                </Button>
                                <Button variant="outline" @click="resetFilters">
                                    Reset Filters
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cooperatives List -->
                <Card>
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="text-lg">Cooperatives</CardTitle>
                                <CardDescription>{{ filteredCooperatives.length }} cooperative(s) available</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cooperative</TableHead>
                                        <TableHead class="text-center">Members</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Location</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Accreditation</TableHead>
                                        <TableHead>Established</TableHead>
                                        <TableHead class="text-center">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="filteredCooperatives.length === 0">
                                        <TableCell colspan="8" class="py-10 text-center text-muted-foreground">
                                            <div class="mx-auto max-w-md space-y-3">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                    <Building2 class="h-6 w-6" />
                                                </div>
                                                <p class="font-medium text-foreground">No cooperatives found</p>
                                                <p class="text-sm text-muted-foreground">Try adjusting your filters</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="coop in filteredCooperatives" :key="coop.id" class="cursor-pointer hover:bg-muted/50" @click="selectCoop(coop)">
                                        <TableCell class="font-medium">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                    <Building2 class="h-5 w-5" />
                                                </div>
                                                <div>
                                                    <div class="max-w-48 truncate text-foreground">{{ coop.name }}</div>
                                                    <div v-if="coop.registration_number" class="text-xs text-muted-foreground">Reg. {{ coop.registration_number }}</div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Badge class="rounded-full border px-2.5 py-0.5 text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ coop.members_count ?? 0 }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <Badge v-if="coop.types && coop.types.length > 0" variant="outline" class="text-xs">
                                                    {{ coop.types[0]?.name }}
                                                </Badge>
                                                <Badge v-if="coop.types && coop.types.length > 1" variant="secondary" class="text-xs">
                                                    +{{ coop.types.length - 1 }}
                                                </Badge>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            <div class="max-w-40 truncate">{{ coop.city_municipality || coop.province || 'N/A' }}</div>
                                            <div v-if="coop.city_municipality && coop.province" class="truncate text-xs text-muted-foreground">{{ coop.province }}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getStatusColor(coop.status)" class="text-xs font-medium">
                                                {{ coop.status }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="coop.latest_accreditation" class="text-sm">
                                                <div class="text-foreground">{{ coop.latest_accreditation.level }}</div>
                                                <div class="text-xs text-muted-foreground">{{ coop.latest_accreditation.date_granted ? new Date(coop.latest_accreditation.date_granted).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}</div>
                                            </div>
                                            <span v-else class="text-sm text-muted-foreground">N/A</span>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">
                                            {{ coop.date_established ? new Date(coop.date_established).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A' }}
                                        </TableCell>
                                        <TableCell class="text-center">
                                            <Button variant="ghost" size="sm" class="gap-1" @click.stop="selectCoop(coop)">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="showMembersList" class="space-y-4">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToCooperatives" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <h2 class="text-lg font-semibold">Members in {{ selectedCoop?.name }}</h2>
                </div>
                <div v-if="loadingMembers" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                    Loading members...
                </div>
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="member in coopMembers" :key="member.id" class="cursor-pointer rounded-lg border bg-card p-4 transition hover:border-primary hover:bg-primary/5" @click="selectMember(member)">
                        <h3 class="font-medium text-foreground">{{ member.first_name }} {{ member.last_name }}</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Click to view savings</p>
                    </div>
                </div>
                <div v-if="!loadingMembers && (!coopMembers || coopMembers.length === 0)" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                    No members found in this cooperative.
                </div>
            </div>

            <div v-if="showSavingsList" class="space-y-4">
                <div v-if="isGlobalMode && selectedMember" class="mb-1 flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToMembers" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Members
                    </Button>
                    <h2 class="text-lg font-semibold">Savings for {{ selectedMember.first_name }} {{ selectedMember.last_name }}</h2>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filters</CardTitle>
                        <CardDescription>Narrow records by account status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                                <select v-model="status" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                    <option value="">All</option>
                                    <option v-for="item in accountStatuses" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </div>
                            <Button variant="outline" class="gap-2" @click="applyFilter">
                                <Filter class="h-4 w-4" />
                                Apply
                            </Button>
                            <Button variant="ghost" @click="resetFilter">Reset</Button>
                        </div>
                    </CardContent>
                </Card>

                <div class="overflow-hidden rounded-lg border bg-card">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left">Account</th>
                                <th class="px-4 py-3 text-left">Member</th>
                                <th class="px-4 py-3 text-left">Balance</th>
                                <th class="px-4 py-3 text-left">Interest</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="savings.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <div class="space-y-1">
                                        <p class="font-medium text-foreground">No savings accounts yet</p>
                                        <p class="text-sm text-muted-foreground">Open a savings account for an active member to start tracking deposits, withdrawals, and interest.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="row in savings.data" :key="row.id" class="border-t">
                                <td class="px-4 py-3">{{ row.account_number }}</td>
                                <td class="px-4 py-3">{{ row.member?.first_name }} {{ row.member?.last_name }}</td>
                                <td class="px-4 py-3">{{ formatPhilippinePeso(row.current_balance) }}</td>
                                <td class="px-4 py-3">{{ row.interest_rate }}%</td>
                                <td class="px-4 py-3">
                                    <Badge :class="[getFinanceStatusBadgeClass(row.account_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                        {{ row.account_status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <Link :href="viewHref(row.id)">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link v-if="permissions.can_edit" :href="editHref(row.id)">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="permissions.can_close && row.account_status !== 'Closed'"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                            @click="closeAccount(row.id)"
                                        >
                                            <XCircle class="h-4 w-4" />
                                            Close
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
