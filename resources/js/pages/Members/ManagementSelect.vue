<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2, Filter, Search, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { useCoopLabel } from '@/composables/useCoopLabel';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import type { BreadcrumbItem } from '@/types';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    coop_type: string;
    date_established: string;
    province: string;
    region: string | null;
    city_municipality: string | null;
    status: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended';
    accreditation_status: string | null;
    accreditation_date: string | null;
    types?: Array<{ id: number; name: string }>;
}

interface Props {
    cooperatives: {
        data: Cooperative[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        coop_type?: string;
        region?: string;
        province?: string;
        municipality?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();
const page = usePage();
const {
    availableCooperativesLabel,
    cooperativeLabelLower,
    noCooperativesFoundLabel,
    totalCooperativesLabel,
} = useCoopLabel();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Members Management',
        href: '/members/management',
    },
    {
        title: 'Choose Cooperative',
        href: '/members/management',
    },
];

const coopTypes = [
    'Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose',
    'Advocacy', 'Agrarian Reform', 'Dairy', 'Education', 'Electric', 'Fishermen',
    'Health Services', 'Housing', 'Insurance', 'Laboratory', 'Transport', 'Water Service', 'Workers',
];

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

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '20';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    const regionName = (selectedRegionCode.value !== 'all' && regions.value.find((r) => r.code === selectedRegionCode.value)?.name) || '';
    const provinceName = (selectedProvinceCode.value !== 'all' && provinces.value.find((p) => p.code === selectedProvinceCode.value)?.name) || '';
    const municipalityName = (selectedMunicipalityCode.value !== 'all' && cities.value.find((c) => c.code === selectedMunicipalityCode.value)?.name) || '';

    router.get('/members/management', {
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

watch(status, (newStatus, oldStatus) => {
    if (newStatus === oldStatus) return;

    if (newStatus === 'Archived' || oldStatus === 'Archived') {
        applyFilters();
    }
});

const resetFilters = () => {
    search.value = '';
    status.value = 'all';
    coopType.value = 'all';
    selectedRegionCode.value = 'all';
    selectedProvinceCode.value = 'all';
    selectedMunicipalityCode.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/members/management');
};

const goToManagement = (coopId: number) => {
    router.get(`/members/management/${coopId}`);
};

const getStatusBadgeColor = (coopStatus: string) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Inactive': 'bg-gray-100 text-gray-800 border-gray-200',
        'Dissolved': 'bg-red-100 text-red-800 border-red-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
    };
    return colors[coopStatus] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-2xl border border-border/70 bg-gradient-to-br from-background via-card to-muted/30 p-5 shadow-sm md:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-border/80 bg-background/80 px-3 py-1 text-xs font-semibold text-muted-foreground">
                            <Sparkles class="h-3.5 w-3.5" />
                            Step 1 of 2
                        </div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Members Management</h1>
                        <p class="mt-1 text-sm text-muted-foreground md:text-base">
                            Choose a cooperative to view and manage its members.
                        </p>
                        <p class="mt-2 max-w-3xl text-sm text-muted-foreground">
                            Use the search and filters below to narrow down {{ cooperativeLabelLower }}, then open one to manage members, services, activities, and trainings in one place.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge variant="outline" class="hidden sm:inline-flex">
                            {{ cooperatives.total }} {{ totalCooperativesLabel }}
                        </Badge>
                    </div>
                </div>

                <div class="mt-6 border-t border-border/60 pt-6">
                <FilterPanel
                    title="Filters"
                    :description="`Show filter fields to refine ${cooperativeLabelLower} results.`"
                    showLabel="Show filters"
                    hideLabel="Hide filters"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-foreground">Search</label>
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
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
                                    <SelectItem v-for="regionItem in regions" :key="regionItem.code" :value="regionItem.code">
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
                                    <SelectItem v-for="provinceItem in provinces" :key="provinceItem.code" :value="provinceItem.code">
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
                                    <SelectItem v-for="municipalityItem in cities" :key="municipalityItem.code" :value="municipalityItem.code">
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

            <Card class="overflow-hidden border-border/80 bg-card shadow-sm">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold text-foreground">{{ availableCooperativesLabel }}</CardTitle>
                    <CardDescription>Select one cooperative to continue to Step 2: member management.</CardDescription>
                </CardHeader>

                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cooperative</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Accreditation</TableHead>
                                    <TableHead>Established</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="cooperatives.data.length === 0">
                                    <TableCell colspan="7" class="py-10 text-center text-muted-foreground">
                                        <div class="mx-auto max-w-md space-y-2">
                                            <p class="font-medium text-foreground">{{ noCooperativesFoundLabel }}</p>
                                            <p class="text-sm text-muted-foreground">
                                                Try clearing filters or searching with a broader keyword like a province name.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="coop in cooperatives.data"
                                    :key="coop.id"
                                    class="cursor-pointer"
                                    @click="goToManagement(coop.id)"
                                >
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
                                        <Badge variant="outline">
                                            {{ coop.types?.length ? coop.types.map((t) => t.name).join(', ') : coop.coop_type }}
                                        </Badge>
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
                                    <TableCell class="text-center">
                                        <Button size="sm" variant="outline" class="table-action-btn table-action-other gap-1.5" @click.stop="goToManagement(coop.id)">
                                            Open Members
                                            <ArrowRight class="h-4 w-4" />
                                        </Button>
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
                                {{ cooperatives.total }} {{ cooperativeLabelLower }}
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="page in cooperatives.last_page"
                                    :key="page"
                                    :variant="page === cooperatives.current_page ? 'default' : 'outline'"
                                    size="sm"
                                    @click="router.get('/members/management', {
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
