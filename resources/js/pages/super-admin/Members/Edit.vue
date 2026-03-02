<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, Users } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalError } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { update as membersUpdate } from "@/routes/super-admin/members";

type Member = { id: number; office_id: number | null; membership_number: string; membership_type: string; membership_status: string; date_joined: string | null; share_capital: number | null; savings_balance: number | null; loan_balance: number | null; pds?: { first_name?: string; last_name?: string } };
type Office = { id: number; name: string };
const props = defineProps<{ member: Member; offices: Office[] }>();

const memberName = props.member.pds ? `${props.member.pds.first_name} ${props.member.pds.last_name}` : `Member #${props.member.id}`;
const breadcrumbs: BreadcrumbItem[] = [
    { title: "Members", href: "/super-admin/members" },
    { title: memberName, href: `/super-admin/members/${props.member.id}` },
    { title: "Edit", href: "" },
];

const form = useForm({
    office_id: props.member.office_id ? String(props.member.office_id) : "",
    membership_type: props.member.membership_type,
    membership_status: props.member.membership_status,
    date_joined: props.member.date_joined ?? "",
    share_capital: props.member.share_capital ?? "",
    savings_balance: props.member.savings_balance ?? "",
    loan_balance: props.member.loan_balance ?? "",
});

const submit = () => {
    form.patch(membersUpdate(props.member.id).url, {
        onSuccess: () => swalSuccess("Member Updated!", "Changes have been saved."),
        onError: () => swalError("Validation Error", "Please check the fields."),
    });
};
</script>

<template>
    <Head :title="`Edit — ${memberName}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-3xl mx-auto w-full">
            <div class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Users class="h-5 w-5" /></div>
                        <div><h1 class="text-xl font-bold">Edit Member</h1><p class="text-sm text-amber-100">{{ memberName }}</p></div>
                    </div>
                    <Link :href="`/super-admin/members/${member.id}`">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Membership Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Office</label>
                            <Select :modelValue="form.office_id" @update:modelValue="form.office_id = $event">
                                <SelectTrigger><SelectValue placeholder="Select office" /></SelectTrigger>
                                <SelectContent><SelectItem v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</SelectItem></SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Type</label>
                            <Select :modelValue="form.membership_type" @update:modelValue="form.membership_type = $event">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="regular">Regular</SelectItem>
                                    <SelectItem value="associate">Associate</SelectItem>
                                    <SelectItem value="provisional">Provisional</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Status</label>
                            <Select :modelValue="form.membership_status" @update:modelValue="form.membership_status = $event">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="resigned">Resigned</SelectItem>
                                    <SelectItem value="expelled">Expelled</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Date Joined</label><Input type="date" v-model="form.date_joined" /></div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Financial</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Share Capital (&#8369;)</label><Input type="number" step="0.01" v-model="form.share_capital" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Savings Balance (&#8369;)</label><Input type="number" step="0.01" v-model="form.savings_balance" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Loan Balance (&#8369;)</label><Input type="number" step="0.01" v-model="form.loan_balance" /></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="`/super-admin/members/${member.id}`"><Button type="button" variant="outline">Cancel</Button></Link>
                    <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />{{ form.processing ? "Saving..." : "Save Changes" }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
