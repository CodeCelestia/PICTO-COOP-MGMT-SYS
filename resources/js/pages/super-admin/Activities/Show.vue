<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { CalendarDays, Pencil, Trash2, ArrowLeft, MapPin, Clock, Building2, Banknote } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalConfirmDelete } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { destroy as activitiesDestroy, index as activitiesIndex, edit as activitiesEdit } from "@/routes/super-admin/activities";

type Activity = { id: number; title: string; type: string; status: string; activity_date: string | null; start_time: string | null; end_time: string | null; venue: string | null; budget: string | null; description: string | null; office: { id: number; name: string } | null };
const props = defineProps<{ activity: Activity }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Activities", href: "/super-admin/activities" }, { title: props.activity.title, href: "#" }];
const statusBadge: Record<string, string> = { planned: "bg-blue-50 text-blue-700 ring-blue-200", ongoing: "bg-amber-50 text-amber-700 ring-amber-200", completed: "bg-emerald-50 text-emerald-700 ring-emerald-200", cancelled: "bg-red-50 text-red-700 ring-red-200" };
const fmtDate = (d: string | null) => d ? new Date(d).toLocaleDateString("en-PH", { year: "numeric", month: "long", day: "numeric" }) : "TBD";
const fmtCurrency = (v: string | null) => v ? `₱${parseFloat(v).toLocaleString("en-PH", { minimumFractionDigits: 2 })}` : "—";
const del = async () => { const r = await swalConfirmDelete(props.activity.title); if (r.isConfirmed) router.delete(activitiesDestroy(props.activity.id).url); };
</script>
<template>
    <Head :title="activity.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-rose-600 to-pink-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><CalendarDays class="h-5 w-5" /></div>
                    <div class="flex-1">
                        <h1 class="text-xl font-bold">{{ activity.title }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize bg-white/20 text-white ring-white/30">{{ activity.status }}</span>
                            <span class="text-sm text-rose-200 capitalize">{{ activity.type?.replace("_", " ") }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="activitiesIndex().url"><Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button></Link>
                        <Link :href="activitiesEdit(activity.id).url"><Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><Pencil class="h-4 w-4" /> Edit</Button></Link>
                        <Button variant="ghost" class="border border-red-300/50 text-red-100 hover:bg-red-700/40 gap-2" @click="del"><Trash2 class="h-4 w-4" /> Delete</Button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-4">Activity Details</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 flex items-center gap-3">
                            <CalendarDays class="h-4 w-4 text-rose-500 shrink-0" />
                            <div><p class="text-xs text-slate-500">Date</p><p class="text-sm font-semibold text-slate-800">{{ fmtDate(activity.activity_date) }}</p></div>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 flex items-center gap-3">
                            <Clock class="h-4 w-4 text-rose-500 shrink-0" />
                            <div><p class="text-xs text-slate-500">Time</p><p class="text-sm font-semibold text-slate-800">{{ activity.start_time ?? "—" }}{{ activity.end_time ? " – " + activity.end_time : "" }}</p></div>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 flex items-center gap-3">
                            <MapPin class="h-4 w-4 text-rose-500 shrink-0" />
                            <div><p class="text-xs text-slate-500">Venue</p><p class="text-sm font-semibold text-slate-800">{{ activity.venue ?? "—" }}</p></div>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-3 flex items-center gap-3">
                            <Building2 class="h-4 w-4 text-rose-500 shrink-0" />
                            <div><p class="text-xs text-slate-500">Office</p><p class="text-sm font-semibold text-slate-800">{{ activity.office?.name ?? "—" }}</p></div>
                        </div>
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 flex items-center gap-3 sm:col-span-2">
                            <Banknote class="h-4 w-4 text-emerald-600 shrink-0" />
                            <div><p class="text-xs text-emerald-700">Budget</p><p class="text-lg font-bold text-emerald-700">{{ fmtCurrency(activity.budget) }}</p></div>
                        </div>
                    </div>
                    <div v-if="activity.description" class="mt-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-rose-600 mb-2">Description</p>
                        <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-line">{{ activity.description }}</p>
                    </div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6 flex flex-col gap-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600">Status</p>
                    <span class="inline-flex self-start items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset capitalize" :class="statusBadge[activity.status] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ activity.status }}</span>
                    <p class="text-xs font-bold uppercase tracking-wider text-rose-600 mt-2">Type</p>
                    <p class="text-sm font-semibold text-slate-800 capitalize">{{ activity.type?.replace("_", " ") }}</p>
                    <div class="mt-auto pt-4 border-t border-slate-100 flex flex-col gap-2">
                        <Link :href="activitiesEdit(activity.id).url" class="w-full"><Button class="w-full bg-amber-500 hover:bg-amber-600 text-white gap-2"><Pencil class="h-4 w-4" /> Edit Activity</Button></Link>
                        <Button variant="outline" class="w-full border-red-200 text-red-600 hover:bg-red-50 gap-2" @click="del"><Trash2 class="h-4 w-4" /> Delete</Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
