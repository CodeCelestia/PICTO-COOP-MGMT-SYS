<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ClipboardList } from "lucide-vue-next";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";

type LogItem = { id: number; description: string; event: string | null; log_name: string | null; subject_type: string | null; subject_id: number | null; causer_name: string | null; causer_email: string | null; properties: Record<string, unknown> | null; created_at: string };
type PaginationLink = { url: string | null; label: string; active: boolean };
type Paginator = { data: LogItem[]; links: PaginationLink[]; from: number | null; to: number | null; total: number; current_page: number; last_page: number };
const props = defineProps<{ logs: Paginator }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Audit Logs", href: "/super-admin/logs" }];

const eventBadge: Record<string, string> = { created: "bg-emerald-50 text-emerald-700 ring-emerald-200", updated: "bg-amber-50 text-amber-700 ring-amber-200", deleted: "bg-red-50 text-red-700 ring-red-200", restored: "bg-blue-50 text-blue-700 ring-blue-200" };
const fmtDate = (d: string) => new Date(d).toLocaleString("en-PH", { year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit" });
const modelName = (t: string | null) => t ? t.split("\\").pop() ?? t : "—";
</script>
<template>
    <Head title="Audit Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10"><ClipboardList class="h-5 w-5" /></div>
                    <div><h1 class="text-xl font-bold">Audit Logs</h1><p class="text-sm text-slate-400">{{ logs.total }} entries</p></div>
                </div>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Model</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Performed By</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="eventBadge[log.event ?? ''] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ log.event ?? log.log_name ?? "—" }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" :title="log.description">{{ log.description }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                <span class="font-medium text-slate-700">{{ modelName(log.subject_type) }}</span>
                                <span v-if="log.subject_id" class="text-slate-400"> #{{ log.subject_id }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="log.causer_name"><p class="text-sm font-medium text-slate-800">{{ log.causer_name }}</p><p class="text-xs text-slate-500">{{ log.causer_email }}</p></div>
                                <span v-else class="text-xs text-slate-400">System</span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">{{ fmtDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.data.length"><td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">No log entries found.</td></tr>
                    </tbody>
                </table>
                <div v-if="logs.last_page > 1" class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">Showing {{ logs.from }}–{{ logs.to }} of {{ logs.total }}</p>
                    <div class="flex gap-1 flex-wrap">
                        <template v-for="link in logs.links" :key="link.label">
                            <Link v-if="link.url" :href="link.url" class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium transition-colors" :class="link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-100'" v-html="link.label" />
                            <span v-else class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-slate-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
