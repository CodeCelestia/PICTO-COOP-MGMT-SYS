<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Search, UserCheck, UserX } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalConfirmDelete, swalSuccess } from '@/composables/useSwal';
import type { BreadcrumbItem, Paginator } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    status: 'pending' | 'active' | 'suspended';
    office: { id: number; name: string } | null;
    created_at: string;
}

interface Office {
    id: number;
    name: string;
}

interface Filters {
    search: string;
    status: string;
    office_id: string;
}

interface Props {
    users: Paginator<User>;
    offices: Office[];
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDN Dashboard', href: '/sdn-admin/dashboard' },
    { title: 'Member Accounts', href: '/sdn-admin/users' },
];

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '_all');
const officeFilter = ref(props.filters.office_id ? String(props.filters.office_id) : '_all');

let debounce: ReturnType<typeof setTimeout>;
const applyFilters = () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        router.get(
            '/sdn-admin/users',
            {
                search: search.value || undefined,
                status: statusFilter.value === '_all' ? undefined : statusFilter.value,
                office_id: officeFilter.value === '_all' ? undefined : officeFilter.value,
            },
            { preserveState: true, replace: true },
        );
    }, 350);
};

const statusBadgeVariant = (status: string) =>
    status === 'active'    ? 'default'     :
    status === 'suspended' ? 'destructive' : 'secondary';

const suspend = async (user: User) => {
    const result = await swalConfirmDelete(`Suspend "${user.name}"?`);
    if (!result.isConfirmed) return;

    router.patch(`/sdn-admin/users/${user.id}/suspend`, {}, {
        preserveScroll: true,
        onSuccess: () => swalSuccess(`${user.name} has been suspended.`),
    });
};

const activate = (user: User) => {
    router.patch(`/sdn-admin/users/${user.id}/activate`, {}, {
        preserveScroll: true,
        onSuccess: () => swalSuccess(`${user.name} has been activated.`),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Member Accounts" />

        <div class="space-y-4 p-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">Member Accounts</h1>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <div class="relative">
                    <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                        v-model="search"
                        class="pl-8 w-56"
                        placeholder="Search by name or email…"
                        @input="applyFilters"
                    />
                </div>

                <Select
                    :model-value="statusFilter"
                    @update:model-value="(val) => { statusFilter = String(val ?? '_all'); applyFilters(); }"
                >
                    <SelectTrigger class="w-40">
                        <SelectValue placeholder="All statuses" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="_all">All statuses</SelectItem>
                        <SelectItem value="active">Active</SelectItem>
                        <SelectItem value="pending">Pending</SelectItem>
                        <SelectItem value="suspended">Suspended</SelectItem>
                    </SelectContent>
                </Select>

                <Select
                    :model-value="officeFilter"
                    @update:model-value="(val) => { officeFilter = String(val ?? '_all'); applyFilters(); }"
                >
                    <SelectTrigger class="w-52">
                        <SelectValue placeholder="All offices" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="_all">All offices</SelectItem>
                        <SelectItem v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground text-xs">
                            <th class="py-2 text-left font-medium">Name</th>
                            <th class="py-2 text-left font-medium">Office</th>
                            <th class="py-2 text-left font-medium">Status</th>
                            <th class="py-2 text-left font-medium">Registered</th>
                            <th class="py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0">
                            <td class="py-2">
                                <div class="font-medium">{{ user.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                            </td>
                            <td class="py-2 text-sm">{{ user.office?.name ?? '—' }}</td>
                            <td class="py-2">
                                <Badge :variant="statusBadgeVariant(user.status)">{{ user.status }}</Badge>
                            </td>
                            <td class="py-2 text-sm text-muted-foreground">{{ user.created_at }}</td>
                            <td class="py-2 text-right">
                                <Button
                                    v-if="user.status !== 'active'"
                                    size="sm"
                                    variant="outline"
                                    class="mr-1"
                                    @click="activate(user)"
                                >
                                    <UserCheck class="mr-1 h-3 w-3" /> Activate
                                </Button>
                                <Button
                                    v-if="user.status === 'active'"
                                    size="sm"
                                    variant="destructive"
                                    @click="suspend(user)"
                                >
                                    <UserX class="mr-1 h-3 w-3" /> Suspend
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td colspan="5" class="py-8 text-center text-muted-foreground">No users found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.last_page > 1" class="flex items-center justify-end gap-2">
                <template v-for="link in users.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        as="a"
                        :href="link.url"
                        v-html="link.label"
                    />
                    <span v-else class="px-2 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
