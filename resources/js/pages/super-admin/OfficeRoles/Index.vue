<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, Shield } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalConfirm } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { index as officeRolesIndex, destroy as officeRolesDestroy } from '@/routes/super-admin/office-roles';

interface OfficeRole {
    id: number;
    name: string;
    display_name: string;
    description: string | null;
    is_system: boolean;
    created_at: string;
}

interface Props {
    roles: {
        data: OfficeRole[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/super-admin/dashboard' },
    { title: 'Office Roles', href: '/super-admin/office-roles' },
];

const deleteRole = async (role: OfficeRole) => {
    if (role.is_system) {
        return;
    }

    const confirmed = await swalConfirm(
        'Delete Role?',
        `Are you sure you want to delete "${role.display_name}"? This action cannot be undone.`
    );

    if (confirmed.isConfirmed) {
        router.delete(officeRolesDestroy(role.id).url);
    }
};
</script>

<template>
    <Head title="Office Roles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-6xl mx-auto w-full">

            <!-- Header -->
            <div class="rounded-2xl bg-linear-to-r from-purple-600 to-pink-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <Shield class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Office Roles Management</h1>
                            <p class="text-sm text-purple-200">Manage roles for office assignments</p>
                        </div>
                    </div>
                    <Link :href="'/super-admin/office-roles/create'">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <Plus class="h-4 w-4" /> Add New Role
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Roles List -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Role Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Display Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Description
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="role in roles.data" :key="role.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <code class="text-sm font-mono bg-slate-100 px-2 py-1 rounded">{{ role.name }}</code>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-slate-900">{{ role.display_name }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">{{ role.description || '—' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="role.is_system" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        System
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Custom
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <Link :href="`/super-admin/office-roles/${role.id}/edit`">
                                        <Button size="sm" variant="outline" class="gap-1.5">
                                            <Edit class="h-3 w-3" /> Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="!role.is_system"
                                        size="sm"
                                        variant="destructive"
                                        class="gap-1.5"
                                        @click="deleteRole(role)"
                                    >
                                        <Trash2 class="h-3 w-3" /> Delete
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="!roles.data.length">
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    No office roles found. Click "Add New Role" to create one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
