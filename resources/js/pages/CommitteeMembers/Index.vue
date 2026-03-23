<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
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
}

interface Member {
    id: number;
    full_name: string;
}

interface CommitteeMember {
    id: number;
    member_id: number;
    coop_id: number;
    committee_name: string;
    role: string | null;
    date_assigned: string | null;
    date_removed: string | null;
    status: string;
    member: Member;
    cooperative: Cooperative;
}

interface Props {
    committeeMembers: {
        data: CommitteeMember[];
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
const canCreate = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEdit = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDelete = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');

const applyFilters = () => {
    router.get('/committee-members', {
        search: search.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        status: status.value === 'all' ? '' : status.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    coopId.value = 'all';
    status.value = 'all';
    router.get('/committee-members');
};

const deleteMember = async (member: CommitteeMember) => {
    const confirmed = await confirmAction({
        title: 'Remove committee member?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Remove',
    });

    if (!confirmed) return;

    router.delete(`/committee-members/${member.id}`, {
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
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Committee Members</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage committee assignments</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/officers" class="text-sm text-blue-600 hover:underline">
                        View Officers
                    </Link>
                    <Link v-if="canCreate" href="/committee-members/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Committee Member
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
                                placeholder="Member name..."
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
                                <SelectItem value="Inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
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
                            <TableHead>Member</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Committee</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Date Assigned</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead v-if="showActions" class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="committeeMembers.data.length === 0">
                            <TableCell :colspan="showActions ? 7 : 6" class="text-center text-gray-500">
                                No committee members found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="member in committeeMembers.data" :key="member.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <Users class="h-4 w-4" />
                                    </div>
                                    <span class="font-medium text-gray-900">{{ member.member.full_name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ member.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ member.committee_name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ member.role || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(member.date_assigned) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ member.status }}</TableCell>
                            <TableCell v-if="showActions" class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link v-if="canEdit" :href="`/committee-members/${member.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteMember(member)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="committeeMembers.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (committeeMembers.current_page - 1) * committeeMembers.per_page + 1 }} to
                            {{ Math.min(committeeMembers.current_page * committeeMembers.per_page, committeeMembers.total) }} of
                            {{ committeeMembers.total }} committee members
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in committeeMembers.last_page"
                                :key="page"
                                @click="router.get(`/committee-members?page=${page}`)"
                                :variant="page === committeeMembers.current_page ? 'default' : 'outline'"
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
