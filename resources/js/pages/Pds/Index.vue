<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
    };
    cooperatives: CooperativeOption[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');
const coopId = ref(props.filters.coop_id || 'all');

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

const applyFilters = () => {
    router.get('/pds', {
        search: search.value,
        status: status.value === 'all' ? '' : status.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = 'all';
    coopId.value = 'all';
    router.get('/pds');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Personal Data Sheet</h1>
                    <p class="mt-1 text-sm text-gray-500">CS Form No. 212 Revised 2025 submissions</p>
                </div>
                <Link href="/pds/create">
                    <Button>New PDS</Button>
                </Link>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <Input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Name, coop, or submission ID"
                        />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="final">Final</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="isProvincialAdmin">
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
                </div>
                <div class="mt-4 flex gap-2">
                    <Button @click="applyFilters">Apply Filters</Button>
                    <Button variant="outline" @click="clearFilters">Clear</Button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>PDS ID</TableHead>
                            <TableHead>Member</TableHead>
                            <TableHead v-if="showAdminColumns">Cooperative</TableHead>
                            <TableHead>Date Saved</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date Submitted</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="submissions.data.length === 0">
                            <TableCell :colspan="showAdminColumns ? 7 : 6" class="text-center text-gray-500">No PDS submissions yet.</TableCell>
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
                            <TableCell>
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/pds/${pds.id}/edit`">
                                        <Button variant="outline" size="sm">Edit</Button>
                                    </Link>
                                    <a :href="`/pds/${pds.id}/download`" target="_blank" rel="noopener noreferrer">
                                        <Button variant="outline" size="sm">Download</Button>
                                    </a>
                                    <Button variant="destructive" size="sm" @click="deleteSubmission(pds.id)">
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="submissions.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (submissions.current_page - 1) * submissions.per_page + 1 }} to
                            {{ Math.min(submissions.current_page * submissions.per_page, submissions.total) }} of
                            {{ submissions.total }} submissions
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in submissions.last_page"
                                :key="page"
                                @click="router.get('/pds', {
                                    page,
                                    search: search || '',
                                    status: status === 'all' ? '' : status,
                                    coop_id: coopId === 'all' ? '' : coopId,
                                })"
                                :variant="page === submissions.current_page ? 'default' : 'outline'"
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
