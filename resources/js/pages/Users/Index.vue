<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Users, Plus, X, Calendar, MessageSquare, UserPlus, Mail, Lock, User as UserIcon, Pencil, Trash2, Eye } from 'lucide-vue-next';
import { confirmAction } from '@/lib/alerts';
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
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';

interface Role {
    id: number;
    name: string;
    level: number;
    description?: string | null;
    is_active?: boolean;
    assigned_at?: string;
    assigned_by?: string;
    status?: string;
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
}

const props = defineProps<{
    users: User[];
    availableRoles: Role[];
    cooperatives: Cooperative[];
}>();

const isAssignDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isViewDialogOpen = ref(false);
const selectedUser = ref<User | null>(null);
const selectedRoleId = ref<number | null>(null);
const expiresAt = ref('');
const remarks = ref('');
const selectedCoopId = ref<number | null>(null);

const coopAdminRoleId = props.availableRoles.find(role => role.name === 'Coop Admin')?.id ?? null;

const isCoopAdminRole = (roleId: number | null) => {
    if (!roleId || !coopAdminRoleId) return false;
    return roleId === coopAdminRoleId;
};

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
const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    coop_id: '',
    role_ids: [] as number[],
});

const openAssignDialog = (user: User) => {
    selectedUser.value = user;
    selectedRoleId.value = null;
    expiresAt.value = '';
    remarks.value = '';
    selectedCoopId.value = null;
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

const isCoopAdminAccount = (user: User | null) => {
    return user?.account_type === 'Coop Admin';
};


const openEditDialog = (user: User) => {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.account_status = user.account_status || 'Pending Approval';
    editForm.coop_id = user.coop_id ? String(user.coop_id) : '';
    isEditDialogOpen.value = true;
};

const updateUser = () => {
    if (!selectedUser.value) return;

    editForm.put(`/users/${selectedUser.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
        },
    });
};

const deleteUser = async (user: User) => {
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
    if (!selectedUser.value || !selectedRoleId.value) return;

    if (isCoopAdminRole(selectedRoleId.value) && !selectedCoopId.value) return;

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
    createForm.reset();
    isCreateDialogOpen.value = true;
};

const openRoleDialog = () => {
    roleForm.reset();
    roleForm.clearErrors();
    isEditingRole.value = false;
    editingRoleId.value = null;
    isRoleDialogOpen.value = true;
};

const startEditRole = (role: Role) => {
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
    if (isEditingRole.value && editingRoleId.value) {
        roleForm.put(`/roles/${editingRoleId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                cancelEditRole();
            },
        });
        return;
    }

    roleForm.post('/roles', {
        preserveScroll: true,
        onSuccess: () => {
            roleForm.reset();
        },
    });
};

const deleteRole = async (role: Role) => {
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

    if (!requiresCoop()) {
        createForm.coop_id = '';
    }
};

const requiresCoop = () => {
    return coopAdminRoleId ? createForm.role_ids.includes(coopAdminRoleId) : false;
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage users and their role assignments
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button @click="openRoleDialog" variant="outline" class="gap-2">
                        <Plus class="h-4 w-4" />
                        Add Role
                    </Button>
                    <Button @click="openCreateDialog" class="gap-2">
                        <UserPlus class="h-4 w-4" />
                        Create User
                    </Button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
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
                        <TableRow v-for="user in users" :key="user.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <Users class="h-5 w-5" />
                                    </div>
                                    <span>{{ user.name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-gray-600">{{ user.email }}</TableCell>
                            <TableCell>
                                <Badge
                                    :class="getAccountStatusBadgeColor(user.account_status)"
                                    class="border"
                                >
                                    {{ user.account_status || 'Pending Approval' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-wrap gap-1">
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
                                                @click="removeRole(user, role.id)"
                                                class="ml-1 hover:text-red-600"
                                            >
                                                <X class="h-3 w-3" />
                                            </button>
                                        </Badge>
                                        <div
                                            class="absolute bottom-full left-0 z-10 mb-2 hidden w-64 rounded-md bg-gray-900 p-2 text-xs text-white shadow-lg group-hover:block"
                                        >
                                            <div class="space-y-1">
                                                <div><strong>Assigned By:</strong> {{ role.assigned_by }}</div>
                                                <div><strong>Assigned On:</strong> {{ formatDate(role.assigned_at) }}</div>
                                                <div><strong>Status:</strong> {{ role.status }}</div>
                                                <div v-if="role.expires_at">
                                                    <strong>Expires:</strong> {{ formatDate(role.expires_at) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        @click="openAssignDialog(user)"
                                        class="inline-flex items-center gap-1 rounded-md border border-dashed border-gray-300 px-2 py-1 text-xs text-gray-600 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <Plus class="h-3 w-3" />
                                        Add Role
                                    </button>
                                </div>
                            </TableCell>
                            <TableCell class="text-center">
                                <div class="flex flex-wrap items-center justify-center gap-2">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5"
                                        @click="openViewDialog(user)"
                                        title="View details"
                                    >
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5"
                                        @click="openEditDialog(user)"
                                        title="Edit user"
                                    >
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5 text-red-600 hover:text-red-700"
                                        @click="deleteUser(user)"
                                        title="Delete user"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5"
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
                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-gray-500">Basic</div>
                            <div class="mt-2 grid gap-2 text-sm text-gray-700">
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
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800'"
                                        >
                                            {{ selectedUser.email_verified_at ? 'Verified' : 'Unverified' }}
                                        </Badge>
                                    </span>
                                </div>
                                <div><strong>Joined:</strong> {{ formatDate(selectedUser.created_at) }}</div>
                                <div><strong>Created By:</strong> {{ selectedUser.created_by || 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-gray-500">Roles</div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge
                                    v-for="role in selectedUser.roles"
                                    :key="role.id"
                                    :class="getRoleLevelBadgeColor(role.level)"
                                    class="border"
                                >
                                    {{ role.name }}
                                </Badge>
                                <span v-if="!selectedUser.roles.length" class="text-sm text-gray-500">No roles assigned.</span>
                            </div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-gray-500">Cooperative</div>
                            <div class="mt-2 text-sm text-gray-700">
                                <div><strong>Assigned Cooperative:</strong> {{ getCoopName(selectedUser.coop_id) }}</div>
                            </div>
                        </div>

                        <div class="rounded-md border border-gray-200 p-4">
                            <div class="text-xs font-semibold uppercase tracking-widest text-gray-500">Security</div>
                            <div class="mt-2 grid gap-2 text-sm text-gray-700">
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
            <Dialog v-model:open="isEditDialogOpen">
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
                                :class="{ 'border-red-500': editForm.errors.name }"
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
                                :class="{ 'border-red-500': editForm.errors.email }"
                            />
                            <p v-if="editForm.errors.email" class="text-sm text-red-600">
                                {{ editForm.errors.email }}
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="edit_status">Account Status</Label>
                            <Select v-model="editForm.account_status">
                                <SelectTrigger id="edit_status" :class="{ 'border-red-500': editForm.errors.account_status }">
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

                        <div v-if="isCoopAdminAccount(selectedUser)" class="grid gap-2">
                            <Label for="edit_coop_id">Cooperative</Label>
                            <Select v-model="editForm.coop_id">
                                <SelectTrigger id="edit_coop_id" :class="{ 'border-red-500': editForm.errors.coop_id }">
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
            <Dialog v-model:open="isAssignDialogOpen">
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

                        <div v-if="isCoopAdminRole(selectedRoleId)" class="grid gap-2">
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
                            :disabled="!selectedRoleId || (isCoopAdminRole(selectedRoleId) && !selectedCoopId)"
                        >
                            Assign Role
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <!-- Create User Dialog -->
            <Dialog v-model:open="isCreateDialogOpen">
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
                                :class="{ 'border-red-500': createForm.errors.name }"
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
                                :class="{ 'border-red-500': createForm.errors.email }"
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
                                :class="{ 'border-red-500': createForm.errors.password }"
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
                                <Badge
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    @click="toggleRole(role.id)"
                                    :class="[
                                        'cursor-pointer border-2 transition-all',
                                        createForm.role_ids.includes(role.id)
                                            ? getRoleLevelBadgeColor(role.level)
                                            : 'bg-gray-50 text-gray-500 border-gray-300 hover:border-gray-400'
                                    ]"
                                >
                                    <span class="flex items-center gap-1">
                                        {{ role.name }}
                                        <span v-if="createForm.role_ids.includes(role.id)" class="ml-1">✓</span>
                                    </span>
                                </Badge>
                            </div>
                            <p class="text-xs text-gray-500">
                                Click to select/deselect roles. You can also assign roles later.
                            </p>
                        </div>

                        <div v-if="requiresCoop()" class="grid gap-2">
                            <Label for="create_coop_id">Cooperative</Label>
                            <Select v-model="createForm.coop_id">
                                <SelectTrigger id="create_coop_id" :class="{ 'border-red-500': createForm.errors.coop_id }">
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
                            <p v-if="createForm.errors.coop_id" class="text-sm text-red-600">
                                {{ createForm.errors.coop_id }}
                            </p>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            @click="isCreateDialogOpen = false"
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

            <!-- Role Management Dialog -->
            <Dialog v-model:open="isRoleDialogOpen">
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
                                    :class="{ 'border-red-500': roleForm.errors.name }"
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
                                    :class="{ 'border-red-500': roleForm.errors.level }"
                                />
                                <p v-if="roleForm.errors.level" class="text-sm text-red-600">
                                    {{ roleForm.errors.level }}
                                </p>
                            </div>

                            <div class="grid gap-2">
                                <Label for="role_status">Status</Label>
                                <select
                                    id="role_status"
                                    v-model="roleForm.is_active"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="true">Active</option>
                                    <option value="false">Inactive</option>
                                </select>
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
                                    :class="{ 'border-red-500': roleForm.errors.description }"
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
                            <div class="mb-2 text-sm font-medium text-gray-700">Existing Roles</div>
                            <div class="space-y-2">
                                <div
                                    v-for="role in availableRoles"
                                    :key="role.id"
                                    class="flex flex-col gap-3 rounded-md border border-gray-200 p-3 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div class="flex items-start gap-3">
                                        <Badge :class="getRoleLevelBadgeColor(role.level)" class="border">
                                            L{{ role.level }}
                                        </Badge>
                                        <div>
                                            <div class="font-medium text-gray-900">{{ role.name }}</div>
                                            <div class="text-xs text-gray-500">
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
                                        <Button variant="ghost" size="sm" @click="startEditRole(role)">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="text-red-600 hover:text-red-700"
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
