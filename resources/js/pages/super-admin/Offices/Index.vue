<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onMounted, watch } from 'vue';
import { Plus, Pencil, Trash2, Users, MapPin, Building2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalConfirmDelete, swalSuccess } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { destroy as officesDestroy, create as officesCreate, edit as officesEdit } from '@/routes/super-admin/offices';
import { index as officesUsersIndex } from '@/routes/super-admin/offices/users';

type Office = { id: number; name: string; code: string; region_name: string | null; city_municipality_name: string | null; contact_email: string | null; contact_phone: string | null; users_count: number };
type Paginator = { data: Office[]; current_page: number; last_page: number; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] };

const props = defineProps<{ offices: Paginator }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/super-admin/dashboard' },
    { title: 'Offices', href: '/super-admin/offices' },
];

const page = usePage<{ flash?: { success?: string } }>();
onMounted(() => { if (page.props.flash?.success) swalSuccess(page.props.flash.success); });
watch(() => page.props.flash?.success, (v) => { if (v) swalSuccess(v); });

const initials = (n: string) => n.split(' ').slice(0, 2).map((w: string) => w[0]).join('').toUpperCase();

const deleteOffice = async (id: number, name: string) => {
    const result = await swalConfirmDelete(name);
    if (result.isConfirmed) router.delete(officesDestroy(id).url, { preserveScroll: true });
};
</script>

<template>
    <Head title="Offices" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            <!-- Vibrant Header -->
            <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <Building2 class="h-5 w-5 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Office Management</h1>
                            <p class="text-sm text-indigo-200">{{ props.offices.total }} cooperative offices registered</p>
                        </div>
                    </div>
                    <Link :href="officesCreate().url">
                        <Button class="bg-white text-indigo-700 hover:bg-indigo-50 gap-2 font-semibold shadow">
                            <Plus class="h-4 w-4" /> New Office
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Office</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Contact</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-300">Users</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="office in props.offices.data" :key="office.id" class="hover:bg-indigo-50/40 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs font-bold shrink-0">
                                        {{ initials(office.name) }}
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">{{ office.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 px-2 py-0.5 rounded-md">{{ office.code }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div v-if="office.city_municipality_name || office.region_name" class="flex items-center gap-1 text-sm text-slate-600">
                                    <MapPin class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                    {{ [office.city_municipality_name, office.region_name].filter(Boolean).join(', ') }}
                                </div>
                                <span v-else class="text-xs text-slate-400 italic">No location</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ office.contact_email ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    <Users class="h-3 w-3" /> {{ office.users_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="officesUsersIndex(office.id).url">
                                        <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-indigo-700 hover:bg-indigo-50">
                                            <Users class="h-3.5 w-3.5" /> Users
                                        </Button>
                                    </Link>
                                    <Link :href="officesEdit(office.id).url">
                                        <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-amber-700 hover:bg-amber-50">
                                            <Pencil class="h-3.5 w-3.5" /> Edit
                                        </Button>
                                    </Link>
                                    <Button size="sm" variant="ghost" class="h-8 gap-1 px-2 text-slate-600 hover:text-red-700 hover:bg-red-50"
                                        @click="deleteOffice(office.id, office.name)">
                                        <Trash2 class="h-3.5 w-3.5" /> Delete
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!props.offices.data.length">
                            <td colspan="6" class="px-4 py-16 text-center">
                                <Building2 class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                                <p class="text-sm text-slate-500">No offices found.</p>
                                <Link :href="officesCreate().url" class="text-indigo-600 text-sm hover:underline mt-1 inline-block">Create the first office →</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="props.offices.last_page > 1"
                    class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs text-slate-500">
                        Showing {{ props.offices.from }}–{{ props.offices.to }} of {{ props.offices.total }}
                    </p>
                    <div class="flex gap-1">
                        <template v-for="link in props.offices.links" :key="link.label">
                            <Link v-if="link.url"
                                :href="link.url"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-indigo-600 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-100'"
                                v-html="link.label" />
                            <span v-else class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs text-slate-400 cursor-default" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
