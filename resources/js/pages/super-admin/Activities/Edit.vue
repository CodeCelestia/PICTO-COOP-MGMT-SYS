<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { useForm } from "@inertiajs/vue3";
import { ArrowLeft, Pencil } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import AppLayout from "@/layouts/AppLayout.vue";
import { swalSuccess, swalError } from "@/composables/useSwal";
import type { BreadcrumbItem } from "@/types";
import { update as activitiesUpdate, show as activitiesShow } from "@/routes/super-admin/activities";

type Office = { id: number; name: string };
type Activity = { id: number; title: string; office_id: number | null; type: string; status: string; activity_date: string | null; start_time: string | null; end_time: string | null; venue: string | null; budget: string | null; description: string | null };
const props = defineProps<{ activity: Activity; offices: Office[] }>();
const breadcrumbs: BreadcrumbItem[] = [{ title: "Activities", href: "/super-admin/activities" }, { title: "Edit", href: "#" }];
const form = useForm({ title: props.activity.title, office_id: props.activity.office_id ?? "", type: props.activity.type, status: props.activity.status, activity_date: props.activity.activity_date ?? "", start_time: props.activity.start_time ?? "", end_time: props.activity.end_time ?? "", venue: props.activity.venue ?? "", budget: props.activity.budget ?? "", description: props.activity.description ?? "" });
const submit = () => form.patch(activitiesUpdate(props.activity.id).url, {
    onSuccess: () => swalSuccess("Activity Updated!", "Changes have been saved."),
    onError: () => swalError("Validation Error", "Please review the highlighted fields."),
});
</script>
<template>
    <Head :title="`Edit – ${activity.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-3xl mx-auto w-full">
            <div class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Pencil class="h-5 w-5" /></div>
                    <div class="flex-1"><h1 class="text-xl font-bold">Edit Activity</h1><p class="text-sm text-amber-100">{{ activity.title }}</p></div>
                    <Link :href="activitiesShow(activity.id).url"><Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button></Link>
                </div>
            </div>
            <form @submit.prevent="submit" class="rounded-xl border border-slate-200 bg-white shadow-sm p-6 flex flex-col gap-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <Label for="title" class="text-xs font-bold uppercase tracking-wider text-amber-600">Title *</Label>
                        <Input id="title" v-model="form.title" />
                        <p v-if="form.errors.title" class="text-xs text-red-600">{{ form.errors.title }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="office_id" class="text-xs font-bold uppercase tracking-wider text-amber-600">Office</Label>
                        <select id="office_id" v-model="form.office_id" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="">— None —</option>
                            <option v-for="o in offices" :key="o.id" :value="o.id">{{ o.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="type" class="text-xs font-bold uppercase tracking-wider text-amber-600">Type *</Label>
                        <select id="type" v-model="form.type" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="meeting">Meeting</option>
                            <option value="seminar">Seminar</option>
                            <option value="training">Training</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="status" class="text-xs font-bold uppercase tracking-wider text-amber-600">Status *</Label>
                        <select id="status" v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                            <option value="planned">Planned</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="activity_date" class="text-xs font-bold uppercase tracking-wider text-amber-600">Date</Label>
                        <Input id="activity_date" type="date" v-model="form.activity_date" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="start_time" class="text-xs font-bold uppercase tracking-wider text-amber-600">Start Time</Label>
                        <Input id="start_time" type="time" v-model="form.start_time" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="end_time" class="text-xs font-bold uppercase tracking-wider text-amber-600">End Time</Label>
                        <Input id="end_time" type="time" v-model="form.end_time" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="venue" class="text-xs font-bold uppercase tracking-wider text-amber-600">Venue</Label>
                        <Input id="venue" v-model="form.venue" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label for="budget" class="text-xs font-bold uppercase tracking-wider text-amber-600">Budget (₱)</Label>
                        <Input id="budget" type="number" step="0.01" v-model="form.budget" />
                    </div>
                    <div class="sm:col-span-2 flex flex-col gap-1.5">
                        <Label for="description" class="text-xs font-bold uppercase tracking-wider text-amber-600">Description</Label>
                        <textarea id="description" v-model="form.description" rows="4" class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <Link :href="activitiesShow(activity.id).url"><Button type="button" variant="outline">Cancel</Button></Link>
                    <Button type="submit" :disabled="form.processing" class="bg-amber-500 hover:bg-amber-600 text-white">{{ form.processing ? "Saving…" : "Save Changes" }}</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
