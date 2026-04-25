<script setup lang="ts">
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ChevronsLeft,
    ChevronsRight,
    ChevronLeft,
    ChevronRight,
    Eye,
    Loader2,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    SearchX,
    SlidersHorizontal,
    Trash2,
    UserPlus,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { runBulkDelete, useBulkSelection, type CheckedState } from '@/composables/useBulkSelection';
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
        membership_type?: string;
        per_page?: string;
    };
    membershipTypes?: string[];
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
const canCreateUserAccounts = computed(() => permissions.value.includes('create user-accounts'));
const canReadMemberLoans = computed(() => permissions.value.includes('read finance-member-loans'));
const canBulkDelete = computed(() => canDeleteMember.value);
const showActions = computed(() => canViewMember.value || canEditMember.value || canReadMemberLoans.value || canCreateUserAccounts.value || canDeleteMember.value);

const search = ref(props.filters.search || '');
const membershipStatus = ref(props.filters.membership_status || 'all');
const membershipType = ref(props.filters.membership_type || 'all');
const presetPageSizes = ['10', '25', '50', '100'];
const initialPerPageRaw = props.filters.per_page || String(props.members.per_page || 10);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : '10');
const filtersVisible = ref(true);
const isLoading = ref(false);
const SEARCH_DEBOUNCE_MS = 300;
let searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;

const currentPage = computed(() => props.members.current_page || 1);
const totalPages = computed(() => Math.max(props.members.last_page || 1, 1));
const showingFrom = computed(() => (props.members.total ? (currentPage.value - 1) * props.members.per_page + 1 : 0));
const showingTo = computed(() => (props.members.total ? Math.min(currentPage.value * props.members.per_page, props.members.total) : 0));

const clearSearchTimer = () => {
    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = null;
    }
};

const hasActiveFilters = computed(() => (
    search.value.trim() !== ''
    || membershipStatus.value !== 'all'
    || membershipType.value !== 'all'
    || perPage.value !== '10'
));

const membershipTypeOptions = computed(() => {
    const provided = props.membershipTypes?.filter(Boolean) || [];

    if (provided.length > 0) {
        return provided;
    }

    return Array.from(new Set(props.members.data.map((member) => member.membership_type).filter((value): value is string => Boolean(value)))).sort((left, right) => left.localeCompare(right));
});

const paginationItems = computed<Array<number | string>>(() => {
    const total = totalPages.value;
    const current = currentPage.value;

    if (total <= 7) {
        return Array.from({ length: total }, (_, index) => index + 1);
    }

    if (current <= 4) {
        return [1, 2, 3, 4, 5, 'ellipsis-right', total];
    }

    if (current >= total - 3) {
        return [1, 'ellipsis-left', total - 4, total - 3, total - 2, total - 1, total];
    }

    return [1, 'ellipsis-left', current - 1, current, current + 1, 'ellipsis-right', total];
});

const resolvedPerPage = () => perPage.value;

const buildQuery = (pageNumber?: number) => {
    const query: Record<string, string> = {
        [queryKey('search')]: search.value,
        [queryKey('membership_status')]: membershipStatus.value === 'all' ? '' : membershipStatus.value,
        [queryKey('membership_type')]: membershipType.value === 'all' ? '' : membershipType.value,
        [queryKey('per_page')]: resolvedPerPage(),
    };

    if (pageNumber) {
        query.page = String(pageNumber);
    }

    return query;
};

const applyFilters = () => {
    isLoading.value = true;
    router.get(baseUrl.value, buildQuery(), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

watch(search, () => {
    clearSearchTimer();
    searchDebounceTimer = setTimeout(() => {
        applyFilters();
    }, SEARCH_DEBOUNCE_MS);
});

watch([membershipStatus, membershipType, perPage], () => {
    clearSearchTimer();
    applyFilters();
});

const resetFilters = () => {
    clearSearchTimer();
    search.value = '';
    membershipStatus.value = 'all';
    membershipType.value = 'all';
    perPage.value = '10';
    applyFilters();
};

const goToPage = (pageNumber: number) => {
    if (pageNumber < 1 || pageNumber > totalPages.value || pageNumber === currentPage.value) return;

    isLoading.value = true;
    router.get(baseUrl.value, buildQuery(pageNumber), {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
};

onUnmounted(() => {
    clearSearchTimer();
});

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

const formatMemberName = (member: Member) => {
    const lastName = member.last_name?.trim() || '';
    const firstName = member.first_name?.trim() || '';

    if (!lastName && !firstName) {
        return member.full_name || 'N/A';
    }

    if (!lastName) {
        return firstName;
    }

    if (!firstName) {
        return lastName;
    }

    return `${lastName}, ${firstName}`;
};

const getStatusBadgeColor = (status: string | null) => {
    if (!status) return 'bg-gray-100 text-gray-800 border-gray-200';

    const colors: Record<string, string> = {
        Active: 'bg-green-100 text-green-800 border-green-200',
        Suspended: 'bg-orange-100 text-orange-800 border-orange-200',
        Resigned: 'bg-red-100 text-red-800 border-red-200',
        Deceased: 'bg-gray-100 text-gray-800 border-gray-200',
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

const isCreateAccountDialogOpen = ref(false);
const selectedMemberId = ref<number | null>(null);
const createAccountForm = useForm({
    email: '',
    password: '',
    password_confirmation: '',
});

const selectedMember = computed(() => {
    if (!selectedMemberId.value) return null;
    return props.members.data.find((member) => member.id === selectedMemberId.value) || null;
});

const hasLinkedAccount = (member: Member) => {
    return Boolean(member.user?.id);
};

const primaryRoleName = (member: Member) => {
    return member.roles?.[0]?.name || 'Member';
};

const additionalRolesCount = (member: Member) => {
    const total = member.roles?.length || 0;
    return total > 1 ? total - 1 : 0;
};

const openCreateAccountModal = (member: Member) => {
    if (!canCreateUserAccounts.value || hasLinkedAccount(member)) return;
    selectedMemberId.value = member.id;
    createAccountForm.reset();
    createAccountForm.clearErrors();
    isCreateAccountDialogOpen.value = true;
};

const closeCreateAccountModal = () => {
    isCreateAccountDialogOpen.value = false;
    selectedMemberId.value = null;
    createAccountForm.reset();
    createAccountForm.clearErrors();
};

const submitCreateAccount = () => {
    if (!selectedMemberId.value) return;

    createAccountForm.post(`/members/${selectedMemberId.value}/create-account`, {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateAccountModal();
            router.reload({
                only: ['members'],
                preserveScroll: true,
                preserveState: true,
            });
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <section class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">Members</h2>
                        <Badge variant="secondary">{{ members.total }} records</Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">Manage cooperative member profiles</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
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
                    <Button
                        type="button"
                        variant="outline"
                        class="gap-2"
                        @click="filtersVisible = !filtersVisible"
                    >
                        <SlidersHorizontal class="h-4 w-4 transition-transform duration-200" :class="filtersVisible ? 'rotate-90' : 'rotate-0'" />
                        {{ filtersVisible ? 'Hide Filters' : 'Show Filters' }}
                    </Button>
                    <Link v-if="canCreateMember" href="/members/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Register Member
                        </Button>
                    </Link>
                </div>
            </div>

            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="filtersVisible" class="mt-6 border-t border-border/60 pt-6">
                    <div class="rounded-xl border border-border/80 bg-card p-4 shadow-sm">
                        <div class="flex flex-wrap items-end gap-3">
                            <div class="min-w-60 flex-1 space-y-1">
                                <label for="member-search" class="text-sm font-medium text-foreground/80">Search</label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        id="member-search"
                                        v-model="search"
                                        placeholder="Name or email"
                                        class="pl-9"
                                    />
                                </div>
                            </div>

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Membership Status</label>
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
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Membership</label>
                                <Select v-model="membershipType">
                                    <SelectTrigger aria-label="Filter by membership type">
                                        <SelectValue placeholder="All memberships" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Memberships</SelectItem>
                                        <SelectItem v-for="option in membershipTypeOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="min-w-40 space-y-1">
                                <label class="text-sm font-medium text-foreground/80">Rows Per Page</label>
                                <Select v-model="perPage">
                                    <SelectTrigger aria-label="Rows per page">
                                        <SelectValue placeholder="Select size" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <Button
                                v-if="hasActiveFilters"
                                type="button"
                                variant="outline"
                                class="ml-auto gap-2"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-4 w-4" />
                                Clear Filters
                            </Button>
                        </div>
                    </div>
                </div>
            </Transition>
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
                        <template v-if="isLoading">
                            <TableRow v-for="rowIndex in 6" :key="`member-loading-${rowIndex}`">
                                <TableCell v-if="canBulkDelete" class="w-12">
                                    <div class="h-4 w-4 rounded bg-muted animate-pulse" />
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <div class="h-4 w-48 rounded bg-muted animate-pulse" />
                                        <div class="h-3 w-24 rounded bg-muted animate-pulse" />
                                    </div>
                                </TableCell>
                                <TableCell><div class="h-4 w-44 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-5 w-20 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell><div class="h-4 w-28 rounded bg-muted animate-pulse" /></TableCell>
                                <TableCell v-if="showActions"><div class="mx-auto h-8 w-60 rounded bg-muted animate-pulse" /></TableCell>
                            </TableRow>
                        </template>

                        <TableRow v-else-if="members.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="py-10 text-center text-muted-foreground">
                                <div class="mx-auto max-w-md space-y-3 py-6">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <SearchX class="h-6 w-6" />
                                    </div>
                                    <p class="font-medium text-foreground">No results found</p>
                                    <p class="text-sm text-muted-foreground">Try adjusting your filters or search terms.</p>
                                    <Button v-if="hasActiveFilters" type="button" variant="outline" class="gap-2" @click="resetFilters">
                                        <RotateCcw class="h-4 w-4" />
                                        Clear Filters
                                    </Button>
                                </div>
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
                                <TooltipProvider :delay-duration="150">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="max-w-56 truncate font-medium text-foreground">{{ formatMemberName(member) }}</span>
                                                <Badge variant="outline" class="text-[11px]">
                                                    {{ primaryRoleName(member) }}
                                                    <span v-if="additionalRolesCount(member) > 0" class="ml-1">
                                                        +{{ additionalRolesCount(member) }}
                                                    </span>
                                                </Badge>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ formatMemberName(member) }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </TableCell>
                            <TableCell>
                                <TooltipProvider :delay-duration="150">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="flex flex-col text-sm">
                                                <span v-if="member.email" class="max-w-60 truncate text-foreground">{{ member.email }}</span>
                                                <span v-else class="text-muted-foreground">N/A</span>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ member.email || 'N/A' }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
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
                                <TooltipProvider :delay-duration="150">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <Tooltip v-if="canCreateUserAccounts && hasLinkedAccount(member)">
                                            <TooltipTrigger as-child>
                                                <Badge variant="secondary" class="h-8 px-2.5">
                                                    Has Account
                                                </Badge>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Linked account already exists</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip v-else-if="canCreateUserAccounts">
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="openCreateAccountModal(member)"
                                                    variant="default"
                                                    size="sm"
                                                    class="h-8 gap-1.5 border border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-slate-300 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                                                >
                                                    <UserPlus class="h-4 w-4" />
                                                    Create Account
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>Create a linked account</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip v-if="canReadMemberLoans">
                                            <TooltipTrigger as-child>
                                                <Link :href="`/finance/loans?member_id=${member.id}`">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-8 gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950/35 dark:text-emerald-300 dark:hover:bg-emerald-900/55"
                                                    >
                                                        <Wallet class="h-4 w-4" />
                                                        Loans
                                                    </Button>
                                                </Link>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p>View member loans</p>
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip v-if="canViewMember">
                                            <TooltipTrigger as-child>
                                                <Link :href="`/members/${member.id}`">
                                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-1.5">
                                                        <Eye class="h-4 w-4" />
                                                        View
                                                    </Button>
                                                </Link>
                                            </TooltipTrigger>
                                            <TooltipContent><p>View details</p></TooltipContent>
                                        </Tooltip>

                                        <Tooltip v-if="canEditMember">
                                            <TooltipTrigger as-child>
                                                <Link :href="`/members/${member.id}/edit`">
                                                    <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-1.5">
                                                        <Pencil class="h-4 w-4" />
                                                        Edit
                                                    </Button>
                                                </Link>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Edit this record</p></TooltipContent>
                                        </Tooltip>

                                        <Tooltip v-if="canDeleteMember">
                                            <TooltipTrigger as-child>
                                                <Button
                                                    @click="deleteMember(member)"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="table-action-btn table-action-delete gap-1.5 text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                    Delete
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent><p>Delete this record</p></TooltipContent>
                                        </Tooltip>
                                    </div>
                                </TooltipProvider>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="border-t border-border px-4 py-4 md:px-6">
                <div class="mb-3 flex justify-center text-sm text-muted-foreground">
                    <Loader2 v-if="isLoading" class="mr-2 h-4 w-4 animate-spin" />
                    Showing {{ showingFrom }}–{{ showingTo }} of {{ members.total }} results
                </div>

                <div class="grid gap-3 md:grid-cols-[1fr_auto_1fr] md:items-center">
                    <div class="flex items-center gap-2 md:justify-start">
                        <span class="text-sm text-muted-foreground">Show</span>
                        <Select v-model="perPage">
                            <SelectTrigger class="w-24" aria-label="Rows per page">
                                <SelectValue placeholder="Rows" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="10">10</SelectItem>
                                <SelectItem value="25">25</SelectItem>
                                <SelectItem value="50">50</SelectItem>
                                <SelectItem value="100">100</SelectItem>
                            </SelectContent>
                        </Select>
                        <span class="text-sm text-muted-foreground">per page</span>
                    </div>

                    <div class="flex justify-center mt-4">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(1)"
                            >
                                <ChevronsLeft class="h-4 w-4" />
                                First
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage <= 1"
                                :class="currentPage <= 1 ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(currentPage - 1)"
                            >
                                <ChevronLeft class="h-4 w-4" />
                                Previous
                            </Button>

                            <template v-for="item in paginationItems" :key="`member-page-${item}`">
                                <span v-if="typeof item !== 'number'" class="px-1 text-sm text-muted-foreground">...</span>
                                <Button
                                    v-else
                                    type="button"
                                    :variant="item === currentPage ? 'default' : 'outline'"
                                    size="sm"
                                    class="min-w-9"
                                    @click="goToPage(item)"
                                >
                                    {{ item }}
                                </Button>
                            </template>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(currentPage + 1)"
                            >
                                Next
                                <ChevronRight class="h-4 w-4" />
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="gap-1"
                                :disabled="currentPage >= totalPages"
                                :class="currentPage >= totalPages ? 'cursor-not-allowed opacity-50' : ''"
                                @click="goToPage(totalPages)"
                            >
                                Last
                                <ChevronsRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <div class="hidden md:block" />
                </div>
            </div>
        </section>

        <Dialog v-model:open="isCreateAccountDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Create Account</DialogTitle>
                    <DialogDescription>
                        Create a linked user account for {{ selectedMember?.full_name || 'this member' }}.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitCreateAccount">
                    <div class="space-y-2">
                        <Label for="account_email">Account Email</Label>
                        <Input
                            id="account_email"
                            v-model="createAccountForm.email"
                            type="email"
                            autocomplete="email"
                            placeholder="member@email.com"
                        />
                        <InputError :message="createAccountForm.errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Label for="account_password">Password</Label>
                        <Input
                            id="account_password"
                            v-model="createAccountForm.password"
                            type="password"
                            autocomplete="new-password"
                        />
                        <InputError :message="createAccountForm.errors.password" />
                    </div>

                    <div class="space-y-2">
                        <Label for="account_password_confirmation">Confirm Password</Label>
                        <Input
                            id="account_password_confirmation"
                            v-model="createAccountForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                        />
                        <InputError :message="createAccountForm.errors.password_confirmation" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="closeCreateAccountModal">Cancel</Button>
                        <Button type="submit" :disabled="createAccountForm.processing">Create Account</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
