<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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

interface TrainingSummary {
    id: number | null;
    title: string | null;
    date_conducted: string | null;
    status: string | null;
    cooperative: string | null;
}

interface ParticipantEntry {
    id: number;
    training: TrainingSummary;
    outcome: string | null;
    certificate_no: string | null;
    certificate_date: string | null;
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
</script>

<template>
    <Head title="My Trainings" />

    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <Card class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">My Trainings</h1>
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
                    <CardTitle class="text-base font-semibold text-foreground">Training Participation</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">
                        Trainings you have been registered for or attended.
                    </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Training</TableHead>
                                    <TableHead>Date Conducted</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Cooperative</TableHead>
                                    <TableHead>Outcome</TableHead>
                                    <TableHead>Certificate</TableHead>
                                    <TableHead class="text-center">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="participants.length === 0">
                                    <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                                        No training participation recorded yet.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="entry in participants" :id="`training-${entry.id}`" :key="entry.id">
                                    <TableCell class="text-sm text-foreground">{{ entry.training.title || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ formatDate(entry.training.date_conducted) }}</TableCell>
                                    <TableCell>
                                        <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">
                                            {{ entry.training.status || 'N/A' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.training.cooperative || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.outcome || 'N/A' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ entry.certificate_no || 'N/A' }}</TableCell>
                                    <TableCell class="text-center">
                                        <a :href="`#training-${entry.id}`" class="inline-flex">
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
