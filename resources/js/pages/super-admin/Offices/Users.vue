<script setup lang="ts">
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { ArrowLeft, UserPlus, Check, X, Trash2, Users } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalConfirmDelete } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { assign as officesUsersAssign, updateRole as officesUsersUpdateRole, remove as officesUsersRemove } from "@/routes/super-admin/offices/users";

type OfficeUser = { id: number; name: string; email: string; pivot: { role_name: string; assigned_at: string } };
type Office = { id: number; name: string; code: string };

const props = defineProps<{ office: Office; officeUsers: OfficeUser[]; roles: string[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Offices", href: "/super-admin/offices" },
    { title: props.office.name, href: `/super-admin/offices/${props.office.id}` },
    { title: "Users", href: "" },
];

const assignForm = useForm({ user_id: "", role_name: "" });
const assignUser = () => {
    assignForm.post(officesUsersAssign(props.office.id).url, {
        onSuccess: () => { swalSuccess("User Assigned!", "The user has been added to this office."); assignForm.reset(); },
    });
};

const editingId = ref<number | null>(null);
const editRole = ref("");
const startEdit = (u: OfficeUser) => { editingId.value = u.id; editRole.value = u.pivot.role_name; };
const saveRole = (userId: number) => {
    router.patch(officesUsersUpdateRole([props.office.id, userId]).url, { role_name: editRole.value }, {
        preserveScroll: true,
        onSuccess: () => { swalSuccess("Role Updated!"); editingId.value = null; },
    });
};

const removeUser = async (u: OfficeUser) => {
    const result = await swalConfirmDelete(u.name);
    if (result.isConfirmed) router.delete(officesUsersRemove([props.office.id, u.id]).url, { preserveScroll: true });
};

const roleBadge: Record<string, string> = {
    admin: "bg-indigo-50 text-indigo-700 ring-indigo-200",
    member: "bg-emerald-50 text-emerald-700 ring-emerald-200",
    staff: "bg-amber-50 text-amber-700 ring-amber-200",
};
</script>

<template>
    <Head :title="`${office.name} Users`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Users class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Office Users</h1>
                            <p class="text-sm text-teal-100">{{ office.name }} &mdash; {{ officeUsers.length }} assigned</p>
                        </div>
                    </div>
                    <Link :href="`/super-admin/offices/${office.id}`">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <ArrowLeft class="h-4 w-4" /> Back
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Assign User to Office</p>
                <form @submit.prevent="assignUser" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">User ID</label>
                        <Input v-model="assignForm.user_id" placeholder="Enter user ID" required />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-semibold text-slate-700">Role</label>
                        <Select :modelValue="assignForm.role_name" @update:modelValue="assignForm.role_name = $event">
                            <SelectTrigger><SelectValue placeholder="Select role" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="r in roles" :key="r" :value="r">{{ r }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" :disabled="assignForm.processing" class="gap-2 bg-teal-600 hover:bg-teal-700 text-white h-10">
                        <UserPlus class="h-4 w-4" /> Assign
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-[600px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Assigned</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in officeUsers" :key="u.id" class="hover:bg-indigo-50/40 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ u.name.charAt(0) }}</div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ u.name }}</p>
                                        <p class="text-xs text-slate-500">{{ u.email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="editingId === u.id" class="flex items-center gap-2">
                                    <Select :modelValue="editRole" @update:modelValue="editRole = $event">
                                        <SelectTrigger class="h-8 w-32"><SelectValue /></SelectTrigger>
                                        <SelectContent><SelectItem v-for="r in roles" :key="r" :value="r">{{ r }}</SelectItem></SelectContent>
                                    </Select>
                                    <Button size="sm" variant="ghost" class="h-7 px-1.5 hover:text-emerald-600" @click="saveRole(u.id)"><Check class="h-3.5 w-3.5" /></Button>
                                    <Button size="sm" variant="ghost" class="h-7 px-1.5 hover:text-red-600" @click="editingId = null"><X class="h-3.5 w-3.5" /></Button>
                                </div>
                                <span v-else class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize cursor-pointer hover:opacity-80" :class="roleBadge[u.pivot.role_name] || 'bg-slate-50 text-slate-600 ring-slate-200'" @click="startEdit(u)">{{ u.pivot.role_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ u.pivot.assigned_at ? new Date(u.pivot.assigned_at).toLocaleDateString() : "—" }}</td>
                            <td class="px-4 py-3 text-right">
                                <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-red-700 hover:bg-red-50" @click="removeUser(u)">
                                    <Trash2 class="h-3.5 w-3.5" /> Remove
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!officeUsers.length">
                            <td colspan="4" class="px-4 py-12 text-center text-sm text-slate-500">No users assigned yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
