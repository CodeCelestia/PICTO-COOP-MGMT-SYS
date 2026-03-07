<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Check, GitMerge, X } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalConfirmDelete, swalSuccess } from '@/composables/useSwal';
import type { BreadcrumbItem, Paginator } from '@/types';

interface MergeQueueEntry {
    id: number;
    match_type: string;
    status: 'pending' | 'approved' | 'rejected';
    source_pds: { id: number; full_name: string; email: string };
    target_pds: { id: number; full_name: string; email: string };
    triggered_by_user: { id: number; name: string } | null;
    created_at: string;
    notes: string | null;
}

interface Props {
    queue: Paginator<MergeQueueEntry>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'SDN Dashboard', href: '/sdn-admin/dashboard' },
    { title: 'Merge Queue', href: '/sdn-admin/merge-queue' },
];

const matchTypeLabel = (type: string) => ({
    exact_id:       'Exact Gov\'t ID',
    exact_email:    'Exact Email',
    fuzzy_name_dob: 'Name + DOB',
    suspected:      'Suspected',
}[type] ?? type);

const statusBadgeVariant = (status: string) =>
    status === 'approved'  ? 'default'     :
    status === 'rejected'  ? 'destructive' : 'secondary';

// ── Approve ───────────────────────────────────────────────────────────────────
const approveEntry = async (entry: MergeQueueEntry) => {
    const result = await swalConfirmDelete(
        `Confirm merge: "${entry.source_pds.full_name}" → "${entry.target_pds.full_name}"?`
    );
    if (!result.isConfirmed) return;

    router.post(`/sdn-admin/merge-queue/${entry.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => swalSuccess('Records merged successfully.'),
    });
};

// ── Reject ────────────────────────────────────────────────────────────────────
const showRejectModal = ref(false);
const rejectForm = useForm({ notes: '' });
const activeEntry = ref<MergeQueueEntry | null>(null);

const openRejectModal = (entry: MergeQueueEntry) => {
    activeEntry.value = entry;
    rejectForm.reset();
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!activeEntry.value) return;
    rejectForm.post(`/sdn-admin/merge-queue/${activeEntry.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            showRejectModal.value = false;
            swalSuccess('Entry rejected.');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Merge Queue" />

        <div class="space-y-4 p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <GitMerge class="h-6 w-6" /> Merge Queue
                    </h1>
                    <p class="text-muted-foreground text-sm mt-1">Review potential duplicate PDS records detected by the system.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-muted-foreground text-xs">
                            <th class="py-2 text-left font-medium">Source Record</th>
                            <th class="py-2 text-left font-medium">Target Record</th>
                            <th class="py-2 text-left font-medium">Match Type</th>
                            <th class="py-2 text-left font-medium">Status</th>
                            <th class="py-2 text-left font-medium">Detected</th>
                            <th class="py-2 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in queue.data" :key="entry.id" class="border-b last:border-0">
                            <td class="py-2">
                                <div class="font-medium">{{ entry.source_pds.full_name }}</div>
                                <div class="text-xs text-muted-foreground">{{ entry.source_pds.email }}</div>
                            </td>
                            <td class="py-2">
                                <div class="font-medium">{{ entry.target_pds.full_name }}</div>
                                <div class="text-xs text-muted-foreground">{{ entry.target_pds.email }}</div>
                            </td>
                            <td class="py-2">
                                <Badge variant="outline">{{ matchTypeLabel(entry.match_type) }}</Badge>
                            </td>
                            <td class="py-2">
                                <Badge :variant="statusBadgeVariant(entry.status)">{{ entry.status }}</Badge>
                            </td>
                            <td class="py-2 text-muted-foreground">{{ entry.created_at }}</td>
                            <td class="py-2 text-right">
                                <template v-if="entry.status === 'pending'">
                                    <Button size="sm" class="mr-1" @click="approveEntry(entry)">
                                        <Check class="mr-1 h-3 w-3" /> Approve
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="openRejectModal(entry)">
                                        <X class="mr-1 h-3 w-3" /> Reject
                                    </Button>
                                </template>
                                <span v-else class="text-xs text-muted-foreground italic">{{ entry.notes ?? 'No notes' }}</span>
                            </td>
                        </tr>
                        <tr v-if="!queue.data.length">
                            <td colspan="6" class="py-8 text-center text-muted-foreground">No merge queue entries. All clear!</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (links-based) -->
            <div v-if="queue.last_page > 1" class="flex items-center justify-end gap-2">
                <template v-for="link in queue.links" :key="link.label">
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

        <!-- Reject Modal -->
        <Dialog v-model:open="showRejectModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reject Duplicate Match</DialogTitle>
                    <DialogDescription>
                        Please provide a reason for rejecting this duplicate match. This will be saved in the audit log.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitReject" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label for="reject-notes">Reason <span class="text-destructive">*</span></Label>
                        <textarea
                            id="reject-notes"
                            v-model="rejectForm.notes"
                            placeholder="Explain why these are not duplicates…"
                            required
                            rows="3"
                            class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <p v-if="rejectForm.errors.notes" class="text-xs text-destructive">{{ rejectForm.errors.notes }}</p>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showRejectModal = false">Cancel</Button>
                        <Button type="submit" variant="destructive" :disabled="rejectForm.processing">Reject</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
