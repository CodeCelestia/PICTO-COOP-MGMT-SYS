<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import { Plus, Pencil, Trash2, Eye, Users } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalConfirmDelete, swalSuccess } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { destroy as membersDestroy, create as membersCreate, show as membersShow, edit as membersEdit } from "@/routes/super-admin/members";

type Member = { id: number; membership_number: string; membership_type: string; membership_status: string; date_joined: string | null; pds: { first_name: string; last_name: string } | null; office: { name: string } | null };
type Paginator = { data: Member[]; current_page: number; last_page: number; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] };
type Office = { id: number; name: string };

const props = defineProps<{ members: Paginator; offices: Office[]; filters?: { search?: string; office_id?: string; status?: string; type?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Dashboard", href: "/super-admin/dashboard" },
    { title: "Members", href: "/super-admin/members" },
];

const page = usePage<{ flash?: { success?: string } }>();
onMounted(() => { if (page.props.flash?.success) swalSuccess(page.props.flash.success); });
watch(() => page.props.flash?.success, (v) => { if (v) swalSuccess(v); });

const search = ref(props.filters?.search ?? "");
const officeId = ref(props.filters?.office_id ?? "");
const status = ref(props.filters?.status ?? "");
const type = ref(props.filters?.type ?? "");

const applyFilters = () => {
    router.get("/super-admin/members", { search: search.value, office_id: officeId.value, status: status.value, type: type.value }, { preserveState: true, replace: true });
};

const deleteMember = async (id: number, name: string) => {
    const result = await swalConfirmDelete(name);
    if (result.isConfirmed) router.delete(membersDestroy(id).url, { preserveScroll: true });
};

const statusBadge: Record<string, string> = {
    active: "bg-emerald-50 text-emerald-700 ring-emerald-200",
    inactive: "bg-slate-50 text-slate-600 ring-slate-200",
    resigned: "bg-red-50 text-red-700 ring-red-200",
    expelled: "bg-red-50 text-red-700 ring-red-200",
};
const typeBadge: Record<string, string> = {
    regular: "bg-blue-50 text-blue-700 ring-blue-200",
    associate: "bg-purple-50 text-purple-700 ring-purple-200",
    provisional: "bg-orange-50 text-orange-700 ring-orange-200",
};

const fullName = (m: Member) => m.pds ? `${m.pds.first_name} ${m.pds.last_name}` : "—";
const initials = (m: Member) => m.pds ? `${m.pds.first_name.charAt(0)}${m.pds.last_name.charAt(0)}` : "?";
</script>

<template>
    <Head title="Members" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Users class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Members</h1>
                            <p class="text-sm text-emerald-100">{{ members.total }} cooperative members enrolled</p>
                        </div>
                    </div>
                    <Link :href="membersCreate().url">
                        <Button class="bg-white text-emerald-700 hover:bg-emerald-50 gap-2 font-semibold shadow">
                            <Plus class="h-4 w-4" /> New Member
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <Input v-model="search" placeholder="Search by name or number..." @input="applyFilters" />
                    <Select :modelValue="officeId || '_all'" @update:modelValue="v => { officeId = v === '_all' ? '' : v; applyFilters(); }">
                        <SelectTrigger><SelectValue placeholder="All Offices" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">All Offices</SelectItem>
                            <SelectItem v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select :modelValue="status || '_all'" @update:modelValue="v => { status = v === '_all' ? '' : v; applyFilters(); }">
                        <SelectTrigger><SelectValue placeholder="All Statuses" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">All Statuses</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="resigned">Resigned</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select :modelValue="type || '_all'" @update:modelValue="v => { type = v === '_all' ? '' : v; applyFilters(); }">
                        <SelectTrigger><SelectValue placeholder="All Types" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="_all">All Types</SelectItem>
                            <SelectItem value="regular">Regular</SelectItem>
                            <SelectItem value="associate">Associate</SelectItem>
                            <SelectItem value="provisional">Provisional</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Member</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Number</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Office</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="m in members.data" :key="m.id" class="hover:bg-emerald-50/30 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ initials(m) }}</div>
                                    <span class="text-sm font-semibold text-slate-900">{{ fullName(m) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3"><span class="font-mono text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md">{{ m.membership_number }}</span></td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ m.office?.name ?? "—" }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="typeBadge[m.membership_type] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ m.membership_type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="statusBadge[m.membership_status] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ m.membership_status }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="membersShow(m.id).url">
                                        <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-indigo-700 hover:bg-indigo-50"><Eye class="h-3.5 w-3.5" /> View</Button>
                                    </Link>
                                    <Link :href="membersEdit(m.id).url">
                                        <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-amber-700 hover:bg-amber-50"><Pencil class="h-3.5 w-3.5" /> Edit</Button>
                                    </Link>
                                    <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-red-700 hover:bg-red-50" @click="deleteMember(m.id, fullName(m))">
                                        <Trash2 class="h-3.5 w-3.5" /> Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!members.data.length">
                            <td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No members found.</td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="members.last_page > 1" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Showing {{ members.from }}–{{ members.to }} of {{ members.total }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in members.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium transition-colors" :class="link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-slate-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
