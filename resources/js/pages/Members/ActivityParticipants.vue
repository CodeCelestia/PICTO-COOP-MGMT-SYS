<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
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
    cooperative: Cooperative;
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

const props = defineProps<Props>();

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
    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Activity Participation</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ member.first_name }} {{ member.last_name }} · {{ member.cooperative.name }}
                    </p>
                </div>
                <Link :href="`/members/${member.id}`">
                    <Button variant="outline">Back to Member</Button>
                </Link>
            </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
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
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="participants.length === 0">
                                <TableCell colspan="7" class="py-10 text-center text-muted-foreground">
                                    No activity participation recorded yet.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="entry in participants" :key="entry.id">
                                <TableCell class="text-sm text-foreground">{{ entry.activity.title || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ entry.activity.category || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ entry.activity.status || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ entry.activity.cooperative || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ entry.role || 'N/A' }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ formatDate(entry.date_joined) }}</TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ beneficiaryLabel(entry.is_beneficiary) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
