<script setup lang="ts">
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { ArrowLeft, Save, Users } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalError } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { store as membersStore } from "@/routes/super-admin/members";

type PDS = { id: number; first_name: string; last_name: string; email: string | null };
type Office = { id: number; name: string };
const props = defineProps<{ pds_list: PDS[]; offices: Office[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "Members", href: "/super-admin/members" },
    { title: "New Member", href: "" },
];

const form = useForm({
    pds_id: "", office_id: "", membership_number: "", membership_type: "regular",
    membership_status: "active", date_joined: "", capital_contribution: "",
    occupation: "", employer: "", monthly_income: "",
    emergency_contact_name: "", emergency_contact_relationship: "", emergency_contact_phone: "",
    notes: "",
});

const selectedPds = ref<PDS | null>(null);
watch(() => form.pds_id, (id) => {
    selectedPds.value = props.pds_list.find(p => String(p.id) === String(id)) ?? null;
});

const submit = () => {
    form.post(membersStore().url, {
        onSuccess: () => swalSuccess("Member Created!", "New member has been enrolled successfully."),
        onError: () => swalError("Validation Error", "Please check the fields."),
    });
};
</script>

<template>
    <Head title="New Member" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-4xl mx-auto w-full">
            <div class="rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Users class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Enroll New Member</h1>
                            <p class="text-sm text-emerald-100">Link a PDS record to create a cooperative member</p>
                        </div>
                    </div>
                    <Link href="/super-admin/members">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- PDS Selection -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Link Personal Data Sheet</p>
                    <Select :modelValue="form.pds_id" @update:modelValue="form.pds_id = $event">
                        <SelectTrigger :class="{ 'border-red-400': form.errors.pds_id }"><SelectValue placeholder="Select a PDS record..." /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="p in pds_list" :key="p.id" :value="String(p.id)">{{ p.first_name }} {{ p.last_name }} {{ p.email ? `(${p.email})` : "" }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <span v-if="form.errors.pds_id" class="text-xs text-red-600 mt-1 block">{{ form.errors.pds_id }}</span>
                    <div v-if="selectedPds" class="mt-3 rounded-lg bg-emerald-50 border border-emerald-200 p-3 flex items-center gap-3">
                        <div class="h-9 w-9 rounded-full bg-emerald-600 text-white flex items-center justify-center text-sm font-bold">{{ selectedPds.first_name.charAt(0) }}{{ selectedPds.last_name.charAt(0) }}</div>
                        <div>
                            <p class="text-sm font-semibold text-emerald-900">{{ selectedPds.first_name }} {{ selectedPds.last_name }}</p>
                            <p class="text-xs text-emerald-700">{{ selectedPds.email ?? "No email" }}</p>
                        </div>
                    </div>
                </div>

                <!-- Membership Info -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Membership Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Membership Number <span class="text-red-500">*</span></label>
                            <Input v-model="form.membership_number" required placeholder="e.g., COOP-2024-001" :class="{ 'border-red-400': form.errors.membership_number }" />
                            <span v-if="form.errors.membership_number" class="text-xs text-red-600">{{ form.errors.membership_number }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Office</label>
                            <Select :modelValue="form.office_id" @update:modelValue="form.office_id = $event">
                                <SelectTrigger><SelectValue placeholder="Select office" /></SelectTrigger>
                                <SelectContent><SelectItem v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</SelectItem></SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Type</label>
                            <Select :modelValue="form.membership_type" @update:modelValue="form.membership_type = $event">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="regular">Regular</SelectItem>
                                    <SelectItem value="associate">Associate</SelectItem>
                                    <SelectItem value="provisional">Provisional</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Status</label>
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
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Date Joined</label>
                            <Input type="date" v-model="form.date_joined" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Capital Contribution (&#8369;)</label>
                            <Input type="number" step="0.01" v-model="form.capital_contribution" placeholder="0.00" />
                        </div>
                    </div>
                </div>

                <!-- Employment -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Employment Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Occupation</label><Input v-model="form.occupation" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Employer</label><Input v-model="form.employer" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Monthly Income (&#8369;)</label><Input type="number" step="0.01" v-model="form.monthly_income" /></div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Emergency Contact</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Name</label><Input v-model="form.emergency_contact_name" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Relationship</label><Input v-model="form.emergency_contact_relationship" /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Phone</label><Input v-model="form.emergency_contact_phone" /></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link href="/super-admin/members"><Button type="button" variant="outline">Cancel</Button></Link>
                    <Button type="submit" :disabled="form.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />{{ form.processing ? "Creating..." : "Enroll Member" }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
