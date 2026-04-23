<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
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

interface Training {
    id: number;
    title: string;
}

interface Cooperative {
    id: number;
    name: string;
}

interface Participant {
    id: number;
    officer_id: number | null;
    outcome: string | null;
    member: {
        id: number;
        first_name: string;
        last_name: string;
        email: string | null;
        phone: string | null;
    };
}

interface Props {
    training: Training;
    cooperative: Cooperative;
    participants: Participant[];
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Training & Capacity Building', href: '/trainings' },
    { title: props.training.title, href: `/trainings/${props.training.id}/cooperatives-participating` },
    { title: 'Cooperatives Participating', href: `/trainings/${props.training.id}/cooperatives-participating` },
    { title: props.cooperative.name, href: `/trainings/${props.training.id}/cooperatives/${props.cooperative.id}/participants` },
    { title: 'Participants', href: `/trainings/${props.training.id}/cooperatives/${props.cooperative.id}/participants` },
]);

const memberName = (participant: Participant) => {
    const full = `${participant.member.first_name || ''} ${participant.member.last_name || ''}`.trim();
    return full || 'N/A';
};

const participantRole = () => 'Participant';

const participantStatus = (participant: Participant) => {
    return participant.outcome || 'Registered';
};

const statusBadgeClass = (status: string) => {
    const normalized = status.toLowerCase();

    if (normalized === 'passed' || normalized === 'attended') {
        return 'border-green-200 bg-green-100 text-green-800 dark:border-green-500/40 dark:bg-green-500/20 dark:text-green-200';
    }

    if (normalized === 'failed' || normalized === 'absent') {
        return 'border-red-200 bg-red-100 text-red-800 dark:border-red-500/40 dark:bg-red-500/20 dark:text-red-200';
    }

    return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200';
};

const contactInfo = (participant: Participant) => {
    const email = participant.member.email || 'N/A';
    const phone = participant.member.phone || 'N/A';

    return `${email} / ${phone}`;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                            Participants from {{ cooperative.name }} in {{ training.title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            View member participants from the selected cooperative for this training.
                        </p>
                    </div>
                    <Link :href="`/trainings/${training.id}/cooperatives-participating`">
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
                                <TableHead>Member Name</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Contact Info</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-center">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="participants.length === 0">
                                <TableCell :colspan="5" class="text-center text-muted-foreground">
                                    No participants found for this cooperative.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="participant in participants" :key="participant.id">
                                <TableCell class="font-medium text-foreground">{{ memberName(participant) }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ participantRole() }}</TableCell>
                                <TableCell class="text-muted-foreground">{{ contactInfo(participant) }}</TableCell>
                                <TableCell>
                                    <span
                                        :class="['inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold', statusBadgeClass(participantStatus(participant))]"
                                    >
                                        {{ participantStatus(participant) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-center">
                                    <Link :href="`/members/${participant.member.id}`">
                                        <Button variant="outline" size="sm" class="gap-2">
                                            <Eye class="h-4 w-4" />
                                            View
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
