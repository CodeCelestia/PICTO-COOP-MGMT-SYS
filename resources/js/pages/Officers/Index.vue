<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search, RotateCcw } from 'lucide-vue-next';
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

interface Member {
    id: number;
    full_name: string;
}

interface Officer {
    id: number;
    member_id: number;
    coop_id: number;
    position: string;
    committee: string | null;
    term_start: string | null;
    term_end: string | null;
    status: string;
    member: Member;
    cooperative: Cooperative;
}

interface Props {
    officers: {
        data: Officer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { roles?: string[]; isCoopAdmin?: boolean; user?: { account_type?: string } } | undefined);
const roles = computed<string[]>(() => auth.value?.roles || []);
const accountType = computed(() => auth.value?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const canCreateOfficer = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEditOfficer = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDeleteOfficer = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEditOfficer.value || canDeleteOfficer.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');
const isArchivedView = computed(() => status.value === 'Archived');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.officers.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    router.get('/officers', {
        search: search.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        status: status.value === 'all' ? '' : status.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    coopId.value = 'all';
    status.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/officers');
};

const deleteOfficer = async (officer: Officer) => {
    const confirmed = await confirmAction({
        title: 'Delete officer record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/officers/${officer.id}`, {
        preserveScroll: true,
    });
};

const restoreOfficer = async (officer: Officer) => {
    const confirmed = await confirmAction({
        title: 'Restore officer record?',
        text: `Restore ${officer.member.full_name} to active records?`,
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post(`/officers/${officer.id}/restore`, {}, {
        preserveScroll: true,
    });
};

const formatTerm = (start: string | null, end: string | null) => {
    if (!start && !end) return 'N/A';
    if (start && end) return `${start} - ${end}`;
    return start || end || 'N/A';
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Officers & Committees</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage officer assignments and committees</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/committee-members" class="text-sm text-blue-600 hover:underline">
                        View Committee Members
                    </Link>
                    <Link v-if="canCreateOfficer" href="/officers/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Officer
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
                                placeholder="Officer name..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="Active">Active</SelectItem>
                                <SelectItem value="Retired">Retired</SelectItem>
                                <SelectItem value="Removed">Removed</SelectItem>
                                <SelectItem value="Resigned">Resigned</SelectItem>
                                <SelectItem value="Archived">Archived</SelectItem>
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
                            <TableHead>Officer</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Position</TableHead>
                            <TableHead>Committee</TableHead>
                            <TableHead>Term</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="officers.data.length === 0">
                            <TableCell :colspan="showActions ? 7 : 6" class="text-center text-gray-500">
                                No officer records found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="officer in officers.data" :key="officer.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <span class="font-medium text-gray-900">{{ officer.member.full_name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ officer.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ officer.position }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ officer.committee || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatTerm(officer.term_start, officer.term_end) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ officer.status }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="!isArchivedView && canEditOfficer" :href="`/officers/${officer.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="!isArchivedView && canDeleteOfficer"
                                        @click="deleteOfficer(officer)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                    <Button
                                        v-if="isArchivedView && canDeleteOfficer"
                                        @click="restoreOfficer(officer)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-emerald-700 hover:text-emerald-800"
                                    >
                                        <RotateCcw class="h-4 w-4" />
                                        Restore
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="officers.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (officers.current_page - 1) * officers.per_page + 1 }} to
                            {{ Math.min(officers.current_page * officers.per_page, officers.total) }} of
                            {{ officers.total }} officers
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in officers.last_page"
                                :key="page"
                                @click="router.get('/officers', {
                                    page,
                                    search: search || '',
                                    coop_id: coopId === 'all' ? '' : coopId,
                                    status: status === 'all' ? '' : status,
                                    per_page: resolvedPerPage(),
                                })"
                                :variant="page === officers.current_page ? 'default' : 'outline'"
                                size="sm"
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
