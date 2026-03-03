<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import {
    ShieldCheck, Users, Key, Crown, Briefcase, ClipboardList,
    UserCheck, UserCog, ChevronRight, Lock, Save, CheckCircle2, Settings, Plus, Pencil, Trash2,
} from "lucide-vue-next";
import { computed, ref, watch } from "vue";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalError, swalConfirmDelete } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";

// ── Types ─────────────────────────────────────────────────────────────────────
type OfficeRoleData = {
    id: number; name: string; display_name: string; description: string | null; is_system: boolean;
};
type SpatieRole = { id: number; name: string; permissions: string[]; users_count: number };
type UserItem    = { id: number; name: string; email: string; created_at: string; roles: string[] };

const props = defineProps<{
    users:          UserItem[];
    systemRoles:    string[];
    officeRoles:    OfficeRoleData[];
    roleGroups:     Record<string, any[]>;
    spatieRoles:    SpatieRole[];
    allPermissions: Record<string, string[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: "Users & Roles", href: "/super-admin/users" }];

// ── Tabs ─────────────────────────────────────────────────────────────────────
type Tab = "roles" | "users" | "permissions";
const activeTab = ref<Tab>("roles");

// ── Office Role Config ────────────────────────────────────────────────────────
// Default icon/color mapping for known roles
const DEFAULT_ROLE_STYLES: Record<string, { icon: any; color: string; bg: string; border: string }> = {
    chairperson:      { icon: Crown,         color: "text-violet-700", bg: "bg-violet-50",  border: "border-violet-200" },
    general_manager:  { icon: Briefcase,     color: "text-blue-700",   bg: "bg-blue-50",    border: "border-blue-200"   },
    officer:          { icon: UserCheck,     color: "text-emerald-700",bg: "bg-emerald-50", border: "border-emerald-200"},
    committee_member: { icon: ClipboardList, color: "text-amber-700",  bg: "bg-amber-50",   border: "border-amber-200"  },
    member:           { icon: Users,         color: "text-slate-600",  bg: "bg-slate-50",   border: "border-slate-200"  },
};

// Fallback style for custom roles
const FALLBACK_STYLE = { icon: UserCog, color: "text-indigo-700", bg: "bg-indigo-50", border: "border-indigo-200" };

// Dynamically generate role meta from backend data
const OFFICE_ROLE_META = computed(() => {
    const meta: Record<string, { label: string; icon: any; color: string; bg: string; border: string }> = {};
    props.officeRoles.forEach(role => {
        const style = DEFAULT_ROLE_STYLES[role.name] || FALLBACK_STYLE;
        meta[role.name] = {
            label:  role.display_name,
            icon:   style.icon,
            color:  style.color,
            bg:     style.bg,
            border: style.border,
        };
    });
    return meta;
});

// ── Roles tab — navigation ────────────────────────────────────────────────────
function viewRoleDetails(roleName: string) {
    const role = props.officeRoles.find(r => r.name === roleName);
    if (role) {
        router.visit(`/super-admin/office-roles/${role.id}`);
    }
}

// ── Roles tab — detail panel (removed) ────────────────────────────────────────

// ── Users tab — system role assignment ───────────────────────────────────────
const pendingRoles = ref<Record<number, string>>({});

function initPending(userId: number, role: string) {
    pendingRoles.value[userId] = role;
}

function saveUserRole(user: UserItem) {
    const newRole = pendingRoles.value[user.id];
    if (!newRole || newRole === user.roles[0]) return;
    router.patch(`/super-admin/users/${user.id}/role`, { role: newRole }, {
        preserveScroll: true,
        onSuccess: () => swalSuccess("Role Updated!", `${user.name} is now a ${newRole}.`),
        onError:   () => swalError("Update Failed", "Could not update the role."),
    });
}

async function deleteUser(user: UserItem) {
    const result = await swalConfirmDelete(user.name);
    if (result.isConfirmed) {
        router.delete(`/super-admin/users/${user.id}`, {
            preserveScroll: true,
            onSuccess: () => swalSuccess("Deleted!", `${user.name} has been deleted.`),
            onError:   () => swalError("Delete Failed", "Could not delete the user."),
        });
    }
}

const initials = (name: string) => name.split(" ").map(p => p[0]).slice(0, 2).join("").toUpperCase();

const systemRoleBadge: Record<string, string> = {
    "super_admin":      "bg-violet-50 text-violet-700 ring-violet-200",
    "coop_admin":       "bg-blue-50 text-blue-700 ring-blue-200",
    "chairperson":      "bg-purple-50 text-purple-700 ring-purple-200",
    "general_manager":  "bg-indigo-50 text-indigo-700 ring-indigo-200",
    "officer":          "bg-emerald-50 text-emerald-700 ring-emerald-200",
    "committee_member": "bg-amber-50 text-amber-700 ring-amber-200",
    "member":           "bg-slate-50 text-slate-600 ring-slate-200",
};

// ── Permissions tab ───────────────────────────────────────────────────────────
const selectedSpatieRoleId = ref<number | null>(props.spatieRoles[0]?.id ?? null);

const selectedSpatieRole = computed<SpatieRole | null>(
    () => props.spatieRoles.find(r => r.id === selectedSpatieRoleId.value) ?? null
);

// Local editable copy of permissions per role (keyed by role id)
const localPerms = ref<Record<number, Set<string>>>({});

watch(() => props.spatieRoles, (roles) => {
    roles.forEach(r => {
        if (!localPerms.value[r.id]) {
            localPerms.value[r.id] = new Set(r.permissions);
        }
    });
}, { immediate: true });

function hasPermission(roleId: number, perm: string): boolean {
    return localPerms.value[roleId]?.has(perm) ?? false;
}

function togglePermission(roleId: number, perm: string) {
    const role = props.spatieRoles.find(r => r.id === roleId);
    if (!role || role.name === "super_admin") return;
    const current = localPerms.value[roleId] ?? new Set<string>();
    if (current.has(perm)) current.delete(perm);
    else current.add(perm);
    localPerms.value[roleId] = new Set(current); // force reactivity
}

function selectAllInModule(roleId: number, perms: string[]) {
    const current = localPerms.value[roleId] ?? new Set<string>();
    perms.forEach(p => current.add(p));
    localPerms.value[roleId] = new Set(current);
}

function clearAllInModule(roleId: number, perms: string[]) {
    const current = localPerms.value[roleId] ?? new Set<string>();
    perms.forEach(p => current.delete(p));
    localPerms.value[roleId] = new Set(current);
}

const savingPerms = ref(false);

function savePermissions() {
    if (!selectedSpatieRole.value || selectedSpatieRole.value.name === "super_admin") return;
    savingPerms.value = true;
    const perms = [...(localPerms.value[selectedSpatieRole.value.id] ?? [])];
    router.patch(`/super-admin/roles/${selectedSpatieRole.value.id}/permissions`, { permissions: perms }, {
        preserveScroll: true,
        onSuccess: () => swalSuccess("Saved!", `Permissions for "${selectedSpatieRole.value!.name}" updated.`),
        onError:   () => swalError("Failed", "Could not save permissions."),
        onFinish:  () => { savingPerms.value = false; },
    });
}

const MODULE_LABELS: Record<string, string> = {
    cooperatives: "Cooperatives",
    members:      "Members",
    officers:     "Officers",
    activities:   "Activities",
    financials:   "Financials",
    trainings:    "Trainings / L&D",
    reports:      "Reports",
    users:        "User Management",
    system:       "System Config",
    logs:         "Activity Logs",
};

function permLabel(perm: string): string {
    return perm.split(".").slice(1).join(" ").replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
}

const SPATIE_ROLE_META: Record<string, { label: string; icon: any; color: string }> = {
    super_admin:      { label: "Super Admin",      icon: ShieldCheck,   color: "text-violet-600" },
    coop_admin:       { label: "Coop Admin",       icon: UserCog,       color: "text-blue-600"   },
    chairperson:      { label: "Chairperson",      icon: Crown,         color: "text-purple-600" },
    general_manager:  { label: "General Manager",  icon: Briefcase,     color: "text-indigo-600" },
    officer:          { label: "Officer",           icon: UserCheck,     color: "text-emerald-600"},
    committee_member: { label: "Committee Member", icon: ClipboardList, color: "text-amber-600"  },
    member:           { label: "Member",            icon: Users,         color: "text-slate-600"  },
};
</script>

<template>
    <Head title="Users & Roles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">

            <!-- Header -->
            <div class="rounded-2xl bg-linear-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                        <ShieldCheck class="h-5 w-5" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold">Users &amp; Roles</h1>
                        <p class="text-sm text-indigo-200">{{ users.length }} total users · {{ officeRoles.length }} office positions defined</p>
                    </div>
                </div>
            </div>

            <!-- Tab Nav -->
            <div class="flex gap-1 bg-white rounded-xl border border-slate-200 shadow-sm p-1.5">
                <button v-for="tab in (['roles', 'users', 'permissions'] as const)" :key="tab"
                    @click="activeTab = tab"
                    :class="[
                        'flex items-center gap-2 px-5 py-2 text-sm font-semibold rounded-lg transition-all',
                        activeTab === tab
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100',
                    ]">
                    <Crown v-if="tab === 'roles'" class="w-4 h-4" />
                    <Users v-else-if="tab === 'users'" class="w-4 h-4" />
                    <Key v-else class="w-4 h-4" />
                    {{ tab === 'roles' ? 'Office Positions' : tab === 'users' ? 'All Users' : 'Permissions' }}
                </button>
            </div>

            <!-- ══ TAB: ROLES ═════════════════════════════════════════════════ -->
            <div v-show="activeTab === 'roles'" class="space-y-5">
                <!-- Info Banner -->
                <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-blue-500 p-1 text-white mt-0.5">
                            <Crown class="h-4 w-4" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-blue-900">Office Positions (Not System Access)</p>
                            <p class="text-xs text-blue-700 mt-1">
                                These are positions within offices (e.g., General Manager, Chairperson). 
                                System access is controlled separately (Super Admin, Coop Admin, Member).
                                A user can have BOTH a system role (for dashboard access) AND an office position (their job title).
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">Click a position card to see who holds that office role.</p>
                    <Button @click="router.visit('/super-admin/office-roles')" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Settings class="w-4 h-4" />
                        Manage Office Positions
                    </Button>
                </div>

                <!-- Role cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    <button v-for="(meta, roleKey) in OFFICE_ROLE_META" :key="roleKey"
                        @click="viewRoleDetails(String(roleKey))"
                        class="group rounded-2xl border-2 p-5 text-left transition-all shadow-sm hover:shadow-md bg-white border-slate-200 hover:border-slate-300">
                        <div :class="['flex h-10 w-10 items-center justify-center rounded-xl mb-3 border', meta.bg, meta.border]">
                            <component :is="meta.icon" :class="['w-5 h-5', meta.color]" />
                        </div>
                        <p :class="['text-xs font-bold uppercase tracking-wide', meta.color]">{{ meta.label }}</p>
                        <p class="text-3xl font-extrabold text-slate-800 mt-1">
                            {{ roleGroups[roleKey]?.length ?? 0 }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">people assigned</p>
                        <div class="flex items-center gap-1 mt-3 text-xs font-semibold" :class="meta.color">
                            View details
                            <ChevronRight class="w-3 h-3 transition-transform group-hover:translate-x-0.5" />
                        </div>
                    </button>
                </div>
            </div>

            <!-- ══ TAB: ALL USERS ════════════════════════════════════════════ -->
            <div v-show="activeTab === 'users'" class="space-y-4">
                <!-- Add User Button -->
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">Manage system users and their roles</p>
                    <Button @click="router.visit('/super-admin/users/create')" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                        <Plus class="w-4 h-4" />
                        Add User
                    </Button>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <table class="w-full min-w-160">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-800">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">User</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">System Role</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Assign Role</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Joined</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-indigo-50/20 transition-colors">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ initials(u.name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ u.name }}</p>
                                            <p class="text-xs text-slate-500">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span v-for="r in u.roles" :key="r"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize mr-1"
                                        :class="systemRoleBadge[r] || 'bg-slate-50 text-slate-600 ring-slate-200'">
                                        {{ r.replace(/_/g, " ") }}
                                    </span>
                                    <span v-if="!u.roles.length" class="text-xs text-slate-400">No role</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <select
                                            class="h-8 rounded-lg border border-slate-200 bg-white px-2 text-xs text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                            :value="pendingRoles[u.id] ?? u.roles[0] ?? ''"
                                            @change="initPending(u.id, ($event.target as HTMLSelectElement).value)">
                                            <option value="" disabled>Select role</option>
                                            <option v-for="role in systemRoles" :key="role" :value="role">
                                                {{ role.replace(/_/g, " ") }}
                                            </option>
                                        </select>
                                        <Button size="sm" class="h-8 bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 gap-1.5"
                                            @click="saveUserRole(u)">
                                            <Save class="w-3 h-3" /> Save
                                        </Button>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ new Date(u.created_at).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" }) }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button size="sm" variant="outline" 
                                            @click="router.visit(`/super-admin/users/${u.id}/edit`)"
                                            class="h-8 px-2.5 text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 border-slate-200">
                                            <Pencil class="w-3.5 h-3.5" />
                                        </Button>
                                        <Button size="sm" variant="outline"
                                            @click="deleteUser(u)"
                                            class="h-8 px-2.5 text-slate-600 hover:text-red-600 hover:bg-red-50 border-slate-200">
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.length">
                                <td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ══ TAB: PERMISSIONS ══════════════════════════════════════════ -->
            <div v-show="activeTab === 'permissions'" class="flex gap-5 items-start">

                <!-- Left: role list -->
                <div class="w-56 shrink-0 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b border-slate-100 bg-slate-50">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">System Roles</p>
                    </div>
                    <nav class="p-2 space-y-0.5">
                        <button v-for="r in spatieRoles" :key="r.id"
                            @click="selectedSpatieRoleId = r.id"
                            :class="[
                                'w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-left text-sm transition-all',
                                selectedSpatieRoleId === r.id
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-slate-600 hover:bg-slate-100',
                            ]">
                            <component :is="SPATIE_ROLE_META[r.name]?.icon ?? ShieldCheck"
                                :class="['w-4 h-4 shrink-0', selectedSpatieRoleId === r.id ? 'text-white' : (SPATIE_ROLE_META[r.name]?.color ?? 'text-slate-400')]" />
                            <span class="flex-1 font-medium capitalize">{{ r.name.replace(/_/g, " ") }}</span>
                            <span :class="['text-xs rounded-full px-1.5 py-0.5 font-semibold', selectedSpatieRoleId === r.id ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500']">
                                {{ r.users_count }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Right: permission grid -->
                <div class="flex-1 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden min-h-120">
                    <template v-if="selectedSpatieRole">
                        <!-- Role header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100">
                                    <component :is="SPATIE_ROLE_META[selectedSpatieRole.name]?.icon ?? ShieldCheck"
                                        :class="['w-4 h-4', SPATIE_ROLE_META[selectedSpatieRole.name]?.color ?? 'text-indigo-600']" />
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 capitalize">{{ selectedSpatieRole.name.replace(/_/g, " ") }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ localPerms[selectedSpatieRole.id]?.size ?? 0 }} of {{ Object.values(allPermissions).flat().length }} permissions enabled
                                        · {{ selectedSpatieRole.users_count }} user(s)
                                    </p>
                                </div>
                            </div>
                            <div v-if="selectedSpatieRole.name === 'super_admin'"
                                class="flex items-center gap-2 text-violet-700 bg-violet-50 border border-violet-200 rounded-lg px-3 py-1.5 text-xs font-semibold">
                                <Lock class="w-3 h-3" /> All permissions (locked)
                            </div>
                            <Button v-else size="sm"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2"
                                :disabled="savingPerms"
                                @click="savePermissions">
                                <Save class="w-3.5 h-3.5" />
                                {{ savingPerms ? "Saving…" : "Save Permissions" }}
                            </Button>
                        </div>

                        <!-- Super admin locked -->
                        <div v-if="selectedSpatieRole.name === 'super_admin'" class="flex flex-col items-center justify-center py-16 text-center">
                            <Lock class="w-10 h-10 text-violet-200 mb-3" />
                            <p class="font-semibold text-slate-700">Super Admin has all permissions.</p>
                            <p class="text-xs mt-1 text-slate-400 max-w-xs">This role is fully privileged and cannot be restricted to protect system integrity.</p>
                        </div>

                        <!-- Permission toggles -->
                        <div v-else class="p-6 space-y-7 overflow-y-auto max-h-[calc(100vh-320px)]">
                            <div v-for="(perms, module) in allPermissions" :key="module">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">
                                        {{ MODULE_LABELS[String(module)] ?? module }}
                                    </p>
                                    <div class="flex gap-2 text-xs">
                                        <button type="button" class="text-slate-400 hover:text-indigo-600 transition-colors font-medium"
                                            @click="selectAllInModule(selectedSpatieRole!.id, perms as string[])">All</button>
                                        <span class="text-slate-200">|</span>
                                        <button type="button" class="text-slate-400 hover:text-red-500 transition-colors font-medium"
                                            @click="clearAllInModule(selectedSpatieRole!.id, perms as string[])">None</button>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <label v-for="perm in (perms as string[])" :key="perm"
                                        :class="[
                                            'flex items-center gap-2 cursor-pointer rounded-lg border px-3 py-2 text-xs font-medium transition-all select-none',
                                            hasPermission(selectedSpatieRole!.id, perm)
                                                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 shadow-sm'
                                                : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50',
                                        ]">
                                        <input type="checkbox" class="sr-only"
                                            :checked="hasPermission(selectedSpatieRole!.id, perm)"
                                            @change="togglePermission(selectedSpatieRole!.id, perm)" />
                                        <CheckCircle2 v-if="hasPermission(selectedSpatieRole!.id, perm)"
                                            class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                        <div v-else class="w-3.5 h-3.5 rounded-full border-2 border-slate-300 shrink-0"></div>
                                        {{ permLabel(perm) }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div v-else class="flex items-center justify-center h-64 text-sm text-slate-400">
                        Select a role on the left to manage its permissions.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
