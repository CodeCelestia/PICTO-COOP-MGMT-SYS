<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Building2, Plus, Pencil, Trash2, Search, Filter } from 'lucide-vue-next';
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
import { Badge } from '@/components/ui/badge';
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
        coop_type: string;
        province: string;
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

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const coopType = ref(props.filters.coop_type || '');
const province = ref(props.filters.province || '');

const applyFilters = () => {
    router.get('/cooperatives', {
        search: search.value,
        status: status.value,
        coop_type: coopType.value,
        province: province.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    status.value = '';
    coopType.value = '';
    province.value = '';
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

const provinces = [
    'Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan', 'Batanes', 'Cagayan',
    'Isabela', 'Nueva Vizcaya', 'Quirino', 'Aurora', 'Bataan', 'Bulacan', 'Nueva Ecija',
    'Pampanga', 'Tarlac', 'Zambales', 'Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal',
    'Marinduque', 'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Romblon',
    'Albay', 'Camarines Norte', 'Camarines Sur', 'Catanduanes', 'Masbate', 'Sorsogon',
    'Aklan', 'Antique', 'Capiz', 'Guimaras', 'Iloilo', 'Negros Occidental',
    'Bohol', 'Cebu', 'Negros Oriental', 'Siquijor',
    'Eastern Samar', 'Leyte', 'Northern Samar', 'Samar', 'Southern Leyte', 'Biliran',
    'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay',
    'Bukidnon', 'Camiguin', 'Lanao del Norte', 'Misamis Occidental', 'Misamis Oriental',
    'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
    'Cotabato', 'Sarangani', 'South Cotabato', 'Sultan Kudarat',
    'Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte', 'Surigao del Sur',
    'Basilan', 'Lanao del Sur', 'Maguindanao', 'Sulu', 'Tawi-Tawi',
    'Abra', 'Apayao', 'Benguet', 'Ifugao', 'Kalinga', 'Mountain Province',
    'Metro Manila'
];

const coopTypes = [
    'Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose',
    'Advocacy', 'Agrarian Reform', 'Dairy', 'Education', 'Electric', 'Fishermen',
    'Health Services', 'Housing', 'Insurance', 'Laboratory', 'Transport', 'Water Service', 'Workers'
];
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
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
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
                                <SelectItem value="">All Statuses</SelectItem>
                                <SelectItem value="Active">Active</SelectItem>
                                <SelectItem value="Inactive">Inactive</SelectItem>
                                <SelectItem value="Suspended">Suspended</SelectItem>
                                <SelectItem value="Dissolved">Dissolved</SelectItem>
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
                                <SelectItem value="">All Types</SelectItem>
                                <SelectItem v-for="type in coopTypes" :key="type" :value="type">
                                    {{ type }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Province</label>
                        <Select v-model="province">
                            <SelectTrigger>
                                <SelectValue placeholder="All Provinces" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Provinces</SelectItem>
                                <SelectItem v-for="prov in provinces" :key="prov" :value="prov">
                                    {{ prov }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
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

            <!-- Table -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
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
                            <TableHead v-if="showActions" class="text-right">Actions</TableHead>
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
                                <Badge variant="outline">{{ coop.coop_type }}</Badge>
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
                            <TableCell v-if="showActions" class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link v-if="canEditCoop" :href="`/cooperatives/${coop.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-1">
                                            <Pencil class="h-3 w-3" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDeleteCoop"
                                        @click="deleteCooperative(coop)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        Delete
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
                                @click="router.get(`/cooperatives?page=${page}`)"
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
