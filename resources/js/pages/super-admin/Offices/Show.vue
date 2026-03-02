<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Users, Mail, Phone, MapPin, Building2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalConfirmDelete, swalSuccess } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { destroy as officesDestroy, edit as officesEdit } from '@/routes/super-admin/offices';
import { index as officesUsersIndex } from '@/routes/super-admin/offices/users';

type OfficeUser = { id: number; name: string; email: string; pivot: { role_name: string } };
type Office = { id: number; name: string; code: string; region_name: string | null; province_name: string | null; city_municipality_name: string | null; barangay_name: string | null; contact_email: string | null; contact_phone: string | null; users: OfficeUser[] };

const props = defineProps<{ office: Office }>();
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Offices', href: '/super-admin/offices' },
    { title: props.office.name, href: '' },
];

const location = [props.office.barangay_name, props.office.city_municipality_name, props.office.province_name, props.office.region_name].filter(Boolean).join(', ');

const deleteOffice = async () => {
    const result = await swalConfirmDelete(props.office.name);
    if (result.isConfirmed) router.delete(officesDestroy(props.office.id).url);
};
</script>

<template>
    <Head :title="office.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <!-- Header -->
            <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-xl font-black">
                            {{ office.name.charAt(0) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ office.name }}</h1>
                            <p class="mt-0.5 font-mono text-sm text-indigo-200">{{ office.code }}</p>
                            <p v-if="location" class="mt-1 flex items-center gap-1 text-sm text-indigo-200">
                                <MapPin class="h-3.5 w-3.5" /> {{ location }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="officesEdit(office.id).url">
                            <Button class="bg-white/20 hover:bg-white/30 text-white border border-white/30 gap-2"><Pencil class="h-4 w-4" /> Edit</Button>
                        </Link>
                        <Button variant="ghost" class="border border-red-300/50 text-red-100 hover:bg-red-500/30 gap-2" @click="deleteOffice">
                            Delete
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Details -->
                <div class="md:col-span-2 rounded-xl border border-slate-200 bg-white shadow-sm p-6 space-y-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Office Details</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-500 mb-0.5">Office Code</p>
                            <p class="font-mono text-sm font-semibold text-slate-800">{{ office.code }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-500 mb-0.5">Region</p>
                            <p class="text-sm font-semibold text-slate-800">{{ office.region_name ?? '—' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-500 mb-0.5">Province</p>
                            <p class="text-sm font-semibold text-slate-800">{{ office.province_name ?? '—' }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 p-3">
                            <p class="text-xs text-slate-500 mb-0.5">City / Municipality</p>
                            <p class="text-sm font-semibold text-slate-800">{{ office.city_municipality_name ?? '—' }}</p>
                        </div>
                        <div v-if="office.contact_email" class="rounded-lg bg-slate-50 p-3 flex items-center gap-2">
                            <Mail class="h-4 w-4 text-indigo-500 shrink-0" />
                            <div>
                                <p class="text-xs text-slate-500">Email</p>
                                <p class="text-sm font-medium text-slate-800">{{ office.contact_email }}</p>
                            </div>
                        </div>
                        <div v-if="office.contact_phone" class="rounded-lg bg-slate-50 p-3 flex items-center gap-2">
                            <Phone class="h-4 w-4 text-indigo-500 shrink-0" />
                            <div>
                                <p class="text-xs text-slate-500">Phone</p>
                                <p class="text-sm font-medium text-slate-800">{{ office.contact_phone }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users sidebar -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Users</p>
                        <span class="inline-flex items-center justify-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">{{ office.users.length }}</span>
                    </div>
                    <div class="space-y-2 mb-4">
                        <div v-for="u in office.users.slice(0, 5)" :key="u.id" class="flex items-center gap-2">
                            <div class="h-7 w-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">{{ u.name.charAt(0) }}</div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ u.name }}</p>
                                <p class="text-xs text-slate-500 capitalize">{{ u.pivot.role_name }}</p>
                            </div>
                        </div>
                        <p v-if="office.users.length > 5" class="text-xs text-slate-400">+{{ office.users.length - 5 }} more</p>
                        <p v-if="!office.users.length" class="text-xs text-slate-400 italic">No users assigned</p>
                    </div>
                    <Link :href="officesUsersIndex(office.id).url">
                        <Button class="w-full gap-2 bg-indigo-600 hover:bg-indigo-700 text-white">
                            <Users class="h-4 w-4" /> Manage Users
                        </Button>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
