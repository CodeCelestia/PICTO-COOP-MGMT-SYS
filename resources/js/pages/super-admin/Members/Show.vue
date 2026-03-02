<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Pencil, Users } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalConfirmDelete } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { destroy as membersDestroy, edit as membersEdit } from "@/routes/super-admin/members";

type Member = { id: number; membership_number: string; membership_type: string; membership_status: string; date_joined: string | null; share_capital: number | null; savings_balance: number | null; loan_balance: number | null; capital_contribution: number | null; occupation: string | null; employer: string | null; monthly_income: number | null; emergency_contact_name: string | null; emergency_contact_relationship: string | null; emergency_contact_phone: string | null; notes: string | null; pds: { first_name: string; last_name: string; email: string | null; phone_number: string | null; date_of_birth: string | null; place_of_birth: string | null; civil_status: string | null } | null; office: { id: number; name: string } | null };
const props = defineProps<{ member: Member }>();
const name = props.member.pds ? `${props.member.pds.first_name} ${props.member.pds.last_name}` : `Member #${props.member.id}`;
const breadcrumbs: BreadcrumbItem[] = [{ title: "Members", href: "/super-admin/members" }, { title: name, href: "" }];
const statusBadge: Record<string, string> = { active: "bg-emerald-50 text-emerald-700 ring-emerald-200", inactive: "bg-slate-50 text-slate-600 ring-slate-200", resigned: "bg-red-50 text-red-700 ring-red-200", expelled: "bg-red-50 text-red-700 ring-red-200" };
const deleteMember = async () => {
    const r = await swalConfirmDelete(name);
    if (r.isConfirmed) router.delete(membersDestroy(props.member.id).url);
};
const fmt = (n: number | null) => n != null ? `₱${Number(n).toLocaleString("en-PH", { minimumFractionDigits: 2 })}` : "—";
</script>

<template>
    <Head :title="name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <div class="rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-xl font-black">{{ name.charAt(0) }}</div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ name }}</h1>
                            <p class="mt-0.5 font-mono text-sm text-emerald-100">{{ member.membership_number }}</p>
                            <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize bg-white/20 text-white ring-white/30">{{ member.membership_status }}</span>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="membersEdit(member.id).url">
                            <Button class="bg-white/20 hover:bg-white/30 text-white border border-white/30 gap-2"><Pencil class="h-4 w-4" /> Edit</Button>
                        </Link>
                        <Button variant="ghost" class="border border-red-300/50 text-red-100 hover:bg-red-500/30 gap-2" @click="deleteMember">Delete</Button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2 space-y-5">
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Personal Information</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Email</p><p class="text-sm font-semibold text-slate-800">{{ member.pds?.email ?? "—" }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Phone</p><p class="text-sm font-semibold text-slate-800">{{ member.pds?.phone_number ?? "—" }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Date of Birth</p><p class="text-sm font-semibold text-slate-800">{{ member.pds?.date_of_birth ?? "—" }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Civil Status</p><p class="text-sm font-semibold text-slate-800 capitalize">{{ member.pds?.civil_status ?? "—" }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Occupation</p><p class="text-sm font-semibold text-slate-800">{{ member.occupation ?? "—" }}</p></div>
                            <div class="rounded-lg bg-slate-50 p-3"><p class="text-xs text-slate-500">Employer</p><p class="text-sm font-semibold text-slate-800">{{ member.employer ?? "—" }}</p></div>
                        </div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Financial Summary</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-center"><p class="text-xs text-emerald-600 font-medium">Share Capital</p><p class="text-lg font-black text-emerald-800 mt-0.5">{{ fmt(member.share_capital) }}</p></div>
                            <div class="rounded-lg bg-blue-50 border border-blue-200 p-3 text-center"><p class="text-xs text-blue-600 font-medium">Savings</p><p class="text-lg font-black text-blue-800 mt-0.5">{{ fmt(member.savings_balance) }}</p></div>
                            <div class="rounded-lg bg-red-50 border border-red-200 p-3 text-center"><p class="text-xs text-red-600 font-medium">Loan Balance</p><p class="text-lg font-black text-red-800 mt-0.5">{{ fmt(member.loan_balance) }}</p></div>
                        </div>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">Membership</p>
                        <div class="space-y-2.5">
                            <div><p class="text-xs text-slate-500">Number</p><p class="font-mono text-sm font-semibold text-slate-800">{{ member.membership_number }}</p></div>
                            <div><p class="text-xs text-slate-500">Type</p><p class="text-sm font-semibold text-slate-800 capitalize">{{ member.membership_type }}</p></div>
                            <div><p class="text-xs text-slate-500">Status</p><span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset capitalize" :class="statusBadge[member.membership_status] || 'bg-slate-50 text-slate-600 ring-slate-200'">{{ member.membership_status }}</span></div>
                            <div><p class="text-xs text-slate-500">Date Joined</p><p class="text-sm font-semibold text-slate-800">{{ member.date_joined ?? "—" }}</p></div>
                            <div><p class="text-xs text-slate-500">Office</p><p class="text-sm font-semibold text-slate-800">{{ member.office?.name ?? "—" }}</p></div>
                        </div>
                    </div>
                    <div v-if="member.emergency_contact_name" class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-3">Emergency Contact</p>
                        <p class="text-sm font-semibold text-slate-800">{{ member.emergency_contact_name }}</p>
                        <p class="text-xs text-slate-500 capitalize">{{ member.emergency_contact_relationship }}</p>
                        <p class="text-sm text-slate-700 mt-1">{{ member.emergency_contact_phone }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
