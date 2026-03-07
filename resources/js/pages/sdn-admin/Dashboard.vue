<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, Building2, CheckCircle2, Clock, GitMerge, Users } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Stats {
    offices: number;
    members: number;
    pending: number;
    merge_queue: number;
}

interface Office {
    id: number;
    name: string;
    code: string;
    members_count: number;
    allow_self_registration: boolean;
}

interface PendingMember {
    id: number;
    name: string;
    email: string;
    office: { id: number; name: string } | null;
    created_at: string;
}

interface Props {
    stats: Stats;
    offices: Office[];
    pendingMembers: PendingMember[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDN Dashboard', href: '/sdn-admin/dashboard' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="SDN Dashboard" />

        <div class="space-y-6 p-6">

            <!-- Stats cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Offices</CardTitle>
                        <Building2 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.offices }}</div>
                        <p class="text-xs text-muted-foreground">In your SDN</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Members</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.members }}</div>
                        <p class="text-xs text-muted-foreground">Active members</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Pending Approval</CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending }}</div>
                        <p class="text-xs text-muted-foreground">Awaiting review</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Merge Queue</CardTitle>
                        <GitMerge class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.merge_queue }}</div>
                        <p class="text-xs text-muted-foreground">Duplicates to review</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Alerts -->
            <div v-if="stats.pending > 0 || stats.merge_queue > 0" class="space-y-3">
                <div v-if="stats.pending > 0" class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                    <Clock class="mt-0.5 h-4 w-4 shrink-0" />
                    <span class="text-sm">
                        <strong>{{ stats.pending }} membership application{{ stats.pending > 1 ? 's' : '' }}</strong>
                        {{ stats.pending > 1 ? 'are' : 'is' }} awaiting your approval.
                        <a href="/sdn-admin/users?status=pending" class="underline ml-1">Review now →</a>
                    </span>
                </div>
                <div v-if="stats.merge_queue > 0" class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                    <span class="text-sm">
                        <strong>{{ stats.merge_queue }} potential duplicate PDS record{{ stats.merge_queue > 1 ? 's' : '' }}</strong>
                        require review.
                        <a href="/sdn-admin/merge-queue" class="underline ml-1">Review merge queue →</a>
                    </span>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">

                <!-- Offices table -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Offices</CardTitle>
                                <CardDescription>All offices in your SDN</CardDescription>
                            </div>
                            <Button as="a" href="/sdn-admin/offices" size="sm" variant="outline">Manage</Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-muted-foreground text-xs">
                                    <th class="py-2 text-left font-medium">Name</th>
                                    <th class="py-2 text-right font-medium">Members</th>
                                    <th class="py-2 text-center font-medium">Self‑Reg</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="office in offices" :key="office.id" class="border-b last:border-0">
                                    <td class="py-2 font-medium">{{ office.name }}</td>
                                    <td class="py-2 text-right">{{ office.members_count }}</td>
                                    <td class="py-2 text-center">
                                        <CheckCircle2 v-if="office.allow_self_registration" class="mx-auto h-4 w-4 text-green-500" />
                                        <span v-else class="text-muted-foreground text-xs">—</span>
                                    </td>
                                </tr>
                                <tr v-if="!offices.length">
                                    <td colspan="3" class="py-6 text-center text-muted-foreground text-sm">No offices found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </CardContent>
                </Card>

                <!-- Pending members -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle>Pending Members</CardTitle>
                                <CardDescription>Recent applications awaiting approval</CardDescription>
                            </div>
                            <Button as="a" href="/sdn-admin/users?status=pending" size="sm" variant="outline">View all</Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-muted-foreground text-xs">
                                    <th class="py-2 text-left font-medium">Name</th>
                                    <th class="py-2 text-left font-medium">Office</th>
                                    <th class="py-2 text-left font-medium">Applied</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in pendingMembers" :key="m.id" class="border-b last:border-0">
                                    <td class="py-2">
                                        <div class="font-medium">{{ m.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ m.email }}</div>
                                    </td>
                                    <td class="py-2 text-sm">{{ m.office?.name ?? '—' }}</td>
                                    <td class="py-2 text-sm text-muted-foreground">{{ m.created_at }}</td>
                                </tr>
                                <tr v-if="!pendingMembers.length">
                                    <td colspan="3" class="py-6 text-center text-muted-foreground text-sm">No pending applications.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
