<script setup lang="ts">
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Search, Eye, Wallet, UserPlus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
const canCreateUserAccounts = computed(() => permissions.value.includes('create user-accounts'));
const canReadMemberLoans = computed(() => permissions.value.includes('read finance-member-loans'));
const canBulkDelete = computed(() => canDeleteMember.value);
const showActions = computed(() => canViewMember.value || canEditMember.value || canReadMemberLoans.value || canCreateUserAccounts.value || canDeleteMember.value);
const currentUrl = computed(() => {
    const value = page.url || '';
    return value.startsWith('/') ? value : '';
});

const createMemberHref = computed(() => {
    if (!currentUrl.value) {
        return '/members/create';
    }

    return `/members/create?return_to=${encodeURIComponent(currentUrl.value)}`;
});

const memberDetailHref = (memberId: number) => {
    if (!currentUrl.value) {
        return `/members/${memberId}`;
    }

    return `/members/${memberId}?return_to=${encodeURIComponent(currentUrl.value)}`;
};

const memberEditHref = (memberId: number) => {
    if (!currentUrl.value) {
        return `/members/${memberId}/edit`;
    }

    return `/members/${memberId}/edit?return_to=${encodeURIComponent(currentUrl.value)}`;
};

const search = ref(props.filters.search || '');
const membershipStatus = ref(props.filters.membership_status || 'all');
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

watch(membershipStatus, (newStatus, oldStatus) => {
    if (newStatus === oldStatus) return;

    if (newStatus === 'Archived' || oldStatus === 'Archived') {
        applyFilters();
    }
});

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
            router.get(baseUrl.value, {
                page: String(props.members.current_page),
                [queryKey('search')]: search.value,
                [queryKey('membership_status')]: membershipStatus.value === 'all' ? '' : membershipStatus.value,
                [queryKey('per_page')]: resolvedPerPage(),
            }, {
                preserveScroll: true,
                preserveState: true,
            });
        },
    });
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
                    <Link v-if="canCreateMember" :href="createMemberHref">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Register Member
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mt-6 border-t border-border/60 pt-6">
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
            </div>
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
                                        {{ primaryRoleName(member) }}
                                        <span v-if="additionalRolesCount(member) > 0" class="ml-1">
                                            +{{ additionalRolesCount(member) }}
                                        </span>
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
                                    <Badge
                                        v-if="canCreateUserAccounts && hasLinkedAccount(member)"
                                        variant="secondary"
                                        class="h-8 px-2.5"
                                    >
                                        Has Account
                                    </Badge>
                                    <Button
                                        v-else-if="canCreateUserAccounts"
                                        @click="openCreateAccountModal(member)"
                                        variant="default"
                                        size="sm"
                                        class="h-8 gap-1.5 border border-slate-900 bg-slate-900 text-white hover:bg-slate-800 dark:border-slate-300 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                                        title="Create linked account"
                                    >
                                        <UserPlus class="h-4 w-4" />
                                        Create Account
                                    </Button>
                                    <Link v-if="canReadMemberLoans" :href="`/finance/loans?member_id=${member.id}`">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            class="h-8 gap-1.5 border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950/35 dark:text-emerald-300 dark:hover:bg-emerald-900/55"
                                            title="View member loans"
                                        >
                                            <Wallet class="h-4 w-4" />
                                            Loans
                                        </Button>
                                    </Link>
                                    <Link v-if="canViewMember" :href="memberDetailHref(member.id)">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-1.5" title="View member">
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Button>
                                    </Link>
                                    <Link v-if="canEditMember" :href="memberEditHref(member.id)">
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-1.5" title="Edit member">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDeleteMember"
                                        @click="deleteMember(member)"
                                        variant="ghost"
                                        size="sm"
                                        class="table-action-btn table-action-delete gap-1.5 text-red-600 hover:text-red-700"
                                        title="Delete member"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
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
