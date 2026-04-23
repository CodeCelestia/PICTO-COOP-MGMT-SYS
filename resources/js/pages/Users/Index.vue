<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { Users, Plus, X, Calendar, MessageSquare, UserPlus, Mail, Lock, User as UserIcon, Pencil, Trash2, Eye, Search, Check } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { Textarea } from '@/components/ui/textarea';
import { useCoopLabel } from '@/composables/useCoopLabel';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/alerts';

interface Role {
    id: number;
    name: string;
    level: number;
    description?: string | null;
    is_active?: boolean;
    assigned_at?: string | null;
    assigned_by?: string | number | null;
    status?: string | null;
    expires_at?: string | null;
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    roles: Role[];
    account_type?: string;
    account_status?: string;
    coop_id?: number | null;
    profile_photo?: string;
    last_login_at?: string;
    password_changed_at?: string;
    created_by?: string;
}

interface Cooperative {
    id: number;
    name: string;
    classification?: string | null;
}

const props = defineProps<{
    users: User[];
    availableRoles: Role[];
    cooperatives: Cooperative[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const { cooperativeLabelLower, noCooperativesFoundLabel } = useCoopLabel();
const canCreateUsers = computed(() => permissions.value.includes('create user-accounts'));
const canUpdateUsers = computed(() => permissions.value.includes('update user-accounts'));
const canDeleteUsers = computed(() => permissions.value.includes('delete user-accounts'));
const canManagePermissions = computed(() => permissions.value.includes('manage-permissions'));
const showCoopSelector = computed(() => props.cooperatives.length > 0);
const totalUsers = computed(() => props.users.length);

const isAssignDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);
const selectedRoleId = ref<number | null>(null);
const expiresAt = ref('');
const remarks = ref('');
const selectedCoopId = ref<number | null>(null);

const isRoleDialogOpen = ref(false);
const isEditingRole = ref(false);
const editingRoleId = ref<number | null>(null);
const roleForm = useForm({
    name: '',
    description: '',
    level: '',
    is_active: 'true',
});

const isCreateDialogOpen = ref(false);
const isCoopPickerOpen = ref(false);
const coopPickerSearch = ref('');
const coopPickerFilter = ref<'all' | 'a-m' | 'n-z'>('all');
const coopPickerClassification = ref('all');
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    coop_id: '',
    role_ids: [] as number[],
});

const openAssignDialog = (user: User) => {
    if (!canUpdateUsers.value) return;
    selectedUser.value = user;
    selectedRoleId.value = null;
    expiresAt.value = '';
    remarks.value = '';
    selectedCoopId.value = user.coop_id || null;
    isAssignDialogOpen.value = true;
};

const openViewDialog = (user: User) => {
    selectedUser.value = user;
    isViewDialogOpen.value = true;
};

const editForm = useForm({
    name: '',
    email: '',
    account_status: 'Active',
    coop_id: '',
});


const openEditDialog = (user: User) => {
    if (!canUpdateUsers.value) return;
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.account_status = user.account_status || 'Pending Approval';
    editForm.coop_id = user.coop_id ? String(user.coop_id) : '';
    isEditDialogOpen.value = true;
};

const updateUser = () => {
    if (!canUpdateUsers.value) return;
    if (!selectedUser.value) return;

    editForm.put(`/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const deleteUser = async (user: User) => {
    if (!canDeleteUsers.value) return;
    const confirmed = await confirmAction({
        title: 'Delete user?',
        text: `Delete user "${user.name}"? This cannot be undone.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/users/${user.id}`, {
        preserveScroll: true,
    });
};

const getCoopName = (coopId: number | null | undefined) => {
    if (!coopId) return 'N/A';
    return props.cooperatives.find((coop) => coop.id === coopId)?.name || 'N/A';
};

const assignRole = () => {
    if (!canUpdateUsers.value) return;
    if (!selectedUser.value || !selectedRoleId.value) return;

    router.post(`/users/${selectedUser.value.id}/assign-role`, {
        role_id: selectedRoleId.value,
        coop_id: selectedCoopId.value || null,
        expires_at: expiresAt.value || null,
        remarks: remarks.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isAssignDialogOpen.value = false;
        }
    });
};

const removeRole = async (user: User, roleId: number) => {
    if (!canUpdateUsers.value) return;
    const confirmed = await confirmAction({
        title: 'Remove role?',
        text: 'Are you sure you want to remove this role?',
        confirmButtonText: 'Remove',
    });

    if (!confirmed) return;

    router.post(`/users/${user.id}/remove-role`, {
        role_id: roleId,
    }, {
        preserveScroll: true,
    });
};

const getRoleLevelBadgeColor = (level: number) => {
    const colors: Record<number, string> = {
        1: 'bg-purple-100 text-purple-800 border-purple-200',
        2: 'bg-blue-100 text-blue-800 border-blue-200',
        3: 'bg-green-100 text-green-800 border-green-200',
        4: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        5: 'bg-orange-100 text-orange-800 border-orange-200',
        6: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[level] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date: string | null | undefined) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const resolveRoleAssignedBy = (role: Role) => {
    if (role.assigned_by === null || role.assigned_by === undefined || role.assigned_by === '') {
        return 'System';
    }

    if (typeof role.assigned_by === 'string' && Number.isNaN(Number(role.assigned_by))) {
        return role.assigned_by;
    }

    const assignerId = Number(role.assigned_by);
    if (!Number.isNaN(assignerId)) {
        return props.users.find((user) => user.id === assignerId)?.name || `User #${assignerId}`;
    }

    return 'System';
};

const resolveRoleAssignedOn = (role: Role, user: User) => {
    if (role.assigned_at) {
        return formatDate(role.assigned_at);
    }

    // Fallback keeps the field informative when assignment metadata is missing.
    return formatDate(user.created_at);
};

const resolveRoleStatus = (role: Role) => {
    const status = (role.status || '').trim();
    return status || 'Active';
};

const getRoleStatusBadgeClass = (status: string | null | undefined) => {
    const normalized = String(status || '').trim().toLowerCase();

    if (normalized === 'active') {
        return 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-200';
    }

    if (normalized === 'pending') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200';
    }

    if (normalized === 'inactive' || normalized === 'revoked') {
        return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-200';
    }

    return 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200';
};

const getAccountTypeBadgeColor = (accountType: string | undefined) => {
    const colors: Record<string, string> = {
        'Provincial Admin': 'bg-purple-100 text-purple-800 border-purple-200',
        'Coop Admin': 'bg-blue-100 text-blue-800 border-blue-200',
        'Officer': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'Committee Member': 'bg-cyan-100 text-cyan-800 border-cyan-200',
        'Member': 'bg-green-100 text-green-800 border-green-200',
        'Viewer': 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[accountType || 'Member'] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getAccountStatusBadgeColor = (accountStatus: string | undefined) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Inactive': 'bg-gray-100 text-gray-800 border-gray-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
        'Locked': 'bg-red-100 text-red-800 border-red-200',
        'Pending Approval': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    };
    return colors[accountStatus || 'Pending Approval'] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const openCreateDialog = () => {
    if (!canCreateUsers.value) return;
    createForm.reset();
    createForm.coop_id = '';
    createForm.role_ids = [];
    coopPickerSearch.value = '';
    coopPickerFilter.value = 'all';
    coopPickerClassification.value = 'all';
    isCoopPickerOpen.value = false;
    isCreateDialogOpen.value = true;
};

const sortedCooperatives = computed(() =>
    [...props.cooperatives].sort((a, b) => a.name.localeCompare(b.name)),
);

const filteredCooperatives = computed(() => {
    const search = coopPickerSearch.value.trim().toLowerCase();

    return sortedCooperatives.value.filter((coop) => {
        const name = coop.name.toLowerCase();
        const classification = String(coop.classification || '').trim().toLowerCase();
        const firstLetter = name.charAt(0);
        const matchesSearch = search === '' || name.includes(search) || String(coop.id).includes(search);
        const matchesClassification = coopPickerClassification.value === 'all'
            ? true
            : classification === coopPickerClassification.value;

        if (!matchesSearch || !matchesClassification) {
            return false;
        }

        if (coopPickerFilter.value === 'a-m') {
            return firstLetter >= 'a' && firstLetter <= 'm';
        }

        if (coopPickerFilter.value === 'n-z') {
            return firstLetter >= 'n' && firstLetter <= 'z';
        }

        return true;
    });
});

const cooperativeClassifications = computed(() => {
    const values = new Set<string>();

    props.cooperatives.forEach((coop) => {
        const classification = String(coop.classification || '').trim().toLowerCase();
        if (classification) {
            values.add(classification);
        }
    });

    return Array.from(values).sort((a, b) => a.localeCompare(b));
});

const formatClassificationLabel = (value: string | null | undefined) => {
    const normalized = String(value || '').trim().toLowerCase();

    if (!normalized) {
        return 'Unspecified';
    }

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

const selectedCreateCooperative = computed(() => {
    const coopId = Number(createForm.coop_id);
    if (!coopId) {
        return null;
    }

    return props.cooperatives.find((coop) => coop.id === coopId) || null;
});

const openCooperativePicker = () => {
    if (!showCoopSelector.value) {
        return;
    }

    isCoopPickerOpen.value = true;
};

const selectCooperativeForCreate = (coop: Cooperative) => {
    createForm.coop_id = String(coop.id);
    isCoopPickerOpen.value = false;
};

const openRoleDialog = () => {
    if (!canManagePermissions.value) return;
    roleForm.reset();
    roleForm.clearErrors();
    isEditingRole.value = false;
    editingRoleId.value = null;
    isRoleDialogOpen.value = true;
};

const startEditRole = (role: Role) => {
    if (!canManagePermissions.value) return;
    roleForm.name = role.name;
    roleForm.description = role.description || '';
    roleForm.level = role.level?.toString() || '';
    roleForm.is_active = role.is_active ? 'true' : 'false';
    isEditingRole.value = true;
    editingRoleId.value = role.id;
    isRoleDialogOpen.value = true;
};

const cancelEditRole = () => {
    roleForm.reset();
    roleForm.clearErrors();
    roleForm.is_active = 'true';
    isEditingRole.value = false;
    editingRoleId.value = null;
};

const saveRole = () => {
    if (!canManagePermissions.value) return;
    
    if (isEditingRole.value && editingRoleId.value) {
        roleForm.transform((data) => ({
            ...data,
            is_active: data.is_active === 'true',
        })).put(`/roles/${editingRoleId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditRole();
            },
        });
        return;
    }

    roleForm.transform((data) => ({
        ...data,
        is_active: data.is_active === 'true',
    })).post('/roles', {
        preserveScroll: true,
        onSuccess: () => {
            roleForm.reset();
        },
    });
};

const deleteRole = async (role: Role) => {
    if (!canManagePermissions.value) return;
    const confirmed = await confirmAction({
        title: 'Delete role?',
        text: `Delete role "${role.name}"? This cannot be undone.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/roles/${role.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editingRoleId.value === role.id) {
                cancelEditRole();
            }
        },
    });
};

const createUser = () => {
    if (!canCreateUsers.value) return;
    createForm.post('/users', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
    });
};

const toggleRole = (roleId: number) => {
    const index = createForm.role_ids.indexOf(roleId);
    if (index > -1) {
        createForm.role_ids.splice(index, 1);
    } else {
        createForm.role_ids.push(roleId);
    }

};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">User Management</h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Manage users and their role assignments
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Button v-if="canManagePermissions" @click="openRoleDialog" variant="outline" class="gap-2">
                                <Plus class="h-4 w-4" />
                                Add Role
                            </Button>
                            <Button v-if="canCreateUsers" @click="openCreateDialog" class="gap-2">
                                <UserPlus class="h-4 w-4" />
                                Create User
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="group gap-0 rounded-2xl border border-border bg-card p-5 shadow-[0_8px_20px_rgba(15,23,42,0.08)] transition-all duration-300">
                <div class="flex items-center justify-between">
                    <div class="rounded-xl bg-muted/70 p-3 ring-1 ring-border/70 transition-colors">
                        <Users class="h-5 w-5 text-foreground" />
                    </div>
                    <Badge class="rounded-full bg-muted px-2 py-1 text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                        All registered accounts
                    </Badge>
                </div>
                <div class="mt-4">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">
                        Total Users
                    </h3>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-foreground">
                        {{ totalUsers }}
                    </p>
                </div>
            </Card>

            <Card class="overflow-hidden border-border/80 bg-card shadow-sm">
                <CardHeader class="pb-3">
                    <CardTitle class="text-base font-semibold text-foreground">Users</CardTitle>
                    <CardDescription>
                        View user accounts, status, and role assignments.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!users.length">
                                    <TableCell colspan="5" class="py-10 text-center text-sm text-muted-foreground">
                                        No users found.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="user in users" :key="user.id">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <Users class="h-5 w-5" />
                                            </div>
                                            <span class="text-foreground">{{ user.name }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ user.email }}</TableCell>
                                    <TableCell>
                                        <Badge
                                            :class="getAccountStatusBadgeColor(user.account_status)"
                                            class="border"
                                        >
                                            {{ user.account_status || 'Pending Approval' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1.5">
                                            <div
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                class="group relative"
                                            >
                                                <Badge
                                                    :class="getRoleLevelBadgeColor(role.level)"
                                                    class="border"
                                                >
                                                    {{ role.name }}
                                                    <button
                                                        v-if="canUpdateUsers"
                                                        type="button"
                                                        @click="removeRole(user, role.id)"
                                                        :aria-label="`Remove ${role.name} from ${user.name}`"
                                                        class="ml-1 rounded-sm hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                                    >
                                                        <X class="h-3 w-3" />
                                                    </button>
                                                </Badge>
                                                <div
                                                    class="absolute bottom-full left-0 z-10 mb-2 hidden w-64 rounded-md border border-border/80 bg-card/95 p-2 text-xs text-card-foreground shadow-[0_14px_30px_-18px_rgba(2,8,20,0.55)] backdrop-blur-sm group-hover:block group-focus-within:block"
                                                >
                                                    <div class="space-y-1">
                                                        <div><strong>Assigned By:</strong> {{ resolveRoleAssignedBy(role) }}</div>
                                                        <div><strong>Assigned On:</strong> {{ resolveRoleAssignedOn(role, user) }}</div>
                                                        <div class="flex items-center gap-1.5">
                                                            <strong>Status:</strong>
                                                            <Badge :class="[getRoleStatusBadgeClass(resolveRoleStatus(role)), 'rounded-md px-2 py-0.5 text-[11px] font-medium']">
                                                                {{ resolveRoleStatus(role) }}
                                                            </Badge>
                                                        </div>
                                                        <div v-if="role.expires_at">
                                                            <strong>Expires:</strong> {{ formatDate(role.expires_at) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <Button
                                                v-if="canUpdateUsers"
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="openAssignDialog(user)"
                                                class="h-7 gap-1 border-dashed px-2 text-xs"
                                            >
                                                <Plus class="h-3 w-3" />
                                                Add Role
                                            </Button>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div class="flex flex-wrap items-center justify-center gap-1.5">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-view gap-1.5"
                                                @click="openViewDialog(user)"
                                                title="View details"
                                            >
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                            <Button
                                                v-if="canUpdateUsers"
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-edit gap-1.5"
                                                @click="openEditDialog(user)"
                                                title="Edit user"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                            <Button
                                                v-if="canDeleteUsers"
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-delete gap-1.5 text-red-600 hover:text-red-700"
                                                @click="deleteUser(user)"
                                                title="Delete user"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                                Delete
                                            </Button>
                                            <Button
                                                v-if="canUpdateUsers"
                                                variant="ghost"
                                                size="sm"
                                                class="table-action-btn table-action-other gap-1.5"
                                                @click="openAssignDialog(user)"
                                            >
                                                <UserPlus class="h-4 w-4" />
                                                Manage Roles
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- View User Dialog -->
            <Dialog v-model:open="isViewDialogOpen">
                <DialogContent class="w-full max-w-2xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>User Details</DialogTitle>
                        <DialogDescription>
                            Full profile information for the selected user.
                        </DialogDescription>
                    </DialogHeader>

                    <div v-if="selectedUser" class="grid gap-4 py-2">
                        <div class="rounded-md border border-border bg-muted/30 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Basic</div>
                            <div class="mt-2 grid gap-2 text-sm text-foreground">
                                <div><strong>Name:</strong> {{ selectedUser.name }}</div>
                                <div><strong>Email:</strong> {{ selectedUser.email }}</div>
                                <div>
                                    <strong>Account Type:</strong>
                                    <span class="ml-2 inline-flex">
                                        <Badge :class="getAccountTypeBadgeColor(selectedUser.account_type)" class="border">
                                            {{ selectedUser.account_type || 'Member' }}
                                        </Badge>
                                    </span>
                                </div>
                                <div>
                                    <strong>Status:</strong>
                                    <span class="ml-2 inline-flex">
                                        <Badge :class="getAccountStatusBadgeColor(selectedUser.account_status)" class="border">
                                            {{ selectedUser.account_status || 'Pending Approval' }}
                                        </Badge>
                                    </span>
                                </div>
                                <div>
                                    <strong>Verified:</strong>
                                    <span class="ml-2 inline-flex">
                                        <Badge
                                            :class="selectedUser.email_verified_at
                                                ? 'bg-green-100 text-green-800 border-green-200'
                                                : 'bg-red-100 text-red-800 border-red-200'"
                                            class="border"
                                        >
                                            {{ selectedUser.email_verified_at ? 'Verified' : 'Unverified' }}
                                        </Badge>
                                    </span>
                                </div>
                                <div><strong>Joined:</strong> {{ formatDate(selectedUser.created_at) }}</div>
                                <div><strong>Created By:</strong> {{ selectedUser.created_by || 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="rounded-md border border-border bg-muted/30 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Roles</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge
                                    v-for="role in selectedUser.roles"
                                    :key="role.id"
                                    :class="getRoleLevelBadgeColor(role.level)"
                                    class="border"
                                >
                                    {{ role.name }}
                                </Badge>
                                <span v-if="!selectedUser.roles.length" class="text-sm text-muted-foreground">No roles assigned.</span>
                            </div>
                        </div>

                        <div class="rounded-md border border-border bg-muted/30 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Cooperative</div>
                            <div class="mt-2 text-sm text-foreground">
                                <div><strong>Assigned Cooperative:</strong> {{ getCoopName(selectedUser.coop_id) }}</div>
                            </div>
                        </div>

                        <div class="rounded-md border border-border bg-muted/30 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-muted-foreground">Security</div>
                            <div class="mt-2 grid gap-2 text-sm text-foreground">
                                <div><strong>Last Login:</strong> {{ selectedUser.last_login_at ? formatDate(selectedUser.last_login_at) : 'N/A' }}</div>
                                <div><strong>Password Changed:</strong> {{ selectedUser.password_changed_at ? formatDate(selectedUser.password_changed_at) : 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isViewDialogOpen = false">Close</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Edit User Dialog -->
            <Dialog v-if="canUpdateUsers" v-model:open="isEditDialogOpen">
                <DialogContent class="sm:max-w-125">
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>
                            Update the user profile details and account status.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="edit_name">Name</Label>
                            <Input
                                id="edit_name"
                                v-model="editForm.name"
                                placeholder="Full name"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': editForm.errors.name }"
                            />
                            <p v-if="editForm.errors.name" class="text-sm text-red-600">
                                {{ editForm.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_email">Email</Label>
                            <Input
                                id="edit_email"
                                type="email"
                                v-model="editForm.email"
                                placeholder="user@example.com"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': editForm.errors.email }"
                            />
                            <p v-if="editForm.errors.email" class="text-sm text-red-600">
                                {{ editForm.errors.email }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_status">Account Status</Label>
                            <Select v-model="editForm.account_status">
                                <SelectTrigger id="edit_status" :class="{ 'border-red-500 focus-visible:ring-red-500': editForm.errors.account_status }">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Active">Active</SelectItem>
                                    <SelectItem value="Inactive">Inactive</SelectItem>
                                    <SelectItem value="Suspended">Suspended</SelectItem>
                                    <SelectItem value="Locked">Locked</SelectItem>
                                    <SelectItem value="Pending Approval">Pending Approval</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.errors.account_status" class="text-sm text-red-600">
                                {{ editForm.errors.account_status }}
                            </p>
                        </div>

                        <div v-if="showCoopSelector" class="grid gap-2">
                            <Label for="edit_coop_id">Cooperative</Label>
                            <Select v-model="editForm.coop_id">
                                <SelectTrigger id="edit_coop_id" :class="{ 'border-red-500 focus-visible:ring-red-500': editForm.errors.coop_id }">
                                    <SelectValue placeholder="Select cooperative" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="coop in cooperatives"
                                        :key="coop.id"
                                        :value="coop.id.toString()"
                                    >
                                        {{ coop.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="editForm.errors.coop_id" class="text-sm text-red-600">
                                {{ editForm.errors.coop_id }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isEditDialogOpen = false" :disabled="editForm.processing">
                            Cancel
                        </Button>
                        <Button @click="updateUser" :disabled="editForm.processing">
                            Save Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Assign Role Dialog -->
            <Dialog v-if="canUpdateUsers" v-model:open="isAssignDialogOpen">
                <DialogContent class="sm:max-w-125">
                    <DialogHeader>
                        <DialogTitle>Assign Role to {{ selectedUser?.name }}</DialogTitle>
                        <DialogDescription>
                            Select a role to assign to this user. You can optionally set an expiration date and add remarks.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="role">Role</Label>
                            <Select v-model="selectedRoleId">
                                <SelectTrigger id="role">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="role in availableRoles"
                                        :key="role.id"
                                        :value="role.id"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Badge
                                                :class="getRoleLevelBadgeColor(role.level)"
                                                class="border"
                                            >
                                                L{{ role.level }}
                                            </Badge>
                                            <span>{{ role.name }}</span>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="showCoopSelector" class="grid gap-2">
                            <Label for="coop_id">Cooperative</Label>
                            <Select v-model="selectedCoopId">
                                <SelectTrigger id="coop_id">
                                    <SelectValue placeholder="Select cooperative" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="coop in cooperatives"
                                        :key="coop.id"
                                        :value="coop.id"
                                    >
                                        {{ coop.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="expires_at" class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                Expiration Date (Optional)
                            </Label>
                            <Input
                                id="expires_at"
                                type="date"
                                v-model="expiresAt"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="remarks" class="flex items-center gap-2">
                                <MessageSquare class="h-4 w-4" />
                                Remarks (Optional)
                            </Label>
                            <Textarea
                                id="remarks"
                                v-model="remarks"
                                placeholder="Add any notes or remarks about this role assignment..."
                                rows="3"
                            />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isAssignDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="assignRole"
                            :disabled="!selectedRoleId"
                        >
                            Assign Role
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Create User Dialog -->
            <Dialog v-if="canCreateUsers" v-model:open="isCreateDialogOpen">
                <DialogContent class="sm:max-w-125">
                    <DialogHeader>
                        <DialogTitle>Create New User</DialogTitle>
                        <DialogDescription>
                            Add a new user to the system. You can assign roles during creation or later.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid gap-2">
                            <Label for="name" class="flex items-center gap-2">
                                <UserIcon class="h-4 w-4" />
                                Name
                            </Label>
                            <Input
                                id="name"
                                v-model="createForm.name"
                                placeholder="Enter full name"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': createForm.errors.name }"
                            />
                            <p v-if="createForm.errors.name" class="text-sm text-red-600">
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="email" class="flex items-center gap-2">
                                <Mail class="h-4 w-4" />
                                Email
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                v-model="createForm.email"
                                placeholder="user@example.com"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': createForm.errors.email }"
                            />
                            <p v-if="createForm.errors.email" class="text-sm text-red-600">
                                {{ createForm.errors.email }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="flex items-center gap-2">
                                <Lock class="h-4 w-4" />
                                Password
                            </Label>
                            <Input
                                id="password"
                                type="password"
                                v-model="createForm.password"
                                placeholder="Enter password"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': createForm.errors.password }"
                            />
                            <p v-if="createForm.errors.password" class="text-sm text-red-600">
                                {{ createForm.errors.password }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation" class="flex items-center gap-2">
                                <Lock class="h-4 w-4" />
                                Confirm Password
                            </Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                v-model="createForm.password_confirmation"
                                placeholder="Confirm password"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label>Assign Roles (Optional)</Label>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    @click="toggleRole(role.id)"
                                    :aria-pressed="createForm.role_ids.includes(role.id)"
                                    :class="[
                                        'h-auto border-2 px-2.5 py-1 text-xs transition-all',
                                        createForm.role_ids.includes(role.id)
                                            ? getRoleLevelBadgeColor(role.level)
                                            : 'bg-muted text-muted-foreground border-border hover:border-primary/40'
                                    ]"
                                >
                                    <span class="flex items-center gap-1">
                                        {{ role.name }}
                                        <span v-if="createForm.role_ids.includes(role.id)" class="ml-1">✓</span>
                                    </span>
                                </Button>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Click to select/deselect roles. You can also assign roles later.
                            </p>
                        </div>

                        <div v-if="showCoopSelector" class="grid gap-2">
                            <Label for="create_coop_picker">Cooperative</Label>
                            <Button
                                id="create_coop_picker"
                                type="button"
                                variant="outline"
                                class="h-10 w-full items-center justify-between"
                                :class="{ 'border-red-500 focus-visible:ring-red-500': createForm.errors.coop_id }"
                                @click="openCooperativePicker"
                            >
                                <span class="truncate text-left">
                                    {{ selectedCreateCooperative ? selectedCreateCooperative.name : 'Choose Cooperative' }}
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">
                                    {{ selectedCreateCooperative ? `ID ${selectedCreateCooperative.id}` : 'Select' }}
                                </span>
                            </Button>
                            <p v-if="selectedCreateCooperative" class="text-xs text-muted-foreground">
                                Selected Cooperative: {{ selectedCreateCooperative.name }}
                            </p>
                            <p v-if="createForm.errors.coop_id" class="text-sm text-red-600">
                                {{ createForm.errors.coop_id }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isCreateDialogOpen = false; isCoopPickerOpen = false"
                            :disabled="createForm.processing"
                        >
                            Cancel
                        </Button>
                        <Button
                            @click="createUser"
                            :disabled="createForm.processing"
                        >
                            {{ createForm.processing ? 'Creating...' : 'Create User' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="isCoopPickerOpen">
                <DialogContent class="w-full max-w-5xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Choose Cooperative</DialogTitle>
                        <DialogDescription>
                            Search and filter {{ cooperativeLabelLower }}, then select one to assign to the new user.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 py-2">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="coop_search">Search</Label>
                                <div class="relative">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        id="coop_search"
                                        v-model="coopPickerSearch"
                                        class="pl-9"
                                        placeholder="Search by cooperative name or ID"
                                    />
                                </div>
                            </div>

                            <div class="grid gap-2">
                                <Label for="coop_filter">Filter</Label>
                                <Select v-model="coopPickerFilter">
                                    <SelectTrigger id="coop_filter">
                                        <SelectValue placeholder="All names" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All names</SelectItem>
                                        <SelectItem value="a-m">Name A-M</SelectItem>
                                        <SelectItem value="n-z">Name N-Z</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="grid gap-2">
                                <Label for="coop_classification">Classification</Label>
                                <Select v-model="coopPickerClassification">
                                    <SelectTrigger id="coop_classification">
                                        <SelectValue placeholder="All classifications" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All classifications</SelectItem>
                                        <SelectItem
                                            v-for="classification in cooperativeClassifications"
                                            :key="classification"
                                            :value="classification"
                                        >
                                            {{ formatClassificationLabel(classification) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="max-h-80 overflow-y-auto rounded-md border border-border">
                            <div v-if="!filteredCooperatives.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                {{ noCooperativesFoundLabel }}
                            </div>

                            <div class="grid gap-0 md:grid-cols-2">
                                <button
                                    v-for="coop in filteredCooperatives"
                                    :key="coop.id"
                                    type="button"
                                    class="flex w-full items-center justify-between border-b border-border px-4 py-3 text-left transition hover:bg-muted/50 md:border-r md:nth-[2n]:border-r-0"
                                    @click="selectCooperativeForCreate(coop)"
                                >
                                    <div>
                                        <div class="font-medium text-foreground">{{ coop.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            Cooperative ID: {{ coop.id }}
                                            <span class="mx-1">•</span>
                                            Classification: {{ formatClassificationLabel(coop.classification) }}
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs font-medium text-primary">
                                        <Check
                                            v-if="selectedCreateCooperative?.id === coop.id"
                                            class="h-4 w-4"
                                        />
                                        <span>{{ selectedCreateCooperative?.id === coop.id ? 'Selected' : 'Select' }}</span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isCoopPickerOpen = false">
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Role Management Dialog -->
            <Dialog v-if="canManagePermissions" v-model:open="isRoleDialogOpen">
                <DialogContent class="w-full max-w-3xl sm:max-w-4xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Role Management</DialogTitle>
                        <DialogDescription>
                            Create, update, or remove roles used by the system.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-6 py-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="role_name">Role Name</Label>
                                <Input
                                    id="role_name"
                                    v-model="roleForm.name"
                                    placeholder="e.g., Auditor"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': roleForm.errors.name }"
                                />
                                <p v-if="roleForm.errors.name" class="text-sm text-red-600">
                                    {{ roleForm.errors.name }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="role_level">Role Level</Label>
                                <Input
                                    id="role_level"
                                    v-model="roleForm.level"
                                    type="number"
                                    min="1"
                                    max="20"
                                    placeholder="1"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': roleForm.errors.level }"
                                />
                                <p v-if="roleForm.errors.level" class="text-sm text-red-600">
                                    {{ roleForm.errors.level }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="role_status">Status</Label>
                                <Select v-model="roleForm.is_active">
                                    <SelectTrigger id="role_status" :class="{ 'border-red-500 focus-visible:ring-red-500': roleForm.errors.is_active }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="true">Active</SelectItem>
                                        <SelectItem value="false">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="roleForm.errors.is_active" class="text-sm text-red-600">
                                    {{ roleForm.errors.is_active }}
                                </p>
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label for="role_description">Description</Label>
                                <Textarea
                                    id="role_description"
                                    v-model="roleForm.description"
                                    placeholder="Describe what this role can do"
                                    rows="3"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': roleForm.errors.description }"
                                />
                                <p v-if="roleForm.errors.description" class="text-sm text-red-600">
                                    {{ roleForm.errors.description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 border-t pt-4">
                            <Button
                                v-if="isEditingRole"
                                variant="outline"
                                @click="cancelEditRole"
                            >
                                Cancel Edit
                            </Button>
                            <Button @click="saveRole" :disabled="roleForm.processing">
                                {{ isEditingRole ? 'Update Role' : 'Create Role' }}
                            </Button>
                        </div>

                        <div class="border-t pt-4">
                            <div class="mb-2 text-sm font-medium text-foreground">Existing Roles</div>
                            <div class="space-y-2">
                                <div
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    class="flex flex-col gap-3 rounded-md border border-border bg-muted/30 p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex items-start gap-3">
                                        <Badge :class="getRoleLevelBadgeColor(role.level)" class="border">
                                            L{{ role.level }}
                                        </Badge>
                                        <div>
                                            <div class="font-medium text-foreground">{{ role.name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ role.description || 'No description provided.' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Badge
                                            :class="role.is_active ? 'bg-green-100 text-green-800 border-green-200' : 'bg-gray-100 text-gray-800 border-gray-200'"
                                            class="border"
                                        >
                                            {{ role.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                        <Button variant="ghost" size="sm" class="table-action-btn table-action-edit" @click="startEditRole(role)">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="table-action-btn table-action-delete text-red-600 hover:text-red-700"
                                            @click="deleteRole(role)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" @click="isRoleDialogOpen = false">
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
