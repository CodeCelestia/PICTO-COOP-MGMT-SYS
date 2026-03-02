<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Pencil, Users2 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalConfirmDelete } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { destroy as committeesDestroy, edit as committeesEdit } from "@/routes/super-admin/committees";

type CommitteeItem = { id: number; name: string; code: string; type: string; status: string; description: string | null; office: { name: string } | null; members?: { id: number; pds: { first_name: string; last_name: string } | null }[] };
const props = defineProps<{ committee: CommitteeItem }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Committees", href: "/super-admin/committees" }, { title: props.committee.name, href: "" }];
const statusBadge: Record<string, string> = { active: "bg-emerald-50 text-emerald-700 ring-emerald-200", inactive: "bg-slate-50 text-slate-600 ring-slate-200" };
const del = async () => { const r = await swalConfirmDelete(props.committee.name); if (r.isConfirmed) router.delete(committeesDestroy(props.committee.id).url); };
</script>
<template>
    <Head :title="committee.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-purple-600 to-violet-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-xl font-black">{{ committee.name.charAt(0) }}</div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ committee.name }}</h1>
                            <p class="font-mono text-sm text-purple-200">{{ committee.code }}</p>
                            <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold capitalize bg-white/20 text-white ring-1 ring-inset ring-white/30">{{ committee.status }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="committeesEdit(committee.id).url"><Button class="bg-white/20 hover:bg-white/30 text-white border border-white/30 gap-2"><Pencil class="h-4 w-4" /> Edit</Button></Link>
                        <Button variant="ghost" class="border border-red-300/50 text-red-100 hover:bg-red-500/30 gap-2" @click="del">Delete</Button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Details</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Code</p><p class="font-mono text-sm font-semibold text-slate-800">{{ committee.code }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Type</p><p class="text-sm font-semibold text-slate-800 capitalize">{{ committee.type.replace("_", " ") }}</p></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Status</p><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="statusBadge[committee.status] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ committee.status }}</span></div>
                        <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Office</p><p class="text-sm font-semibold text-slate-800">{{ committee.office?.name ?? "—" }}</p></div>
                    </div>
                    <div v-if="committee.description" class="mt-4 rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500 mb-1">Description</p><p class="text-sm text-slate-700">{{ committee.description }}</p></div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Members ({{ committee.members?.length ?? 0 }})</p>
                    <div class="space-y-2">
                        <div v-for="m in (committee.members ?? []).slice(0, 6)" :key="m.id" class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ m.pds ? m.pds.first_name.charAt(0) : "?" }}</div>
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ m.pds ? `${m.pds.first_name} ${m.pds.last_name}` : `Member #${m.id}` }}</p>
                        </div>
                        <p v-if="!committee.members?.length" class="text-xs text-slate-400 italic">No members yet</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
