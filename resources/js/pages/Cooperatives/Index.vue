<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Eye,
    FileText,
    Loader2,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    SearchX,
    SlidersHorizontal,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import { useCoopLabel } from '@/composables/useCoopLabel';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

interface Cooperative {
    id: number;
    name: string;
    members_count?: number;
    registration_number: string;
    date_established: string;
    address: string;
    province: string;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended';
    latest_accreditation?: {
        id: number;
        cooperative_id: number;
        level: string;
        date_granted: string | null;
    } | null;
    created_at: string;
    deleted_at?: string | null;
    types?: Array<{ id: number; name: string }>;
}

interface CooperativeTypeOption {
    id: number;
    name: string;
}

interface Props {
    cooperatives: {
        data: Cooperative[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperativeTypes: CooperativeTypeOption[];
    filters: {
        search: string;
        status: string;
        coop_type?: string;
        region?: string;
        province?: string;
        municipality?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();
const {
    availableCooperativesLabel,
    cooperativeLabelLower,
    cooperativeManagementLabel,
    totalCooperativesLabel,
} = useCoopLabel();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: cooperativeManagementLabel.value,
        href: '/cooperatives',
    },
]);

const page = usePage();
const currentUrl = page.url || '';
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canViewAllCoops = computed(() => permissions.value.includes('view-all-cooperatives'));
const canCreateCoop = computed(() => permissions.value.includes('create coop-master-profile'));
const canEditCoop = computed(() => permissions.value.includes('update coop-master-profile'));
const canDeleteCoop = computed(() => permissions.value.includes('delete coop-master-profile'));
const canBulkDelete = computed(() => canDeleteCoop.value && !isCoopAdminOnly.value);
const showActions = computed(() => canEditCoop.value || canDeleteCoop.value || canViewAllCoops.value);
const isCoopAdminOnly = computed(() => !canViewAllCoops.value);
const coopProfile = computed(() => props.cooperatives.data[0] || null);

const coopTypes = computed(() => props.cooperativeTypes.map((type) => type.name));

const { regions, provinces, cities, fetchRegions, fetchProvinces, fetchCities } = usePsgc();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const coopType = ref(props.filters.coop_type || 'all');
const selectedRegionCode = ref('all');
const selectedProvinceCode = ref('all');
const selectedMunicipalityCode = ref('all');
const presetPageSizes = ['10', '15', '25', '50', '100'];
const initialPerPageRaw = props.filters.per_page || String(props.cooperatives.per_page || 10);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);
const filtersVisible = ref(true);
const isLoading = ref(false);
const isHydratingFilters = ref(true);
const SEARCH_DEBOUNCE_MS = 300;
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const currentPage = computed(() => props.cooperatives.current_page || 1);
const totalPages = computed(() => Math.max(props.cooperatives.last_page || 1, 1));
const showingFrom = computed(() => (props.cooperatives.total ? (currentPage.value - 1) * props.cooperatives.per_page + 1 : 0));
const showingTo = computed(() => (props.cooperatives.total ? Math.min(currentPage.value * props.cooperatives.per_page, props.cooperatives.total) : 0));

const hasActiveFilters = computed(() => {
    return Boolean(
        search.value.trim()
        || status.value !== 'all'
        || coopType.value !== 'all'
        || selectedRegionCode.value !== 'all'
        || selectedProvinceCode.value !== 'all'
        || selectedMunicipalityCode.value !== 'all'
        || resolvedPerPage() !== '10',
    );
});

const buildQuery = (pageNumber = 1) => {
    const regionName = (selectedRegionCode.value !== 'all' && regions.value.find((r) => r.code === selectedRegionCode.value)?.name) || '';
    const provinceName = (selectedProvinceCode.value !== 'all' && provinces.value.find((p) => p.code === selectedProvinceCode.value)?.name) || '';
    const municipalityName = (selectedMunicipalityCode.value !== 'all' && cities.value.find((c) => c.code === selectedMunicipalityCode.value)?.name) || '';

    return {
        page: pageNumber,
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        coop_type: coopType.value === 'all' ? '' : coopType.value,
        region: regionName,
        province: provinceName,
        municipality: municipalityName,
        per_page: resolvedPerPage(),
    };
};

const navigateWithFilters = (pageNumber = 1) => {
    isLoading.value = true;

    router.get('/cooperatives', buildQuery(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

const applyFilters = (pageNumber = 1) => {
    navigateWithFilters(pageNumber);
};

onMounted(async () => {
    await fetchRegions();

    if (props.filters.region) {
        const regionObj = regions.value.find((r) => r.name === props.filters.region);
        if (regionObj) {
            selectedRegionCode.value = regionObj.code;
            await fetchProvinces(regionObj.code);
        }
    }

    if (props.filters.province) {
        const provinceObj = provinces.value.find((p) => p.name === props.filters.province);
        if (provinceObj) {
            selectedProvinceCode.value = provinceObj.code;
            await fetchCities(provinceObj.code);
        }
    }

    if (props.filters.municipality) {
        const cityObj = cities.value.find((c) => c.name === props.filters.municipality);
        if (cityObj) {
            selectedMunicipalityCode.value = cityObj.code;
        }
    }

    isHydratingFilters.value = false;
});

onUnmounted(() => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }
});

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '10';

    return String(Math.min(parsed, 500));
};

watch(selectedRegionCode, async (newCode) => {
    if (newCode && newCode !== 'all') {
        await fetchProvinces(newCode);
        selectedProvinceCode.value = 'all';
        selectedMunicipalityCode.value = 'all';
    } else {
        selectedProvinceCode.value = 'all';
        selectedMunicipalityCode.value = 'all';
        provinces.value = [];
        cities.value = [];
    }
});

watch(selectedProvinceCode, async (newCode) => {
    if (newCode && newCode !== 'all') {
        await fetchCities(newCode);
        selectedMunicipalityCode.value = 'all';
    } else {
        selectedMunicipalityCode.value = 'all';
        cities.value = [];
    }
});

watch(search, () => {
    if (isHydratingFilters.value) return;

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = setTimeout(() => {
        applyFilters(1);
    }, SEARCH_DEBOUNCE_MS);
});

watch([status, coopType, selectedRegionCode, selectedProvinceCode, selectedMunicipalityCode, perPage, customPerPage], () => {
    if (isHydratingFilters.value) return;
    applyFilters(1);
});

const goToPage = (targetPage: number) => {
    if (targetPage < 1 || targetPage > totalPages.value || targetPage === currentPage.value) return;
    applyFilters(targetPage);
};

const paginationItems = computed<Array<number | string>>(() => {
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis-right', total];
    }

    if (current >= total - 3) {
        return [1, 'ellipsis-left', total - 4, total - 3, total - 2, total - 1, total];
    }

    return [1, 'ellipsis-left', current - 1, current, current + 1, 'ellipsis-right', total];
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    coopType.value = 'all';
    selectedRegionCode.value = 'all';
    selectedProvinceCode.value = 'all';
    selectedMunicipalityCode.value = 'all';
    perPage.value = '10';
    customPerPage.value = '';
    applyFilters(1);
};

const deleteCooperative = async (cooperative: Cooperative) => {
    const confirmed = await confirmAction({
        title: 'Delete cooperative?',
        text: `Are you sure you want to delete ${cooperative.name}? This action cannot be undone.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/cooperatives/${cooperative.id}`, {
        preserveScroll: true,
    });
};

const goToCooperative = (cooperative: Cooperative) => {
    if (!canViewAllCoops.value) return;
    router.get(`/cooperatives/${cooperative.id}`);
};


const getStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Inactive': 'bg-gray-100 text-gray-800 border-gray-200',
        'Dissolved': 'bg-red-100 text-red-800 border-red-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getMemberCountBadgeColor = (memberCount: number) => {
    if (memberCount === 0) {
        return 'border-slate-300 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-300';
    }

    return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200';
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatFullAddress = (coop: Cooperative) => {
    const parts = [
        coop.address,
        coop.barangay ? `Brgy. ${coop.barangay}` : null,
        coop.city_municipality,
        coop.province,
        coop.region,
    ].filter(Boolean);
    
    return parts.join(', ') || 'N/A';
};

const getTypePreview = (coop: Cooperative) => {
    const names = coop.types?.map((type) => type.name) || [];

    if (!names.length) {
        return { first: 'N/A', extra: 0 };
    }

    return {
        first: names[0],
        extra: Math.max(names.length - 1, 0),
    };
};

    const visibleCooperatives = computed(() => props.cooperatives.data);

    const {
        allVisibleSelected,
        clearSelection,
        isSelected,
        selectedCount,
        selectedIds,
        toggleAll,
        toggleOne,
    } = useBulkSelection(visibleCooperatives);

    const bulkDeleteCooperatives = async () => {
        if (!selectedCount.value || !canBulkDelete.value) return;

        const confirmed = await confirmAction({
            title: `Delete selected ${cooperativeLabelLower.value}?`,
            text: `Delete ${selectedCount.value} selected ${cooperativeLabelLower.value} record(s)? This action cannot be undone.`,
            confirmButtonText: 'Delete selected',
        });

        if (!confirmed) return;

        const idsToDelete = [...selectedIds.value];
        await runBulkDelete(idsToDelete, (id) => `/cooperatives/${id}`);
        clearSelection();
    };
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-2xl border border-border/70 bg-linear-to-br from-background via-card to-muted/30 p-5 shadow-sm md:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div v-if="!isCoopAdminOnly" class="mb-3 inline-flex items-center gap-2 rounded-full border border-border/80 bg-background/80 px-3 py-1 text-xs font-semibold text-muted-foreground">
                            <Sparkles class="h-3.5 w-3.5" />
                            Step 1 of 2
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">{{ cooperativeManagementLabel }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground md:text-base">
                            Manage {{ cooperativeLabelLower }} master profiles
                        </p>
                        <p v-if="!isCoopAdminOnly" class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Select a cooperative to manage its members, officers, and committees.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge variant="outline" class="hidden sm:inline-flex">
                            {{ cooperatives.total }} {{ totalCooperativesLabel }}
                        </Badge>
                        <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                            <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                            <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteCooperatives">
                                <Trash2 class="h-3.5 w-3.5" />
                                Delete Selected
                            </Button>
                            <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                                Clear
                            </Button>
                        </div>
                        <Button
                            v-if="!isCoopAdminOnly"
                            type="button"
                            variant="outline"
                            class="gap-2"
                            @click="filtersVisible = !filtersVisible"
                        >
                            <SlidersHorizontal
                                class="h-4 w-4 transition-transform duration-200"
                                :class="filtersVisible ? 'rotate-90' : 'rotate-0'"
                            />
                            {{ filtersVisible ? 'Hide Filters' : 'Show Filters' }}
                        </Button>
                        <Link v-if="canCreateCoop" href="/cooperatives/create">
                            <Button class="gap-2">
                                <Plus class="h-4 w-4" />
                                Register Cooperative
                            </Button>
                        </Link>
                    </div>
                </div>

                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="!isCoopAdminOnly && filtersVisible" class="mt-6 border-t border-border/60 pt-6">
                        <div class="rounded-xl border border-border/80 bg-card p-4 shadow-sm">
                            <div class="flex flex-wrap items-end gap-3">
                                <div class="min-w-55 flex-1 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Search</label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                        <Input v-model="search" placeholder="Search cooperatives..." class="pl-9" />
                                    </div>
                                </div>

                                <div class="min-w-40 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Status</label>
                                    <Select v-model="status">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Statuses" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Statuses</SelectItem>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="Inactive">Inactive</SelectItem>
                                            <SelectItem value="Suspended">Suspended</SelectItem>
                                            <SelectItem value="Dissolved">Dissolved</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="min-w-40 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Type</label>
                                    <Select v-model="coopType">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Types" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Types</SelectItem>
                                            <SelectItem v-for="type in coopTypes" :key="type" :value="type">
                                                {{ type }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="min-w-45 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Region</label>
                                    <Select v-model="selectedRegionCode">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Regions" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Regions</SelectItem>
                                            <SelectItem
                                                v-for="regionItem in regions"
                                                :key="regionItem.code"
                                                :value="regionItem.code"
                                            >
                                                {{ regionItem.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="min-w-45 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Province</label>
                                    <Select v-model="selectedProvinceCode" :disabled="selectedRegionCode === 'all' || provinces.length === 0">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Provinces" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Provinces</SelectItem>
                                            <SelectItem
                                                v-for="provinceItem in provinces"
                                                :key="provinceItem.code"
                                                :value="provinceItem.code"
                                            >
                                                {{ provinceItem.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div class="min-w-45 space-y-1">
                                    <label class="text-sm font-medium text-foreground">Municipality</label>
                                    <Select v-model="selectedMunicipalityCode" :disabled="selectedProvinceCode === 'all' || cities.length === 0">
                                        <SelectTrigger>
                                            <SelectValue placeholder="All Municipalities" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Municipalities</SelectItem>
                                            <SelectItem
                                                v-for="municipalityItem in cities"
                                                :key="municipalityItem.code"
                                                :value="municipalityItem.code"
                                            >
                                                {{ municipalityItem.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button
                                    v-if="hasActiveFilters"
                                    type="button"
                                    variant="outline"
                                    class="ml-auto gap-2"
                                    @click="resetFilters"
                                >
                                    <RotateCcw class="h-4 w-4" />
                                    Clear Filters
                                </Button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </section>

            <Card v-if="isCoopAdminOnly" class="border-border/80 bg-card shadow-sm">
                <CardHeader>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle class="text-lg font-semibold text-foreground">My Cooperative Profile</CardTitle>
                            <CardDescription>Official cooperative registration details.</CardDescription>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge
                                v-if="coopProfile"
                                :class="[getStatusBadgeColor(coopProfile.status), 'rounded-md border px-2 py-1 text-xs font-semibold']"
                            >
                                {{ coopProfile.status }}
                            </Badge>
                            <Link v-if="coopProfile && canEditCoop" :href="currentUrl ? `/cooperatives/${coopProfile.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/cooperatives/${coopProfile.id}/edit`">
                                <Button class="gap-2">
                                    <Pencil class="h-4 w-4" />
                                    Edit Cooperative
                                </Button>
                            </Link>
                        </div>
                    </div>
                </CardHeader>

                <CardContent>
                    <div v-if="coopProfile" class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-border bg-muted/40 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Registration</div>
                            <div class="mt-2 space-y-1 text-sm text-foreground">
                                <div><strong>Name:</strong> {{ coopProfile.name }}</div>
                                <div><strong>Registration #:</strong> {{ coopProfile.registration_number }}</div>
                                <div>
                                    <strong>Type:</strong>
                                    {{ coopProfile.types?.length ? coopProfile.types.map((t) => t.name).join(', ') : 'N/A' }}
                                </div>
                                <div><strong>Date Established:</strong> {{ formatDate(coopProfile.date_established) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-border bg-muted/40 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Contact</div>
                            <div class="mt-2 space-y-1 text-sm text-foreground">
                                <div><strong>Email:</strong> {{ coopProfile.email || 'N/A' }}</div>
                                <div><strong>Phone:</strong> {{ coopProfile.phone || 'N/A' }}</div>
                                <div><strong>Address:</strong> {{ formatFullAddress(coopProfile) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-border bg-muted/40 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Accreditation</div>
                            <div class="mt-2 space-y-1 text-sm text-foreground">
                                <div><strong>Latest Level:</strong> {{ coopProfile.latest_accreditation?.level || 'N/A' }}</div>
                                <div><strong>Date Granted:</strong> {{ formatDate(coopProfile.latest_accreditation?.date_granted || null) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-border bg-muted/40 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Jurisdiction</div>
                            <div class="mt-2 space-y-1 text-sm text-foreground">
                                <div><strong>Province:</strong> {{ coopProfile.province }}</div>
                                <div><strong>City/Municipality:</strong> {{ coopProfile.city_municipality || 'N/A' }}</div>
                                <div><strong>Barangay:</strong> {{ coopProfile.barangay || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-dashed border-orange-300/60 bg-orange-100/40 px-4 py-3 text-sm text-orange-800 dark:border-orange-500/50 dark:bg-orange-500/15 dark:text-orange-200">
                        No cooperative is assigned to this account yet. Please contact your system administrator.
                    </div>
                </CardContent>
            </Card>

            <Card v-else class="overflow-hidden border-border/80 bg-card shadow-sm">
                <CardHeader class="pb-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <CardTitle class="text-base font-semibold text-foreground">{{ availableCooperativesLabel }}</CardTitle>
                        <Badge variant="secondary">{{ cooperatives.total }} records</Badge>
                    </div>
                    <CardDescription>Open a cooperative profile to continue to members, officers, and committees management.</CardDescription>
                </CardHeader>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead v-if="canBulkDelete" class="w-12">
                                        <Checkbox
                                            :model-value="allVisibleSelected"
                                            :disabled="cooperatives.data.length === 0"
                                            :aria-label="`Select all ${cooperativeLabelLower}`"
                                            @update:model-value="toggleAll"
                                        />
                                    </TableHead>
                                    <TableHead>Cooperative</TableHead>
                                    <TableHead class="text-center">No. Members</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Accreditation</TableHead>
                                    <TableHead>Established</TableHead>
                                    <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="isLoading">
                                    <TableRow v-for="rowIndex in 6" :key="`coop-loading-${rowIndex}`">
                                        <TableCell v-if="canBulkDelete" class="w-12">
                                            <div class="h-4 w-4 rounded bg-muted animate-pulse" />
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-3">
                                                <div class="h-10 w-10 rounded-full bg-muted animate-pulse" />
                                                <div class="space-y-2">
                                                    <div class="h-4 w-44 rounded bg-muted animate-pulse" />
                                                    <div class="h-3 w-28 rounded bg-muted animate-pulse" />
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center"><div class="mx-auto h-5 w-14 rounded-full bg-muted animate-pulse" /></TableCell>
                                        <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                        <TableCell><div class="h-4 w-36 rounded bg-muted animate-pulse" /></TableCell>
                                        <TableCell><div class="h-5 w-20 rounded bg-muted animate-pulse" /></TableCell>
                                        <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                        <TableCell><div class="h-4 w-24 rounded bg-muted animate-pulse" /></TableCell>
                                        <TableCell v-if="showActions"><div class="mx-auto h-8 w-44 rounded bg-muted animate-pulse" /></TableCell>
                                    </TableRow>
                                </template>

                                <TableRow v-else-if="cooperatives.data.length === 0">
                                    <TableCell :colspan="(showActions ? 8 : 7) + (canBulkDelete ? 1 : 0)" class="py-10 text-center text-muted-foreground">
                                        <div class="mx-auto max-w-md space-y-3">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                <SearchX class="h-6 w-6" />
                                            </div>
                                            <p class="font-medium text-foreground">No results found</p>
                                            <p class="text-sm text-muted-foreground">Try adjusting your filters or search terms.</p>
                                            <Button v-if="hasActiveFilters" type="button" variant="outline" class="gap-2" @click="resetFilters">
                                                <RotateCcw class="h-4 w-4" />
                                                Clear Filters
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="coop in cooperatives.data"
                                    :key="coop.id"
                                    class="cursor-pointer"
                                    @click="goToCooperative(coop)"
                                >
                                    <TableCell v-if="canBulkDelete" class="w-12">
                                        <Checkbox
                                            :model-value="isSelected(coop.id)"
                                            :aria-label="`Select ${coop.name}`"
                                            @click.stop
                                            @update:model-value="(checked) => toggleOne(coop.id, checked)"
                                        />
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <Building2 class="h-5 w-5" />
                                            </div>
                                            <div>
                                                <TooltipProvider :delay-duration="150">
                                                    <Tooltip>
                                                        <TooltipTrigger as-child>
                                                            <div class="max-w-60 truncate text-foreground">{{ coop.name }}</div>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <p>{{ coop.name }}</p>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <Badge
                                            :class="[
                                                getMemberCountBadgeColor(coop.members_count ?? 0),
                                                'rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                                            ]"
                                        >
                                            {{ coop.members_count ?? 0 }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <Badge variant="outline">
                                                {{ getTypePreview(coop).first }}
                                            </Badge>
                                            <Badge v-if="getTypePreview(coop).extra > 0" variant="secondary">
                                                +{{ getTypePreview(coop).extra }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        <TooltipProvider :delay-duration="150">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <div class="text-sm">
                                                        <div class="max-w-45 truncate font-medium text-foreground">{{ coop.city_municipality || coop.province }}</div>
                                                        <div v-if="coop.city_municipality" class="max-w-45 truncate text-xs text-muted-foreground">{{ coop.province }}</div>
                                                    </div>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>{{ formatFullAddress(coop) }}</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadgeColor(coop.status)" class="border">
                                            {{ coop.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="coop.latest_accreditation" class="text-sm">
                                            <div class="text-foreground">{{ coop.latest_accreditation.level }}</div>
                                            <div class="text-xs text-muted-foreground">{{ formatDate(coop.latest_accreditation.date_granted || null) }}</div>
                                        </div>
                                        <span v-else class="text-muted-foreground">N/A</span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ formatDate(coop.date_established) }}
                                    </TableCell>
                                    <TableCell v-if="showActions" class="text-center">
                                        <TooltipProvider :delay-duration="150">
                                            <div class="flex flex-wrap justify-center gap-2">
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Link :href="`/cooperatives/${coop.id}`" @click.stop>
                                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-1">
                                                                <Eye class="h-3 w-3" />
                                                                View
                                                            </Button>
                                                        </Link>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>View details</p></TooltipContent>
                                                </Tooltip>

                                                <Tooltip v-if="canEditCoop">
                                                    <TooltipTrigger as-child>
                                                        <Link :href="currentUrl ? `/cooperatives/${coop.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/cooperatives/${coop.id}/edit`" @click.stop>
                                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-1">
                                                                <Pencil class="h-3 w-3" />
                                                                Edit
                                                            </Button>
                                                        </Link>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>Edit this record</p></TooltipContent>
                                                </Tooltip>

                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button asChild variant="ghost" size="sm" class="table-action-btn table-action-other gap-1">
                                                            <a
                                                                :href="`/cooperatives/${coop.id}/report`"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                @click.stop
                                                                class="inline-flex items-center gap-1"
                                                            >
                                                                <FileText class="h-3 w-3" />
                                                                Report
                                                            </a>
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>Open report in new tab</p></TooltipContent>
                                                </Tooltip>

                                                <Tooltip v-if="canDeleteCoop">
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            @click.stop="deleteCooperative(coop)"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="table-action-btn table-action-delete gap-1 text-red-600 hover:text-red-700"
                                                        >
                                                            <Trash2 class="h-3 w-3" />
                                                            Delete
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent><p>Delete this record</p></TooltipContent>
                                                </Tooltip>
                                            </div>
                                        </TooltipProvider>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="border-t border-border px-4 py-4">
                        <div class="mb-3 flex items-center justify-center text-sm text-muted-foreground">
                            <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                            Showing {{ showingFrom }}-{{ showingTo }} of {{ cooperatives.total }} results
                        </div>

                        <div class="grid gap-3 md:grid-cols-[1fr_auto_1fr] md:items-center">
                            <div class="flex items-center gap-2 md:justify-start">
                                <span class="text-sm text-muted-foreground">Show</span>
                                <Select v-model="perPage">
                                    <SelectTrigger class="w-24">
                                        <SelectValue placeholder="Rows" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                        <SelectItem value="custom">Custom</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Input
                                    v-if="perPage === 'custom'"
                                    v-model="customPerPage"
                                    type="number"
                                    min="1"
                                    max="500"
                                    class="w-20"
                                />
                                <span class="text-sm text-muted-foreground">per page</span>
                            </div>

                            <div class="flex justify-center">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1"
                                        :disabled="currentPage <= 1"
                                        :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                        @click="goToPage(1)"
                                    >
                                        <ChevronsLeft class="h-4 w-4" />
                                        First
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1"
                                        :disabled="currentPage <= 1"
                                        :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                        @click="goToPage(currentPage - 1)"
                                    >
                                        <ChevronLeft class="h-4 w-4" />
                                        Previous
                                    </Button>

                                    <template v-for="item in paginationItems" :key="`page-item-${item}`">
                                        <span v-if="typeof item !== 'number'" class="px-1 text-sm text-muted-foreground">...</span>
                                        <Button
                                            v-else
                                            type="button"
                                            :variant="item === currentPage ? 'default' : 'outline'"
                                            size="sm"
                                            class="min-w-9"
                                            @click="goToPage(item)"
                                        >
                                            {{ item }}
                                        </Button>
                                    </template>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1"
                                        :disabled="currentPage >= totalPages"
                                        :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                        @click="goToPage(currentPage + 1)"
                                    >
                                        Next
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        class="gap-1"
                                        :disabled="currentPage >= totalPages"
                                        :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                        @click="goToPage(totalPages)"
                                    >
                                        Last
                                        <ChevronsRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div class="hidden md:block" />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
