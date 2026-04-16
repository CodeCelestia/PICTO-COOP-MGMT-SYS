<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    cooperative: Cooperative | null;
}

interface ActivitySummary {
    id: number | null;
    title: string | null;
    category: string | null;
    status: string | null;
    cooperative: string | null;
}

interface ParticipantEntry {
    id: number;
    activity: ActivitySummary;
    role: string | null;
    date_joined: string | null;
    is_beneficiary: boolean;
    remarks: string | null;
}

interface Props {
    member: Member;
    participants: ParticipantEntry[];
}

defineProps<Props>();

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const beneficiaryLabel = (value: boolean) => (value ? 'Yes' : 'No');
</script>

<template>
    <Head title="My Activity Participation" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <Card class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">My Activity Participation</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ member.first_name }} {{ member.last_name }}
                            <span v-if="member.cooperative">· {{ member.cooperative.name }}</span>
                        </p>
                    </div>
                    <Link href="/member-portal">
                        <Button variant="outline" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Dashboard
                        </Button>
                    </Link>
                </div>
            </Card>

            <Card class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <CardHeader class="px-6 pb-3">
                    <CardTitle class="text-base font-semibold text-foreground">Participation History</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">Activities where you are listed as a participant.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Activity</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Cooperative</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Date Joined</TableHead>
                                    <TableHead>Beneficiary</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="participants.length === 0">
                                    <TableCell colspan="8" class="py-8 text-center text-muted-foreground">
                                        No activity participation recorded yet.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="entry in participants" :id="`activity-${entry.id}`" :key="entry.id">
                                    <TableCell class="text-sm text-foreground">{{ entry.activity.title || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.activity.category || 'N/A' }}</TableCell>
                                    <TableCell>
                                        <Badge :class="[getFinanceStatusBadgeClass(entry.activity.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                            {{ entry.activity.status || 'N/A' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.activity.cooperative || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.role || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(entry.date_joined) }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ beneficiaryLabel(entry.is_beneficiary) }}</TableCell>
                                    <TableCell class="text-center">
                                        <a :href="`#activity-${entry.id}`" class="inline-flex">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </a>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
