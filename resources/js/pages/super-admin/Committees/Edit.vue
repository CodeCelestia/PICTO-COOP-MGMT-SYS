<script setup lang="ts">
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, Users2 } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalError } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { update as committeesUpdate } from "@/routes/super-admin/committees";

type Committee = { id: number; name: string; code: string; type: string; status: string; office_id: number | null; description: string | null };
type Office = { id: number; name: string };
const props = defineProps<{ committee: Committee; offices: Office[] }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Committees", href: "/super-admin/committees" }, { title: props.committee.name, href: `/super-admin/committees/${props.committee.id}` }, { title: "Edit", href: "" }];
const form = useForm({ name: props.committee.name, code: props.committee.code, type: props.committee.type, status: props.committee.status, office_id: props.committee.office_id ? String(props.committee.office_id) : "", description: props.committee.description ?? "" });
const submit = () => {
    form.patch(committeesUpdate(props.committee.id).url, {
        onSuccess: () => swalSuccess("Committee Updated!", "Changes have been saved."),
        onError: () => swalError("Validation Error", "Please check the fields."),
    });
};
</script>
<template>
    <Head :title="`Edit ${committee.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-3xl mx-auto w-full">
            <div class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Users2 class="h-5 w-5" /></div><div><h1 class="text-xl font-bold">Edit Committee</h1><p class="text-sm text-amber-100">{{ committee.name }}</p></div></div>
                    <Link :href="`/super-admin/committees/${committee.id}`"><Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button></Link>
                </div>
            </div>
            <form @submit.prevent="submit">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6 space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Committee Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Name <span class="text-red-500">*</span></label><Input v-model="form.name" required /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Code <span class="text-red-500">*</span></label><Input v-model="form.code" required /></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Type</label><Select :modelValue="form.type" @update:modelValue="form.type = $event"><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="standing">Standing</SelectItem><SelectItem value="special">Special</SelectItem><SelectItem value="ad_hoc">Ad Hoc</SelectItem></SelectContent></Select></div>
                        <div class="space-y-1.5"><label class="block text-sm font-semibold text-slate-700">Status</label><Select :modelValue="form.status" @update:modelValue="form.status = $event"><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="active">Active</SelectItem><SelectItem value="inactive">Inactive</SelectItem></SelectContent></Select></div>
                        <div class="space-y-1.5 md:col-span-2"><label class="block text-sm font-semibold text-slate-700">Office</label><Select :modelValue="form.office_id" @update:modelValue="form.office_id = $event"><SelectTrigger><SelectValue placeholder="Select office" /></SelectTrigger><SelectContent><SelectItem v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</SelectItem></SelectContent></Select></div>
                        <div class="space-y-1.5 md:col-span-2"><label class="block text-sm font-semibold text-slate-700">Description</label><textarea v-model="form.description" rows="3" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none resize-none"></textarea></div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <Link :href="`/super-admin/committees/${committee.id}`"><Button type="button" variant="outline">Cancel</Button></Link>
                        <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm"><Save class="h-4 w-4" />{{ form.processing ? "Saving..." : "Save Changes" }}</Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
