<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Search } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ToggleSwitch } from '@/components/ui/toggle-switch';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    description: string | null;
    level: number;
    is_active: boolean;
    permissions: Permission[];
}

const props = defineProps<{
    roles: Role[];
    permissions: Permission[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles & Permissions', href: '/roles-permissions' },
];

const selectedRoleId = ref<number | null>(null);
const roleSearch = ref('');
const permissionSearch = ref('');
const ACCOUNT_ACCESS_MODULE_KEY = '__account_access__';
const RECYCLE_BIN_MODULE_KEY = 'recycle-bin';
const ACCOUNT_ACCESS_PERMISSIONS = ['create user-accounts', 'assign roles'];
const isPermissionExplorerOpen = ref(false);
const isCreateRoleDialogOpen = ref(false);
const isModuleDialogOpen = ref(false);
const selectedModuleKey = ref<string | null>(null);
const form = useForm({
    name: '',
    description: '',
    level: 1,
    is_active: true,
    permission_ids: [] as number[],
});

const isEditing = computed(() => selectedRoleId.value !== null);
const selectedPermissionCount = computed(() => form.permission_ids.length);
const totalPermissionsCount = computed(() => props.permissions.length);
const filteredRoles = computed(() => {
    const query = roleSearch.value.trim().toLowerCase();
    if (!query) return props.roles;
    return props.roles.filter((role) => {
        const description = role.description ?? '';
        return (
            role.name.toLowerCase().includes(query) ||
            description.toLowerCase().includes(query)
        );
    });
});

const permissionGroups = computed(() => {
    const groups: Record<string, Permission[]> = {};
    props.permissions.forEach((permission) => {
        const moduleKey = ACCOUNT_ACCESS_PERMISSIONS.includes(permission.name)
            ? ACCOUNT_ACCESS_MODULE_KEY
            : permission.name.includes(' recycle-bin')
                ? RECYCLE_BIN_MODULE_KEY
                : (permission.name.split(' ').slice(1).join(' ') || 'general');
        if (!groups[moduleKey]) {
            groups[moduleKey] = [];
        }
        groups[moduleKey].push(permission);
    });

    Object.values(groups).forEach((group) => {
        group.sort((a, b) => a.name.localeCompare(b.name));
    });

    return groups;
});

const normalizedPermissionSearch = computed(() => permissionSearch.value.trim().toLowerCase());

const filteredPermissionGroups = computed(() => {
    if (!normalizedPermissionSearch.value) return permissionGroups.value;

    const groups: Record<string, Permission[]> = {};
    Object.entries(permissionGroups.value).forEach(([moduleKey, permissions]) => {
        const matches = permissions.filter((permission) =>
            permission.name.toLowerCase().includes(normalizedPermissionSearch.value)
        );
        if (matches.length) {
            groups[moduleKey] = matches;
        }
    });

    return groups;
});

const filteredModuleLabels = computed(() =>
    Object.keys(filteredPermissionGroups.value).sort((a, b) => a.localeCompare(b))
);

const filteredPermissionsCount = computed(() =>
    Object.values(filteredPermissionGroups.value).reduce((sum, group) => sum + group.length, 0)
);

const hasVisiblePermissions = computed(() =>
    filteredModuleLabels.value.length > 0
);

const activeRole = computed(() =>
    props.roles.find((role) => role.id === selectedRoleId.value) ?? null
);

const activeModuleTitle = computed(() => {
    if (!selectedModuleKey.value) return '';
    if (selectedModuleKey.value === ACCOUNT_ACCESS_MODULE_KEY) return 'Account Access';
    return formatModuleLabel(selectedModuleKey.value);
});

const activeModulePermissions = computed(() => {
    if (!selectedModuleKey.value) return [];
    return permissionGroups.value[selectedModuleKey.value] ?? [];
});

const activeModuleSelectedCount = computed(() =>
    activeModulePermissions.value.filter((permission) => form.permission_ids.includes(permission.id)).length
);

const activeModuleTotalCount = computed(() => activeModulePermissions.value.length);

const formatModuleLabel = (moduleKey: string) => {
    if (moduleKey === ACCOUNT_ACCESS_MODULE_KEY) {
        return 'Account Access';
    }
    if (moduleKey === RECYCLE_BIN_MODULE_KEY) {
        return 'Recycle Bin';
    }

    return moduleKey
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

const selectRole = (role: Role) => {
    selectedRoleId.value = role.id;
    form.name = role.name;
    form.description = role.description ?? '';
    form.level = role.level;
    form.is_active = role.is_active;
    form.permission_ids = role.permissions.map((permission) => permission.id);
    form.clearErrors();
};

const resetForm = () => {
    selectedRoleId.value = null;
    form.reset();
    form.clearErrors();
    form.permission_ids = [];
    form.is_active = true;
    form.level = 1;
    isModuleDialogOpen.value = false;
    selectedModuleKey.value = null;
};

const togglePermission = (permissionId: number, checked: boolean) => {
    if (checked) {
        if (!form.permission_ids.includes(permissionId)) {
            form.permission_ids.push(permissionId);
        }
    } else {
        form.permission_ids = form.permission_ids.filter((id) => id !== permissionId);
    }
};

const moduleSelectedCount = (moduleKey: string) => {
    const permissions = permissionGroups.value[moduleKey] ?? [];
    return permissions.filter((permission) => form.permission_ids.includes(permission.id)).length;
};

const modulePermissionsCount = (moduleKey: string) => {
    return (permissionGroups.value[moduleKey] ?? []).length;
};

const moduleSelectionRatio = (moduleKey: string) => {
    const total = modulePermissionsCount(moduleKey);
    if (!total) return 0;
    return (moduleSelectedCount(moduleKey) / total) * 100;
};

const selectModulePermissions = (moduleKey: string) => {
    const permissions = permissionGroups.value[moduleKey] ?? [];
    const next = new Set(form.permission_ids);
    permissions.forEach((permission) => next.add(permission.id));
    form.permission_ids = Array.from(next);
};

const clearModulePermissions = (moduleKey: string) => {
    const permissions = permissionGroups.value[moduleKey] ?? [];
    const removeIds = new Set(permissions.map((permission) => permission.id));
    form.permission_ids = form.permission_ids.filter((id) => !removeIds.has(id));
};

const openRolePermissions = (role: Role) => {
    selectRole(role);
    permissionSearch.value = '';
    isModuleDialogOpen.value = false;
    selectedModuleKey.value = null;
    isPermissionExplorerOpen.value = true;
};

const closePermissionExplorer = () => {
    isModuleDialogOpen.value = false;
    isPermissionExplorerOpen.value = false;
    resetForm();
};

const openCreateRoleDialog = () => {
    resetForm();
    permissionSearch.value = '';
    isCreateRoleDialogOpen.value = true;
};

const closeCreateRoleDialog = () => {
    isCreateRoleDialogOpen.value = false;
    resetForm();
};

const openModuleDetails = (moduleKey: string) => {
    selectedModuleKey.value = moduleKey;
    isModuleDialogOpen.value = true;
};

const closeModuleDetails = () => {
    isModuleDialogOpen.value = false;
    selectedModuleKey.value = null;
};

const selectAllPermissions = () => {
    form.permission_ids = props.permissions.map((permission) => permission.id);
};

const clearAllPermissions = () => {
    form.permission_ids = [];
};

const submitForm = () => {
    if (isEditing.value && selectedRoleId.value) {
        form.put(`/roles/${selectedRoleId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                closePermissionExplorer();
            },
        });
        return;
    }

    form.post('/roles', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateRoleDialog();
        },
    });
};

const deleteRole = (role: Role) => {
    if (!confirm(`Delete role "${role.name}"?`)) return;
    router.delete(`/roles/${role.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedRoleId.value === role.id) {
                closePermissionExplorer();
            }
        },
    });
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="rp-page flex flex-col gap-6 p-6">
            <div class="rounded-2xl border border-slate-300/95 bg-white/82 p-5 shadow-[0_16px_30px_-24px_rgba(15,23,42,0.55)] backdrop-blur-sm transition-all duration-200 ease-in-out dark:border-zinc-600 dark:bg-zinc-900/82 dark:shadow-[0_18px_34px_-24px_rgba(0,0,0,0.82)]">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-foreground">Roles & Permissions</h1>
                        <p class="text-sm text-muted-foreground">
                            Manage role access through focused overlays and clean module-based permission controls.
                        </p>
                    </div>
                    <Button type="button" class="gap-2" @click="openCreateRoleDialog">
                        <Plus class="h-4 w-4" />
                        Create Role
                    </Button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <div class="relative min-w-72 flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="roleSearch"
                            placeholder="Search roles by name or description..."
                            class="bg-background pl-10"
                        />
                    </div>
                    <div class="rounded-full border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground">
                        {{ filteredRoles.length }} roles
                    </div>
                    <div class="rounded-full border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground">
                        {{ totalPermissionsCount }} permissions
                    </div>
                </div>
            </div>

            <div v-if="filteredRoles.length" class="rp-role-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card
                    v-for="role in filteredRoles"
                    :key="role.id"
                    class="rp-role-card border border-l-4 border-slate-300 bg-white/85 shadow-sm backdrop-blur-sm transition-all duration-200 ease-in-out hover:scale-[1.02] hover:shadow-lg dark:border-zinc-600 dark:bg-zinc-900/85 dark:shadow-md"
                    :class="role.is_active ? 'border-l-green-500' : 'border-l-red-400'"
                >
                    <CardContent class="flex h-full flex-col gap-4 p-5">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h2 class="rp-role-card-title text-lg font-bold text-foreground">
                                    {{ role.name }}
                                </h2>
                                <Badge class="bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">
                                    Level {{ role.level }}
                                </Badge>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :class="role.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300'">
                                    {{ role.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <span class="text-sm font-medium text-muted-foreground">
                                    {{ role.permissions.length }} permissions
                                </span>
                            </div>

                            <TooltipProvider :delay-duration="100">
                                <Tooltip>
                                    <TooltipTrigger as-child>
                                        <p class="line-clamp-2 text-sm leading-relaxed text-muted-foreground">
                                            {{ role.description || 'No description provided for this role.' }}
                                        </p>
                                    </TooltipTrigger>
                                    <TooltipContent side="bottom" class="max-w-sm text-xs">
                                        {{ role.description || 'No description provided for this role.' }}
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>

                        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
                            <Button type="button" variant="secondary" size="sm" class="gap-2" @click="openRolePermissions(role)">
                                Manage
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="ghost"
                                class="table-action-btn table-action-delete text-red-500"
                                @click="deleteRole(role)"
                            >
                                Delete
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-else class="rounded-xl border border-dashed border-border/70 bg-background/75 p-8 text-center text-sm text-muted-foreground backdrop-blur-sm">
                No roles match your search.
            </div>
        </div>

        <Dialog
            :open="isPermissionExplorerOpen"
            @update:open="(open) => {
                if (!open) {
                    closePermissionExplorer();
                }
            }"
        >
            <DialogContent
                class="h-[95vh] w-[96vw] max-w-[96vw] gap-0 overflow-hidden border-slate-300 bg-white/95 p-0 backdrop-blur-sm sm:max-w-[96vw] xl:max-w-6xl dark:border-zinc-600 dark:bg-zinc-900/95"
                @open-auto-focus.prevent
            >
                <div class="flex h-full min-h-0 flex-col">
                    <DialogHeader class="m-6 mb-0 rounded-xl border border-slate-300/95 bg-background/90 p-5 shadow-[0_16px_28px_-24px_rgba(15,23,42,0.6)] dark:border-zinc-600 dark:bg-zinc-900/80 dark:shadow-[0_18px_30px_-24px_rgba(0,0,0,0.84)]">
                        <div class="flex flex-wrap items-start justify-between gap-3 pr-8">
                            <div class="space-y-2">
                                <DialogTitle class="text-xl font-semibold text-foreground">
                                    {{ activeRole?.name || 'Role Permission Explorer' }}
                                </DialogTitle>
                                <DialogDescription class="text-sm text-muted-foreground">
                                    Configure permissions in grouped modules, then save to update this role.
                                </DialogDescription>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge class="bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">
                                    Level {{ activeRole?.level ?? form.level }}
                                </Badge>
                                <Badge :class="form.is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300'">
                                    {{ form.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>
                        </div>
                    </DialogHeader>

                    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                        <div class="rounded-xl border border-slate-300/95 bg-background/88 p-4 shadow-[0_14px_26px_-22px_rgba(15,23,42,0.58)] dark:border-zinc-600 dark:bg-zinc-800/78 dark:shadow-[0_16px_28px_-22px_rgba(0,0,0,0.86)]">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="edit_role_name">Role Name</Label>
                                    <Input id="edit_role_name" v-model="form.name" placeholder="Role name" />
                                    <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>
                                <div class="space-y-2">
                                    <Label for="edit_role_level">Hierarchy Level</Label>
                                    <Input id="edit_role_level" type="number" min="0" max="20" v-model="form.level" />
                                    <p v-if="form.errors.level" class="text-xs text-red-600">{{ form.errors.level }}</p>
                                </div>
                            </div>
                            <div class="mt-4 grid gap-4 md:grid-cols-[1fr_auto]">
                                <div class="space-y-2">
                                    <Label for="edit_role_description">Description</Label>
                                    <Textarea id="edit_role_description" v-model="form.description" rows="3" placeholder="Role description" />
                                    <p v-if="form.errors.description" class="text-xs text-red-600">{{ form.errors.description }}</p>
                                </div>
                                <div class="space-y-2 md:min-w-48">
                                    <Label>Active</Label>
                                    <div class="flex items-center gap-3 rounded-lg border border-border/70 bg-background px-3 py-2 dark:border-zinc-700">
                                        <ToggleSwitch
                                            :model-value="form.is_active"
                                            @update:model-value="(value) => (form.is_active = Boolean(value))"
                                        />
                                        <span class="text-sm text-muted-foreground">Role is active</span>
                                    </div>
                                    <p v-if="form.errors.is_active" class="text-xs text-red-600">{{ form.errors.is_active }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-300/95 bg-background/88 p-4 shadow-[0_14px_26px_-22px_rgba(15,23,42,0.58)] dark:border-zinc-600 dark:bg-zinc-800/78 dark:shadow-[0_16px_28px_-22px_rgba(0,0,0,0.86)]">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="relative min-w-72 flex-1">
                                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <Input
                                        v-model="permissionSearch"
                                        placeholder="Search permission groups..."
                                        class="bg-background pl-10"
                                    />
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="selectAllPermissions">
                                        Select All
                                    </Button>
                                    <Button type="button" variant="ghost" size="sm" @click="clearAllPermissions">
                                        Clear All
                                    </Button>
                                </div>
                            </div>

                            <div class="mt-3 text-xs font-medium text-muted-foreground">
                                {{ selectedPermissionCount }} selected / {{ totalPermissionsCount }} total permissions
                            </div>

                            <div v-if="!hasVisiblePermissions" class="mt-4 rounded-lg border border-dashed border-border/70 bg-background/70 p-5 text-center text-sm text-muted-foreground">
                                No permission groups match your search.
                            </div>

                            <div v-else class="rp-dialog-grid mt-4 grid gap-3 lg:grid-cols-2">
                                <div
                                    v-for="moduleKey in filteredModuleLabels"
                                    :key="moduleKey"
                                    class="cursor-pointer rounded-xl border border-slate-300/95 bg-white/95 p-4 shadow-sm transition-all duration-200 ease-in-out hover:-translate-y-px hover:bg-muted/35 hover:shadow-md dark:border-zinc-600 dark:bg-zinc-900/78 dark:shadow-[0_10px_24px_-16px_rgba(0,0,0,0.8)] dark:hover:bg-zinc-800/82 dark:hover:shadow-[0_14px_28px_-16px_rgba(0,0,0,0.92)]"
                                    :class="moduleSelectedCount(moduleKey) > 0 ? 'ring-1 ring-primary/25 dark:ring-primary/35' : ''"
                                    role="button"
                                    tabindex="0"
                                    @click="openModuleDetails(moduleKey)"
                                    @keydown.space.prevent="openModuleDetails(moduleKey)"
                                    @keydown.enter.prevent="openModuleDetails(moduleKey)"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 class="text-sm font-semibold text-foreground">{{ formatModuleLabel(moduleKey) }}</h3>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                {{ moduleSelectedCount(moduleKey) }} / {{ modulePermissionsCount(moduleKey) }} selected
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                class="h-7 px-2 text-xs"
                                                @click.stop="openModuleDetails(moduleKey)"
                                            >
                                                Select module
                                            </Button>
                                            <Button
                                                type="button"
                                                size="sm"
                                                variant="ghost"
                                                class="h-7 px-2 text-xs"
                                                @click.stop="clearModulePermissions(moduleKey)"
                                            >
                                                Clear
                                            </Button>
                                        </div>
                                    </div>
                                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-muted dark:bg-zinc-700">
                                        <div
                                            class="h-full rounded-full bg-primary transition-all duration-300 ease-in-out"
                                            :style="{ width: `${moduleSelectionRatio(moduleKey)}%` }"
                                        />
                                    </div>
                                </div>
                            </div>

                            <p class="mt-4 text-xs text-muted-foreground">
                                Showing {{ filteredPermissionsCount }} of {{ totalPermissionsCount }} permissions.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="sticky bottom-0 z-10 border-t border-border/70 bg-white/95 p-6 dark:border-zinc-700 dark:bg-zinc-900/95">
                        <Button type="button" variant="outline" @click="closePermissionExplorer">Cancel</Button>
                        <Button type="button" :disabled="form.processing" @click="submitForm">
                            Save Changes
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="isModuleDialogOpen"
            @update:open="(open) => {
                if (!open) {
                    closeModuleDetails();
                }
            }"
        >
            <DialogContent class="z-80 w-[95vw] max-w-[95vw] border-slate-300 bg-white/95 backdrop-blur-sm sm:max-w-2xl dark:border-zinc-600 dark:bg-zinc-800/95">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold">{{ activeModuleTitle }}</DialogTitle>
                    <DialogDescription>
                        Toggle individual permissions for this module.
                    </DialogDescription>
                </DialogHeader>

                <div class="max-h-[58vh] space-y-2 overflow-y-auto pr-1">
                    <div
                        v-for="permission in activeModulePermissions"
                        :key="permission.id"
                        class="flex items-center justify-between gap-3 border-b border-border/60 py-3 last:border-b-0 dark:border-zinc-700"
                    >
                        <div class="text-sm text-foreground">
                            {{ permission.name }}
                        </div>
                        <ToggleSwitch
                            :model-value="form.permission_ids.includes(permission.id)"
                            @update:model-value="(value) => togglePermission(permission.id, Boolean(value))"
                        />
                    </div>

                    <div v-if="!activeModulePermissions.length" class="rounded-md border border-dashed border-border/70 p-4 text-center text-sm text-muted-foreground">
                        No permissions available in this module.
                    </div>
                </div>

                <p class="text-xs text-muted-foreground">
                    {{ activeModuleSelectedCount }} / {{ activeModuleTotalCount }} selected
                </p>

                <DialogFooter>
                    <DialogClose as-child>
                        <Button type="button" variant="outline" @click="closeModuleDetails">Cancel</Button>
                    </DialogClose>
                    <DialogClose as-child>
                        <Button type="button" @click="closeModuleDetails">Apply</Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="isCreateRoleDialogOpen"
            @update:open="(open) => {
                isCreateRoleDialogOpen = open;
                if (!open) {
                    resetForm();
                }
            }"
        >
            <DialogContent class="max-w-lg border-slate-300 bg-white/95 backdrop-blur-sm dark:border-zinc-600 dark:bg-zinc-900/95">
                <DialogHeader>
                    <DialogTitle class="text-lg font-semibold">Create Role</DialogTitle>
                    <DialogDescription>
                        Create a new role profile, then assign permissions in one clear workflow.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create_role_name">Role Name</Label>
                        <Input id="create_role_name" v-model="form.name" placeholder="Role name" />
                        <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create_role_level">Hierarchy Level</Label>
                        <Input id="create_role_level" type="number" min="0" max="20" v-model="form.level" />
                        <p v-if="form.errors.level" class="text-xs text-red-600">{{ form.errors.level }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="create_role_description">Description</Label>
                        <Textarea id="create_role_description" v-model="form.description" rows="3" placeholder="Role description" />
                        <p v-if="form.errors.description" class="text-xs text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label>Active</Label>
                        <div class="flex items-center gap-3 rounded-lg border border-border/70 bg-background px-3 py-2 dark:border-zinc-700">
                            <ToggleSwitch
                                :model-value="form.is_active"
                                @update:model-value="(value) => (form.is_active = Boolean(value))"
                            />
                            <span class="text-sm text-muted-foreground">Role is active</span>
                        </div>
                        <p v-if="form.errors.is_active" class="text-xs text-red-600">{{ form.errors.is_active }}</p>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="closeCreateRoleDialog">Cancel</Button>
                    <Button type="button" :disabled="form.processing" @click="submitForm">
                        Create Role
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
:global(html[data-a11y-size='large']) .rp-page .rp-role-grid {
    grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr));
}

:global(html[data-a11y-size='large']) .rp-page .rp-role-card-title {
    font-size: 1.25rem;
    line-height: 1.5;
}

:global(html[data-a11y-size='large']) .rp-page .rp-dialog-grid {
    grid-template-columns: 1fr;
}
</style>
