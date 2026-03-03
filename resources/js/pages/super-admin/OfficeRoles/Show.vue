<script setup lang="ts">
import { Head, router } from "@inertiajs/vue3";
import { Crown, Briefcase, Users, UserCheck, ClipboardList, UserCog, ArrowLeft, Edit } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type OfficeRole = {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    is_system: boolean;
};

type Assignment = {
    office_id: number;
    office_name: string;
    office_code: string;
    user_id: number;
    user_name: string;
    user_email: string;
    assigned_at: string;
};

const props = defineProps<{
    role: OfficeRole;
    assignments: Assignment[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Users & Roles", href: "/super-admin/users" },
    { title: "Office Roles", href: "/super-admin/office-roles" },
    { title: props.role.display_name, href: "#" },
];

// Icon mapping
const ROLE_ICONS: Record<string, any> = {
    chairperson: Crown,
    general_manager: Briefcase,
    officer: UserCheck,
    committee_member: ClipboardList,
    member: Users,
};

const ROLE_COLORS: Record<string, string> = {
    chairperson: "text-violet-700 bg-violet-50 border-violet-200",
    general_manager: "text-blue-700 bg-blue-50 border-blue-200",
    officer: "text-emerald-700 bg-emerald-50 border-emerald-200",
    committee_member: "text-amber-700 bg-amber-50 border-amber-200",
    member: "text-slate-600 bg-slate-50 border-slate-200",
};

const roleIcon = ROLE_ICONS[props.role.name] || UserCog;
const roleColor = ROLE_COLORS[props.role.name] || "text-indigo-700 bg-indigo-50 border-indigo-200";
const [textColor, bgColor, borderColor] = roleColor.split(' ');
</script>

<template>
    <Head :title="role.display_name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <!-- Header -->
            <div :class="['rounded-2xl px-6 py-5 border-2 shadow-sm', bgColor, borderColor]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div :class="['flex h-12 w-12 items-center justify-center rounded-xl border-2', borderColor]">
                            <component :is="roleIcon" :class="['h-6 w-6', textColor]" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 :class="['text-2xl font-bold', textColor]">{{ role.display_name }}</h1>
                                <span v-if="role.is_system" class="px-2 py-0.5 text-xs font-semibold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                    System Role
                                </span>
                            </div>
                            <p class="text-sm text-slate-500 mt-1">
                                {{ role.description || 'No description provided' }}
                            </p>
                            <p class="text-xs text-slate-400 mt-1 font-mono">
                                Role Code: {{ role.name }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button 
                            @click="router.visit('/super-admin/users')" 
                            variant="outline"
                            class="flex items-center gap-2">
                            <ArrowLeft class="w-4 h-4" />
                            Back to Users & Roles
                        </Button>
                        <Button 
                            @click="router.visit(`/super-admin/office-roles/${role.id}/edit`)" 
                            class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <Edit class="w-4 h-4" />
                            Edit Role
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500 font-medium">Total Assignments</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">{{ assignments.length }}</p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500 font-medium">Unique Offices</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">
                        {{ new Set(assignments.map(a => a.office_id)).size }}
                    </p>
                </div>
                <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500 font-medium">Unique Users</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">
                        {{ new Set(assignments.map(a => a.user_id)).size }}
                    </p>
                </div>
            </div>

            <!-- Assignments Table -->
            <div class="rounded-2xl border bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b bg-slate-50">
                    <h2 class="text-lg font-bold text-slate-800">Role Assignments</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        People assigned to this role across all offices
                    </p>
                </div>

                <div v-if="assignments.length > 0" class="overflow-x-auto">
                    <table class="w-full min-w-150 text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/70">
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Office / Establishment
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Assigned Person
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Email
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Date Assigned
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="entry in assignments" 
                                :key="`${entry.office_id}-${entry.user_id}`"
                                class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-slate-800">{{ entry.office_name }}</span>
                                        <span v-if="entry.office_code" 
                                            class="text-xs text-slate-400 font-mono bg-slate-100 px-1.5 py-0.5 rounded">
                                            {{ entry.office_code }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                            {{ entry.user_name.split(" ").map((p: string) => p[0]).slice(0, 2).join("").toUpperCase() }}
                                        </div>
                                        <span class="font-medium text-slate-700">{{ entry.user_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-500 text-xs">
                                    {{ entry.user_email }}
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs whitespace-nowrap">
                                    {{ entry.assigned_at
                                        ? new Date(entry.assigned_at).toLocaleDateString("en-PH", { 
                                            year: "numeric", 
                                            month: "short", 
                                            day: "numeric" 
                                        })
                                        : "—" 
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="px-6 py-16 text-center">
                    <div class="flex justify-center mb-4">
                        <div :class="['flex h-16 w-16 items-center justify-center rounded-2xl border-2', borderColor, bgColor]">
                            <component :is="roleIcon" :class="['h-8 w-8', textColor]" />
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-700">No Assignments Yet</h3>
                    <p class="text-sm text-slate-500 mt-1">
                        No one has been assigned the <strong>{{ role.display_name }}</strong> role yet.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
