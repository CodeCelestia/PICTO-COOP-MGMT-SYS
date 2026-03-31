<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Download, Trash2, RotateCcw } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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

interface PdsSubmission {
    id: number;
    status: 'draft' | 'final';
    created_at: string;
    submitted_at: string | null;
    c1_data?: {
        surname?: string;
        first_name?: string;
    };
    user?: {
        id: number;
        name: string;
    };
    cooperative?: {
        id: number;
        name: string;
    };
}

interface CooperativeOption {
    id: number;
    name: string;
}

interface Props {
    submissions: {
        data: PdsSubmission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    userRole: 'Provincial Admin' | 'Coop Admin' | 'User';
    filters: {
        search?: string;
        status?: string;
        coop_id?: string;
        per_page?: string;
    };
    cooperatives: CooperativeOption[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const isArchivedView = computed(() => status.value === 'Archived');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.submissions.per_page || 10);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const isProvincialAdmin = computed(() => props.userRole === 'Provincial Admin');
const isCoopAdmin = computed(() => props.userRole === 'Coop Admin');
const showAdminColumns = computed(() => isProvincialAdmin.value || isCoopAdmin.value);

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';

    return new Date(value).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};

const statusClass = (status: 'draft' | 'final') => {
    return status === 'final'
        ? 'bg-green-100 text-green-800 border-green-200'
        : 'bg-amber-100 text-amber-800 border-amber-200';
};

const submissions = computed(() => props.submissions);

const displayName = (pds: PdsSubmission) => {
    const firstName = pds.c1_data?.first_name || '';
    const surname = pds.c1_data?.surname || '';
    const fullName = `${firstName} ${surname}`.trim();

    return fullName || pds.user?.name || 'N/A';
};

const deleteSubmission = (id: number) => {
    if (!globalThis.confirm('Delete this PDS submission?')) {
        return;
    }

    router.delete(`/pds/${id}`, {
        preserveScroll: true,
    });
};

const restoreSubmission = async (pds: PdsSubmission) => {
    const confirmed = await confirmAction({
        title: 'Restore PDS submission?',
        text: `Restore PDS #${pds.id} to active records?`,
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post(`/pds/${pds.id}/restore`, {}, {
        preserveScroll: true,
    });
};

const applyFilters = () => {
    router.get('/pds', {
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/pds');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Personal Data Sheet</h1>
                        <p class="mt-1 text-sm text-muted-foreground">CS Form No. 212 Revised 2025 submissions</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Badge variant="outline" class="hidden sm:inline-flex">{{ submissions.total }} total</Badge>
                        <Link href="/pds/create" prefetch>
                            <Button>New PDS</Button>
                        </Link>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                    <div class="xl:col-span-5">
                        <label for="pds-search" class="mb-2 block text-sm font-medium text-foreground">Search</label>
                        <Input
                            id="pds-search"
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Name, coop, or submission ID"
                        />
                    </div>
                    <div class="xl:col-span-3">
                        <label class="mb-2 block text-sm font-medium text-foreground">Status</label>
                        <Select v-model="status">
                            <SelectTrigger aria-label="Filter by PDS status">
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="final">Final</SelectItem>
                                <SelectItem value="Archived">Archived</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="isProvincialAdmin" class="xl:col-span-4">
                        <label class="mb-2 block text-sm font-medium text-foreground">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger aria-label="Filter by cooperative">
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
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Rows Per Page</label>
                        <div class="flex gap-2">
                            <Select v-model="perPage">
                                <SelectTrigger aria-label="Rows per page">
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
                <div class="mt-4 flex flex-wrap gap-2">
                    <Button @click="applyFilters">Apply Filters</Button>
                    <Button variant="outline" @click="clearFilters">Clear</Button>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>PDS ID</TableHead>
                                <TableHead>Member</TableHead>
                                <TableHead v-if="showAdminColumns">Cooperative</TableHead>
                                <TableHead>Date Saved</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Date Submitted</TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="submissions.data.length === 0">
                                <TableCell :colspan="showAdminColumns ? 7 : 6" class="py-10 text-center text-muted-foreground">No PDS submissions yet.</TableCell>
                            </TableRow>

                            <TableRow v-for="pds in submissions.data" :key="pds.id">
                                <TableCell>#{{ pds.id }}</TableCell>
                                <TableCell>{{ displayName(pds) }}</TableCell>
                                <TableCell v-if="showAdminColumns">{{ pds.cooperative?.name || 'N/A' }}</TableCell>
                                <TableCell>{{ formatDate(pds.created_at) }}</TableCell>
                                <TableCell>
                                    <Badge :class="statusClass(pds.status)" class="border">
                                        {{ pds.status === 'final' ? 'Final' : 'Draft' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ formatDate(pds.submitted_at) }}</TableCell>
                                <TableCell class="text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Link v-if="!isArchivedView" :href="`/pds/${pds.id}/edit`">
                                            <Button variant="outline" size="sm" class="gap-1.5">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <a :href="`/pds/${pds.id}/download`" target="_blank" rel="noopener noreferrer">
                                            <Button variant="outline" size="sm" class="gap-1.5">
                                                <Download class="h-4 w-4" />
                                                Download
                                            </Button>
                                        </a>
                                        <Button v-if="!isArchivedView" variant="destructive" size="sm" class="gap-1.5" @click="deleteSubmission(pds.id)">
                                            <Trash2 class="h-4 w-4" />
                                            Delete
                                        </Button>
                                        <Button
                                            v-if="isArchivedView"
                                            variant="outline"
                                            size="sm"
                                            class="gap-1.5 text-emerald-700 hover:text-emerald-800"
                                            @click="restoreSubmission(pds)"
                                        >
                                            <RotateCcw class="h-4 w-4" />
                                            Restore
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="submissions.last_page > 1" class="border-t border-border px-4 py-4 md:px-6">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ (submissions.current_page - 1) * submissions.per_page + 1 }} to
                            {{ Math.min(submissions.current_page * submissions.per_page, submissions.total) }} of
                            {{ submissions.total }} submissions
                        </p>
                        <nav class="flex flex-wrap gap-2" aria-label="PDS pagination">
                            <Button
                                v-for="page in submissions.last_page"
                                :key="page"
                                @click="router.get('/pds', {
                                    page,
                                    search: search || '',
                                    status: status === 'all' ? '' : status,
                                    coop_id: coopId === 'all' ? '' : coopId,
                                    per_page: resolvedPerPage(),
                                })"
                                :variant="page === submissions.current_page ? 'default' : 'outline'"
                                size="sm"
                                :aria-current="page === submissions.current_page ? 'page' : undefined"
                            >
                                {{ page }}
                            </Button>
                        </nav>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
