<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
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
const canCreate = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEdit = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDelete = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.committeeMembers.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    router.get('/committee-members', {
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
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Committee Members</h1>
                    <p class="text-sm text-muted-foreground">Manage committee assignments</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <Link href="/officers" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
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

            <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Member name..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
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
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Status</label>
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
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-[220px_1fr]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Rows Per Page</label>
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
                <div class="mt-5 flex flex-wrap gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">Clear Filters</Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                        <TableRow>
                                <TableHead class="text-muted-foreground">Member</TableHead>
                                <TableHead class="text-muted-foreground">Cooperative</TableHead>
                                <TableHead class="text-muted-foreground">Committee</TableHead>
                                <TableHead class="text-muted-foreground">Role</TableHead>
                                <TableHead class="text-muted-foreground">Date Assigned</TableHead>
                                <TableHead class="text-muted-foreground">Status</TableHead>
                                <TableHead v-if="showActions" class="text-center text-muted-foreground">Actions</TableHead>
                        </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="committeeMembers.data.length === 0">
                                <TableCell :colspan="showActions ? 7 : 6" class="py-8 text-center text-muted-foreground">
                                    No committee members found.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="member in committeeMembers.data" :key="member.id">
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                            <Users class="h-4 w-4" />
                                        </div>
                                        <span class="font-medium text-foreground">{{ member.member.full_name }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ member.cooperative.name }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ member.committee_name }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ member.role || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatDate(member.date_assigned) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ member.status }}</TableCell>
                                <TableCell v-if="showActions" class="text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Link v-if="canEdit" :href="`/committee-members/${member.id}/edit`">
                                            <Button variant="ghost" size="sm" class="gap-2">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="canDelete"
                                            @click="deleteMember(member)"
                                            variant="ghost"
                                            size="sm"
                                            class="gap-2 text-destructive hover:text-destructive"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Remove
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="committeeMembers.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (committeeMembers.current_page - 1) * committeeMembers.per_page + 1 }} to
                            {{ Math.min(committeeMembers.current_page * committeeMembers.per_page, committeeMembers.total) }} of
                            {{ committeeMembers.total }} committee members
                        </div>
                        <div class="flex flex-wrap gap-2" aria-label="Committee member pagination">
                            <Button
                                v-for="page in committeeMembers.last_page"
                                :key="page"
                                @click="router.get('/committee-members', {
                                    page,
                                    search: search || '',
                                    coop_id: coopId === 'all' ? '' : coopId,
                                    status: status === 'all' ? '' : status,
                                    per_page: resolvedPerPage(),
                                })"
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
