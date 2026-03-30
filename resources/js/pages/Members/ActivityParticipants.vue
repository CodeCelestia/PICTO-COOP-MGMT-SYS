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
        <div class="p-6">
            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Activity Participation</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ member.first_name }} {{ member.last_name }} · {{ member.cooperative.name }}
                    </p>
                </div>
                <Link :href="`/members/${member.id}`">
                    <Button variant="outline">Back to Member</Button>
                </Link>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
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
                            <TableCell colspan="7" class="text-center text-gray-500">
                                No activity participation recorded yet.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="entry in participants" :key="entry.id">
                            <TableCell class="text-sm text-gray-900">{{ entry.activity.title || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ entry.activity.category || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ entry.activity.status || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ entry.activity.cooperative || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ entry.role || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(entry.date_joined) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ beneficiaryLabel(entry.is_beneficiary) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
