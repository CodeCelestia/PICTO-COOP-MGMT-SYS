<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Building2, Plus, Pencil, Trash2, Search, Filter, RotateCcw } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { usePsgc } from '@/composables/usePsgc';
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
    registration_number: string;
    coop_type: string;
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

interface Props {
    cooperatives: {
        data: Cooperative[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
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

const page = usePage();
const roles = computed<string[]>(() => (page.props.auth?.roles as string[]) || []);
const accountType = computed(() => page.props.auth?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(page.props.auth?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const canCreateCoop = computed(() => isProvincialAdmin.value);
const canEditCoop = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canDeleteCoop = computed(() => isProvincialAdmin.value);
const showActions = computed(() => canEditCoop.value || canDeleteCoop.value);
const isCoopAdminOnly = computed(() => isCoopAdmin.value && !isProvincialAdmin.value);
const coopProfile = computed(() => props.cooperatives.data[0] || null);
const isArchivedView = computed(() => status.value === 'Archived');

const coopTypes = [
    'Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose',
    'Advocacy', 'Agrarian Reform', 'Dairy', 'Education', 'Electric', 'Fishermen',
    'Health Services', 'Housing', 'Insurance', 'Laboratory', 'Transport', 'Water Service', 'Workers'
];

const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

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
        const region = regions.value.find((r) => r.code === newCode);
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
        const province = provinces.value.find((p) => p.code === newCode);
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
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Cooperative Management</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage cooperative master profiles
                    </p>
                </div>
                <Link v-if="canCreateCoop" href="/cooperatives/create">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        Register Cooperative
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div v-if="!isCoopAdminOnly" class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Name, Reg #, Province..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Type</label>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Region</label>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Province</label>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Municipality</label>
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
                    <Button @click="applyFilters" variant="default" class="gap-2">
                        <Filter class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Coop Admin Profile -->
            <div v-if="isCoopAdminOnly" class="grid gap-4">
                <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">My Cooperative Profile</h2>
                            <p class="text-sm text-slate-500">Official cooperative registration details.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                v-if="coopProfile"
                                :class="[getStatusBadgeColor(coopProfile.status), 'rounded-md border px-2 py-1 text-xs font-semibold']"
                            >
                                {{ coopProfile.status }}
                            </span>
                            <Link v-if="coopProfile && canEditCoop" :href="`/cooperatives/${coopProfile.id}/edit`">
                                <Button class="gap-2">
                                    <Pencil class="h-4 w-4" />
                                    Edit Cooperative
                                </Button>
                            </Link>
                        </div>
                    </div>

                    <div v-if="coopProfile" class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Registration</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div><strong>Name:</strong> {{ coopProfile.name }}</div>
                                <div><strong>Registration #:</strong> {{ coopProfile.registration_number }}</div>
                                <div><strong>Type:</strong> {{ coopProfile.coop_type }}</div>
                                <div><strong>Date Established:</strong> {{ formatDate(coopProfile.date_established) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Contact</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div><strong>Email:</strong> {{ coopProfile.email || 'N/A' }}</div>
                                <div><strong>Phone:</strong> {{ coopProfile.phone || 'N/A' }}</div>
                                <div><strong>Address:</strong> {{ formatFullAddress(coopProfile) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Accreditation</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div><strong>Status:</strong> {{ coopProfile.accreditation_status || 'N/A' }}</div>
                                <div><strong>Date:</strong> {{ formatDate(coopProfile.accreditation_date) }}</div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200/70 bg-slate-50/60 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Jurisdiction</div>
                            <div class="mt-2 space-y-1 text-sm text-slate-700">
                                <div><strong>Province:</strong> {{ coopProfile.province }}</div>
                                <div><strong>City/Municipality:</strong> {{ coopProfile.city_municipality || 'N/A' }}</div>
                                <div><strong>Barangay:</strong> {{ coopProfile.barangay || 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-6 rounded-lg border border-dashed border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                        No cooperative is assigned to this account yet. Please contact your system administrator.
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div v-else class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Registration #</TableHead>
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
                            <TableCell :colspan="showActions ? 8 : 7" class="text-center text-gray-500">
                                No cooperatives found
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="coop in cooperatives.data" :key="coop.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <Building2 class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div>{{ coop.name }}</div>
                                        <div class="text-xs text-gray-500">{{ coop.email || 'No email' }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="font-mono text-sm text-gray-600">
                                {{ coop.registration_number }}
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">
                                    {{ coop.types?.length ? coop.types.map(t => t.name).join(', ') : coop.coop_type }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-gray-600">
                                <div class="text-sm">
                                    <div class="font-medium">{{ coop.city_municipality || coop.province }}</div>
                                    <div v-if="coop.city_municipality" class="text-xs text-gray-500">{{ coop.province }}</div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusBadgeColor(coop.status)" class="border">
                                    {{ coop.status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div v-if="coop.accreditation_status" class="text-sm">
                                    <div>{{ coop.accreditation_status }}</div>
                                    <div class="text-xs text-gray-500">{{ formatDate(coop.accreditation_date) }}</div>
                                </div>
                                <span v-else class="text-gray-500">N/A</span>
                            </TableCell>
                            <TableCell class="text-gray-600">
                                {{ formatDate(coop.date_established) }}
                            </TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEditCoop" :href="`/cooperatives/${coop.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-1">
                                            <Pencil class="h-3 w-3" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDeleteCoop && !isArchivedView"
                                        @click="deleteCooperative(coop)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        Delete
                                    </Button>
                                    <Button
                                        v-if="canDeleteCoop && isArchivedView"
                                        @click="restoreCooperative(coop)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1 text-emerald-600 hover:text-emerald-700"
                                    >
                                        <RotateCcw class="h-3 w-3" />
                                        Restore
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="cooperatives.last_page > 1" class="border-t border-gray-200 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (cooperatives.current_page - 1) * cooperatives.per_page + 1 }} to
                            {{ Math.min(cooperatives.current_page * cooperatives.per_page, cooperatives.total) }} of
                            {{ cooperatives.total }} cooperatives
                        </div>
                        <div class="flex gap-2">
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
                                })"
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
