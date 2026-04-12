<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Building2, Eye, Filter, Pencil, Plus, RotateCcw, Search, Sparkles, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    classification: string | null;
    date_established: string;
    address: string;
    province: string;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended';
    accreditation_status: string | null;
    accreditation_date: string | null;
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cooperative Management',
        href: '/cooperatives',
    },
];

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canViewAllCoops = computed(() => permissions.value.includes('view-all-cooperatives'));
const canCreateCoop = computed(() => permissions.value.includes('create coop-master-profile'));
const canEditCoop = computed(() => permissions.value.includes('update coop-master-profile'));
const canDeleteCoop = computed(() => permissions.value.includes('delete coop-master-profile'));
const canBulkDelete = computed(() => canDeleteCoop.value && !isArchivedView.value && !isCoopAdminOnly.value);
const showActions = computed(() => canEditCoop.value || canDeleteCoop.value || canViewAllCoops.value);
const isCoopAdminOnly = computed(() => !canViewAllCoops.value);
const coopProfile = computed(() => props.cooperatives.data[0] || null);
const isArchivedView = computed(() => status.value === 'Archived');

const coopTypes = computed(() => props.cooperativeTypes.map((type) => type.name));

const { regions, provinces, cities, fetchRegions, fetchProvinces, fetchCities } = usePsgc();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const coopType = ref(props.filters.coop_type || 'all');
const selectedRegionCode = ref('all');
const selectedProvinceCode = ref('all');
const selectedMunicipalityCode = ref('all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.cooperatives.per_page || 20);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

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
});

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

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

const applyFilters = () => {
    const regionName = (selectedRegionCode.value !== 'all' && regions.value.find((r) => r.code === selectedRegionCode.value)?.name) || '';
    const provinceName = (selectedProvinceCode.value !== 'all' && provinces.value.find((p) => p.code === selectedProvinceCode.value)?.name) || '';
    const municipalityName = (selectedMunicipalityCode.value !== 'all' && cities.value.find((c) => c.code === selectedMunicipalityCode.value)?.name) || '';

    router.get('/cooperatives', {
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        coop_type: coopType.value === 'all' ? '' : coopType.value,
        region: regionName,
        province: provinceName,
        municipality: municipalityName,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    coopType.value = 'all';
    selectedRegionCode.value = 'all';
    selectedProvinceCode.value = 'all';
    selectedMunicipalityCode.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/cooperatives');
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

const restoreCooperative = async (cooperative: Cooperative) => {
    const confirmed = await confirmAction({
        title: 'Restore cooperative?',
        text: `Restore ${cooperative.name} to active records?`,
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post(`/cooperatives/${cooperative.id}/restore`, {}, {
        preserveScroll: true,
    });
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
            title: 'Delete selected cooperatives?',
            text: `Delete ${selectedCount.value} selected cooperative record(s)? This action cannot be undone.`,
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
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Cooperative Management</h1>
                        <p class="mt-1 text-sm text-muted-foreground md:text-base">
                            Manage cooperative master profiles
                        </p>
                        <p v-if="!isCoopAdminOnly" class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Select a cooperative to manage its members, officers, and committees.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge variant="outline" class="hidden sm:inline-flex">
                            {{ cooperatives.total }} total cooperatives
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
                        <Link v-if="canCreateCoop" href="/cooperatives/create">
                            <Button class="gap-2">
                                <Plus class="h-4 w-4" />
                                Register Cooperative
                            </Button>
                        </Link>
                    </div>
                </div>

                <div v-if="!isCoopAdminOnly" class="mt-6 border-t border-border/60 pt-6">
                <FilterPanel
                    title="Filters"
                    description="Show filter fields to refine cooperative results."
                    showLabel="Show filters"
                    hideLabel="Hide filters"
                >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Name, Reg #, Province..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
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
                                <SelectItem value="Archived">Archived</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Type</label>
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
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Region</label>
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
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Province</label>
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
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Municipality</label>
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
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Rows Per Page</label>
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

                <div class="flex flex-wrap gap-2">
                    <Button @click="applyFilters" variant="default" class="gap-2">
                        <Filter class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline" class="gap-2">
                        <Search class="h-4 w-4" />
                        Reset
                    </Button>
                </div>
                </FilterPanel>
                </div>
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
                            <Link v-if="coopProfile && canEditCoop" :href="`/cooperatives/${coopProfile.id}/edit`">
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
                                <div><strong>Classification:</strong> {{ coopProfile.classification || 'N/A' }}</div>
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
                                <div><strong>Status:</strong> {{ coopProfile.accreditation_status || 'N/A' }}</div>
                                <div><strong>Date:</strong> {{ formatDate(coopProfile.accreditation_date) }}</div>
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
                    <CardTitle class="text-base font-semibold text-foreground">Available Cooperatives</CardTitle>
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
                                            aria-label="Select all cooperatives"
                                            @update:model-value="toggleAll"
                                        />
                                    </TableHead>
                                    <TableHead>Cooperative</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Accreditation</TableHead>
                                    <TableHead>Established</TableHead>
                                    <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="cooperatives.data.length === 0">
                                    <TableCell :colspan="(showActions ? 7 : 6) + (canBulkDelete ? 1 : 0)" class="py-10 text-center text-muted-foreground">
                                        <div class="mx-auto max-w-md space-y-2">
                                            <p class="font-medium text-foreground">No cooperatives matched your current filters.</p>
                                            <p class="text-sm text-muted-foreground">Try clearing filters or searching by province or registration number.</p>
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
                                                <div class="text-foreground">{{ coop.name }}</div>
                                            </div>
                                        </div>
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
                                        <div class="text-sm">
                                            <div class="font-medium text-foreground">{{ coop.city_municipality || coop.province }}</div>
                                            <div v-if="coop.city_municipality" class="text-xs text-muted-foreground">{{ coop.province }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadgeColor(coop.status)" class="border">
                                            {{ coop.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div v-if="coop.accreditation_status" class="text-sm">
                                            <div class="text-foreground">{{ coop.accreditation_status }}</div>
                                            <div class="text-xs text-muted-foreground">{{ formatDate(coop.accreditation_date) }}</div>
                                        </div>
                                        <span v-else class="text-muted-foreground">N/A</span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">
                                        {{ formatDate(coop.date_established) }}
                                    </TableCell>
                                    <TableCell v-if="showActions" class="text-center">
                                        <div class="flex flex-wrap justify-center gap-2">
                                            <Link :href="`/cooperatives/${coop.id}`" @click.stop>
                                                <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-1">
                                                    <Eye class="h-3 w-3" />
                                                    View
                                                </Button>
                                            </Link>
                                            <Link v-if="canEditCoop" :href="`/cooperatives/${coop.id}/edit`" @click.stop>
                                                <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-1">
                                                    <Pencil class="h-3 w-3" />
                                                    Edit
                                                </Button>
                                            </Link>
                                            <Button
                                                v-if="canDeleteCoop && !isArchivedView"
                                                @click="deleteCooperative(coop)"
                                                @click.stop
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-delete gap-1 text-red-600 hover:text-red-700"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                Delete
                                            </Button>
                                            <Button
                                                v-if="canDeleteCoop && isArchivedView"
                                                @click="restoreCooperative(coop)"
                                                @click.stop
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-other gap-1 text-emerald-600 hover:text-emerald-700"
                                            >
                                                <RotateCcw class="h-3 w-3" />
                                                Restore
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div v-if="cooperatives.last_page > 1" class="border-t border-border px-4 py-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ (cooperatives.current_page - 1) * cooperatives.per_page + 1 }} to
                                {{ Math.min(cooperatives.current_page * cooperatives.per_page, cooperatives.total) }} of
                                {{ cooperatives.total }} cooperatives
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="page in cooperatives.last_page"
                                    :key="page"
                                    :variant="page === cooperatives.current_page ? 'default' : 'outline'"
                                    size="sm"
                                    @click="router.get('/cooperatives', {
                                        page,
                                        search: search || '',
                                        status: status === 'all' ? '' : status,
                                        coop_type: coopType === 'all' ? '' : coopType,
                                        region: (selectedRegionCode !== 'all' && regions.find((r) => r.code === selectedRegionCode)?.name) || '',
                                        province: (selectedProvinceCode !== 'all' && provinces.find((p) => p.code === selectedProvinceCode)?.name) || '',
                                        municipality: (selectedMunicipalityCode !== 'all' && cities.find((c) => c.code === selectedMunicipalityCode)?.name) || '',
                                        per_page: resolvedPerPage(),
                                    }, { preserveScroll: true, preserveState: true })"
                                >
                                    {{ page }}
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
