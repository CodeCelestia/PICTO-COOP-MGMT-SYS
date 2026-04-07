<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Search, Eye, RotateCcw } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { runBulkDelete, useBulkSelection, type CheckedState } from '@/composables/useBulkSelection';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';
import type { Member } from '@/types/models';

interface Props {
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        membership_status?: string;
        per_page?: string;
    };
    baseUrl?: string;
    queryPrefix?: string;
}

const props = defineProps<Props>();

const baseUrl = computed(() => props.baseUrl || '/members');
const queryPrefix = computed(() => props.queryPrefix || '');
const queryKey = (key: string) => `${queryPrefix.value}${key}`;

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canViewMember = computed(() => permissions.value.includes('read members-profile'));
const canCreateMember = computed(() => permissions.value.includes('create members-profile'));
const canEditMember = computed(() => permissions.value.includes('update members-profile'));
const canDeleteMember = computed(() => permissions.value.includes('delete members-profile'));
const canBulkDelete = computed(() => canDeleteMember.value && !isArchivedView.value);
const showActions = computed(() => canViewMember.value || canEditMember.value || canDeleteMember.value);

const search = ref(props.filters.search || '');
const membershipStatus = ref(props.filters.membership_status || 'all');
const isArchivedView = computed(() => membershipStatus.value === 'Archived');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.members.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    router.get(baseUrl.value, {
        [queryKey('search')]: search.value,
        [queryKey('membership_status')]: membershipStatus.value === 'all' ? '' : membershipStatus.value,
        [queryKey('per_page')]: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    membershipStatus.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get(baseUrl.value);
};

const deleteMember = async (member: Member) => {
    if (!canDeleteMember.value) return;
    const confirmed = await confirmAction({
        title: 'Delete member?',
        text: `Are you sure you want to delete ${member.full_name}? This action cannot be undone.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/members/${member.id}`, {
        preserveScroll: true,
    });
};

const restoreMember = async (member: Member) => {
    if (!canDeleteMember.value) return;
    const confirmed = await confirmAction({
        title: 'Restore member?',
        text: `Restore ${member.full_name} to active records?`,
        confirmButtonText: 'Restore',
    });

    if (!confirmed) return;

    router.post(`/members/${member.id}/restore`, {}, {
        preserveScroll: true,
    });
};

const getStatusBadgeColor = (status: string | null) => {
    if (!status) return 'bg-gray-100 text-gray-800 border-gray-200';

    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
        'Resigned': 'bg-red-100 text-red-800 border-red-200',
        'Deceased': 'bg-gray-100 text-gray-800 border-gray-200',
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

const visibleMembers = computed(() => props.members.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleMembers);

const bulkDeleteMembers = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected members?',
        text: `Delete ${selectedCount.value} selected member record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/members/${id}`);
    clearSelection();
};

const onToggleAllMembers = (checked: CheckedState) => {
    toggleAll(checked);
};

const onToggleMember = (memberId: number, checked: CheckedState) => {
    toggleOne(memberId, checked);
};
</script>

<template>
    <div class="space-y-6 p-4 md:p-6">
        <section class="rounded-xl border border-border bg-card/95 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Member Management</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Manage cooperative member profiles
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge variant="outline" class="hidden sm:inline-flex">
                        {{ members.total }} total
                    </Badge>
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteMembers">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link v-if="canCreateMember" href="/members/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Register Member
                        </Button>
                    </Link>
                </div>
            </div>

            <FilterPanel
                title="Filters"
                description="Show filter inputs to narrow member records."
                showLabel="Show filters"
                hideLabel="Hide filters"
            >
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                    <div>
                        <label for="member-search" class="mb-2 block text-sm font-medium text-foreground">Search</label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                id="member-search"
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Name or email"
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Membership Status</label>
                        <Select v-model="membershipStatus">
                            <SelectTrigger aria-label="Filter by membership status">
                                <SelectValue placeholder="All statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="Active">Active</SelectItem>
                                <SelectItem value="Suspended">Suspended</SelectItem>
                                <SelectItem value="Resigned">Resigned</SelectItem>
                                <SelectItem value="Deceased">Deceased</SelectItem>
                                <SelectItem value="Archived">Archived</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

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
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">
                        Clear Filters
                    </Button>
                </div>
            </FilterPanel>
        </section>

        <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="members.data.length === 0"
                                    aria-label="Select all members"
                                    @update:model-value="onToggleAllMembers"
                                />
                            </TableHead>
                            <TableHead>Member Name</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Membership</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date Joined</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="members.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="py-10 text-center text-muted-foreground">
                                No members found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="member in members.data" :key="member.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(member.id)"
                                    :aria-label="`Select ${member.full_name}`"
                                    @update:model-value="(checked) => onToggleMember(member.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-foreground">{{ member.full_name }}</span>
                                    <Badge variant="outline" class="text-[11px]">
                                        {{ member.active_officers_count && member.active_officers_count > 0 ? 'Officer' : 'Member' }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col text-sm">
                                    <span v-if="member.email" class="text-foreground">{{ member.email }}</span>
                                    <span v-else class="text-muted-foreground">N/A</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-muted-foreground">{{ member.membership_type || 'N/A' }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusBadgeColor(member.membership_status)" class="border">
                                    {{ member.membership_status || 'N/A' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-muted-foreground">{{ formatDate(member.date_joined) }}</span>
                            </TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link :href="`/members/${member.id}`">
                                        <Button variant="ghost" size="sm" class="gap-1.5" title="View member">
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Button>
                                    </Link>
                                    <Link v-if="!isArchivedView && canEditMember" :href="`/members/${member.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-1.5" title="Edit member">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="!isArchivedView && canDeleteMember"
                                        @click="deleteMember(member)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5 text-red-600 hover:text-red-700"
                                        title="Delete member"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                    <Button
                                        v-if="isArchivedView && canDeleteMember"
                                        @click="restoreMember(member)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5 text-emerald-700 hover:text-emerald-800"
                                        title="Restore member"
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

            <div v-if="members.last_page > 1" class="border-t border-border px-4 py-4 md:px-6">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ (members.current_page - 1) * members.per_page + 1 }} to
                        {{ Math.min(members.current_page * members.per_page, members.total) }} of
                        {{ members.total }} members
                    </p>
                    <nav class="flex flex-wrap gap-2" aria-label="Members pagination">
                        <Button
                            v-for="page in members.last_page"
                            :key="page"
                            @click="router.get(baseUrl, {
                                page,
                                [queryKey('search')]: search || '',
                                [queryKey('membership_status')]: membershipStatus === 'all' ? '' : membershipStatus,
                                [queryKey('per_page')]: resolvedPerPage(),
                            }, { preserveScroll: true, preserveState: true })"
                            :variant="page === members.current_page ? 'default' : 'outline'"
                            size="sm"
                            :aria-current="page === members.current_page ? 'page' : undefined"
                        >
                            {{ page }}
                        </Button>
                    </nav>
                </div>
            </div>
        </section>
    </div>
</template>
