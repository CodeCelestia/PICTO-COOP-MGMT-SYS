<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Users } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { BreadcrumbItem } from '@/types';

interface Activity {
    id: number;
    title: string;
}

interface Cooperative {
    id: number;
    name: string;
    classification: string | null;
    status: string | null;
}

interface Props {
    activity: Activity;
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Activities & Projects', href: '/activities' },
    { title: props.activity.title, href: `/activities/${props.activity.id}/cooperatives-participating` },
    { title: 'Cooperatives Participating', href: `/activities/${props.activity.id}/cooperatives-participating` },
]);

const statusBadgeClass = (status: string | null) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'active') {
        return 'border-green-200 bg-green-100 text-green-800 dark:border-green-500/40 dark:bg-green-500/20 dark:text-green-200';
    }

    if (normalized === 'inactive') {
        return 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200';
    }

    return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                            Cooperatives Participating in {{ activity.title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            Review cooperatives involved in this activity and drill down to member participants.
                        </p>
                    </div>
                    <Link href="/activities">
                        <Button variant="outline" class="h-9 gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Cooperative Name</TableHead>
                                <TableHead>Cooperative Type</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="cooperatives.length === 0">
                                <TableCell :colspan="4" class="text-center text-muted-foreground">
                                    No participating cooperatives found.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="cooperative in cooperatives" :key="cooperative.id">
                                <TableCell class="font-medium text-foreground">{{ cooperative.name }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ cooperative.classification || 'N/A' }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold', statusBadgeClass(cooperative.status)]"
                                    >
                                        {{ cooperative.status || 'Unknown' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Link :href="`/activities/${activity.id}/cooperatives/${cooperative.id}/participants`">
                                        <Button variant="outline" size="sm" class="gap-2">
                                            <Users class="h-4 w-4" />
                                            Participants
                                        </Button>
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
