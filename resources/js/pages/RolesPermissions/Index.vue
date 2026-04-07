<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
const expandedModules = ref<Record<string, boolean>>({});
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
        const parts = permission.name.split(' ');
        const moduleKey = parts.slice(1).join(' ') || 'general';
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

const moduleLabels = computed(() => {
    return Object.keys(permissionGroups.value).sort((a, b) => a.localeCompare(b));
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

const formatModuleLabel = (moduleKey: string) => {
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

const isModuleExpanded = (moduleKey: string) => {
    if (normalizedPermissionSearch.value) return true;
    return expandedModules.value[moduleKey] ?? false;
};

const toggleModule = (moduleKey: string) => {
    expandedModules.value[moduleKey] = !isModuleExpanded(moduleKey);
};

const moduleSelectedCount = (moduleKey: string) => {
    const permissions = filteredPermissionGroups.value[moduleKey] ?? [];
    return permissions.filter((permission) => form.permission_ids.includes(permission.id)).length;
};

const selectModulePermissions = (moduleKey: string) => {
    const permissions = filteredPermissionGroups.value[moduleKey] ?? [];
    const next = new Set(form.permission_ids);
    permissions.forEach((permission) => next.add(permission.id));
    form.permission_ids = Array.from(next);
};

const clearModulePermissions = (moduleKey: string) => {
    const permissions = filteredPermissionGroups.value[moduleKey] ?? [];
    const removeIds = new Set(permissions.map((permission) => permission.id));
    form.permission_ids = form.permission_ids.filter((id) => !removeIds.has(id));
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
        });
        return;
    }

    form.post('/roles', {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
        },
    });
};

const deleteRole = (role: Role) => {
    if (!confirm(`Delete role "${role.name}"?`)) return;
    router.delete(`/roles/${role.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (selectedRoleId.value === role.id) {
                resetForm();
            }
        },
    });
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="rounded-2xl border border-border/70 bg-muted/40 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-semibold text-foreground">Roles & Permissions</h1>
                        <p class="text-sm text-muted-foreground">Manage access, keep roles consistent, and audit permissions with clarity.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-full border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground">
                            Selected permissions: <span class="text-foreground">{{ selectedPermissionCount }}</span>
                        </div>
                        <div class="rounded-full border border-border bg-background px-4 py-2 text-sm font-medium text-muted-foreground">
                            Total permissions: <span class="text-foreground">{{ totalPermissionsCount }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-base font-semibold">Roles</CardTitle>
                                <p class="text-sm text-muted-foreground">Select a role to edit or create a new one.</p>
                            </div>
                            <Badge class="bg-muted/80 text-muted-foreground">
                                {{ filteredRoles.length }} roles
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="space-y-2">
                            <Input
                                v-model="roleSearch"
                                placeholder="Search roles..."
                                class="bg-background"
                            />
                            <Button type="button" variant="secondary" class="w-full" @click="resetForm">
                                + New Role
                            </Button>
                        </div>

                        <div class="space-y-2">
                            <Card
                                v-for="role in filteredRoles"
                                :key="role.id"
                                class="border border-border/70 p-3 transition hover:bg-muted/40"
                                :class="selectedRoleId === role.id ? 'bg-muted/70 ring-1 ring-primary/20' : ''"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <button type="button" class="text-left" @click="selectRole(role)">
                                        <div class="text-sm font-semibold text-foreground">{{ role.name }}</div>
                                        <div class="text-xs text-muted-foreground">Level {{ role.level }}</div>
                                    </button>
                                    <div class="flex flex-col items-end gap-1">
                                        <Badge :class="role.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">
                                            {{ role.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                        <span class="text-xs text-muted-foreground">{{ role.permissions.length }} perms</span>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <span class="text-xs text-muted-foreground line-clamp-2">
                                        {{ role.description || 'No description' }}
                                    </span>
                                    <Button size="sm" variant="ghost" class="text-red-500" @click="deleteRole(role)">Delete</Button>
                                </div>
                            </Card>
                        </div>

                        <div v-if="!filteredRoles.length" class="rounded-lg border border-dashed border-border/70 bg-background/70 p-4 text-center text-xs text-muted-foreground">
                            No roles match your search.
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base font-semibold">
                            {{ isEditing ? 'Edit Role' : 'Create Role' }}
                        </CardTitle>
                        <p class="text-sm text-muted-foreground">Modify role details and assign permissions.</p>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="role_name">Role Name</Label>
                                <Input id="role_name" v-model="form.name" placeholder="Role name" />
                                <p v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="role_level">Hierarchy Level</Label>
                                <Input id="role_level" type="number" min="0" max="20" v-model="form.level" />
                                <p v-if="form.errors.level" class="text-xs text-red-600">{{ form.errors.level }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="role_description">Description</Label>
                                <Textarea id="role_description" v-model="form.description" rows="3" placeholder="Role description" />
                                <p v-if="form.errors.description" class="text-xs text-red-600">{{ form.errors.description }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label>Active</Label>
                                <div class="flex items-center gap-2 rounded-lg border border-border px-3 py-2">
                                    <Checkbox
                                        :model-value="form.is_active"
                                        @update:model-value="(value) => (form.is_active = Boolean(value))"
                                    />
                                    <span class="text-sm text-muted-foreground">Role is active</span>
                                </div>
                                <p v-if="form.errors.is_active" class="text-xs text-red-600">{{ form.errors.is_active }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-border/70 bg-muted/30 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Permission Explorer</h3>
                                    <p class="text-xs text-muted-foreground">Search, filter, and assign permissions with fewer distractions.</p>
                                </div>
                                <Badge class="bg-primary/10 text-primary">{{ selectedPermissionCount }} selected</Badge>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-3">
                                <div class="min-w-55 flex-1">
                                    <Input
                                        v-model="permissionSearch"
                                        placeholder="Search permissions..."
                                        class="bg-background"
                                    />
                                </div>
                                <Button type="button" variant="outline" size="sm" @click="selectAllPermissions">
                                    Select all
                                </Button>
                                <Button type="button" variant="ghost" size="sm" @click="clearAllPermissions">
                                    Clear all
                                </Button>
                                <span class="text-xs text-muted-foreground">
                                    Showing {{ filteredPermissionsCount }} of {{ totalPermissionsCount }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div v-if="!filteredModuleLabels.length" class="rounded-lg border border-dashed border-border/70 bg-background/70 p-6 text-center text-sm text-muted-foreground">
                                    No permissions match your search.
                                </div>

                                <div
                                    v-for="moduleKey in filteredModuleLabels"
                                    :key="moduleKey"
                                    class="rounded-xl border border-border/60 bg-background p-4"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <button
                                            type="button"
                                            class="flex items-center gap-2 text-left text-sm font-semibold text-foreground"
                                            @click="toggleModule(moduleKey)"
                                        >
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full border border-border/70 text-xs text-muted-foreground"
                                            >
                                                {{ isModuleExpanded(moduleKey) ? '−' : '+' }}
                                            </span>
                                            {{ formatModuleLabel(moduleKey) }}
                                        </button>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                            <span>{{ moduleSelectedCount(moduleKey) }} selected</span>
                                            <Button type="button" variant="ghost" size="sm" @click="selectModulePermissions(moduleKey)">
                                                Select module
                                            </Button>
                                            <Button type="button" variant="ghost" size="sm" @click="clearModulePermissions(moduleKey)">
                                                Clear
                                            </Button>
                                        </div>
                                    </div>

                                    <div v-if="isModuleExpanded(moduleKey)" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        <label
                                            v-for="permission in filteredPermissionGroups[moduleKey]"
                                            :key="permission.id"
                                            class="flex items-center gap-2 rounded-md border border-border/70 px-3 py-2 text-sm"
                                        >
                                            <Checkbox
                                                :model-value="form.permission_ids.includes(permission.id)"
                                                @update:model-value="(value) => togglePermission(permission.id, Boolean(value))"
                                            />
                                            <span class="text-muted-foreground">{{ permission.name }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <Button type="button" variant="outline" @click="resetForm">Cancel</Button>
                            <Button type="button" :disabled="form.processing" @click="submitForm">
                                {{ isEditing ? 'Save Changes' : 'Create Role' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
