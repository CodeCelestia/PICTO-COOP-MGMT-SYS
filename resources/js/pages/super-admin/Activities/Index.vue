<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { onMounted, watch } from "vue";
import { Plus, Pencil, Trash2, Eye, CalendarDays } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalConfirmDelete, swalSuccess } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { destroy as activitiesDestroy, create as activitiesCreate, show as activitiesShow, edit as activitiesEdit } from "@/routes/super-admin/activities";

type Activity = { id: number; title: string; type: string; status: string; activity_date: string | null; venue: string | null; office: { name: string } | null };
type Paginator = { data: Activity[]; current_page: number; last_page: number; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] };
const props = defineProps<{ activities: Paginator }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Activities", href: "/super-admin/activities" }];
const page = usePage<{ flash?: { success?: string } }>();
onMounted(() => { if (page.props.flash?.success) swalSuccess(page.props.flash.success); });
watch(() => page.props.flash?.success, (v) => { if (v) swalSuccess(v); });
const statusBadge: Record<string, string> = { planned: "bg-blue-50 text-blue-700 ring-blue-200", ongoing: "bg-amber-50 text-amber-700 ring-amber-200", completed: "bg-emerald-50 text-emerald-700 ring-emerald-200", cancelled: "bg-red-50 text-red-700 ring-red-200" };
const del = async (id: number, title: string) => { const r = await swalConfirmDelete(title); if (r.isConfirmed) router.delete(activitiesDestroy(id).url, { preserveScroll: true }); };
const fmtDate = (d: string | null) => d ? new Date(d).toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "numeric" }) : "TBD";
</script>
<template>
    <Head title="Activities" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><CalendarDays class="h-5 w-5" /></div><div><h1 class="text-xl font-bold">Activities</h1><p class="text-sm text-rose-200">{{ activities.total }} events recorded</p></div></div>
                    <Link :href="activitiesCreate().url"><Button class="bg-white text-rose-700 hover:bg-rose-50 gap-2 font-semibold shadow"><Plus class="h-4 w-4" /> New Activity</Button></Link>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Activity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Office</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="a in activities.data" :key="a.id" class="hover:bg-rose-50/30 transition-colors">
                            <td class="px-4 py-3"><div class="flex items-center gap-3"><div class="h-9 w-9 rounded-xl bg-rose-600 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ a.title.charAt(0) }}</div><span class="text-sm font-semibold text-slate-900">{{ a.title }}</span></div></td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ fmtDate(a.activity_date) }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600 capitalize">{{ a.type?.replace("_", " ") ?? "—" }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ a.office?.name ?? "—" }}</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="statusBadge[a.status] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ a.status }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="activitiesShow(a.id).url"><Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-indigo-700 hover:bg-indigo-50"><Eye class="h-3.5 w-3.5" /> View</Button></Link>
                                    <Link :href="activitiesEdit(a.id).url"><Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-amber-700 hover:bg-amber-50"><Pencil class="h-3.5 w-3.5" /> Edit</Button></Link>
                                    <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-red-700 hover:bg-red-50" @click="del(a.id, a.title)"><Trash2 class="h-3.5 w-3.5" /> Delete</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!activities.data.length"><td colspan="6" class="px-4 py-16 text-center text-sm text-slate-500">No activities found.</td></tr>
                    </tbody>
                </table>
                <div v-if="activities.last_page > 1" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Showing {{ activities.from }}–{{ activities.to }} of {{ activities.total }}</p>
                    <div class="flex gap-1">
                        <template v-for="link in activities.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium transition-colors" :class="link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-slate-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
