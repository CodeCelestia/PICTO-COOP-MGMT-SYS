<script setup lang="ts">
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { useCreateBack } from '@/composables/useCreateBack';
import { Head, Link } from '@inertiajs/vue3';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed, ref } from 'vue';

interface FundingSource {
    id: number;
    activity_id: number | null;
    category: 'activity' | 'project' | 'member_concern';
    coop_id: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string | null;
    amount_released: string | null;
    date_released: string | null;
    status: string;
    remarks: string | null;
    attachment_paths?: string[] | null;
    attachment_names?: string[] | null;
    created_at?: string | null;
    updated_at?: string | null;
    activity?: { title?: string };
    cooperative?: { name?: string };
}

const props = defineProps<{
    fundingSource: FundingSource;
    permissions: {
        can_edit: boolean;
    };
}>();

const { goBack } = useCreateBack({ fallbackHref: '/finance/funding-sources' });

const isFilesDialogOpen = ref(false);
const attachmentList = computed(() =>
    (props.fundingSource.attachment_names || []).map((name, idx) => ({
        name,
        url: props.fundingSource.attachment_paths?.[idx]
            ? `/storage/${props.fundingSource.attachment_paths[idx]}`
            : undefined,
    }))
);

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const categoryLabel = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'Member Concern';
    if (category === 'project') return 'Project';
    return 'Activity';
};

const activityLabel = (source: FundingSource) => {
    if (source.activity_id && source.activity?.title) {
        return source.activity.title;
    }

    if (source.activity_id === null) {
        return 'General Fund';
    }

    return 'Manual Entry';
};
</script>

<template>
    <Head :title="`Finance - Funding Source #${fundingSource.id}`" />

    <FinanceShellLayout active-tab="funding-sources">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Funding Source Details</h1>
                    <p class="text-sm text-muted-foreground">Read-only funding source information.</p>
                </div>
                <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="goBack">Back</button>
            </div>

            <div class="space-y-6">
                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-muted-foreground">Basic Information</h2>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Funding Source ID</p>
                            <p class="text-base font-medium text-foreground">{{ fundingSource.id }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Cooperative</p>
                            <p class="text-base font-medium text-foreground">{{ fundingSource.cooperative?.name || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Category</p>
                            <p class="text-base font-medium text-foreground">{{ categoryLabel(fundingSource.category) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Activity</p>
                            <p class="text-base font-medium text-foreground">{{ activityLabel(fundingSource) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Funder Name</p>
                            <p class="text-base font-medium text-foreground">{{ fundingSource.funder_name }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Funder Type</p>
                            <p class="text-base font-medium text-foreground">{{ fundingSource.funder_type }}</p>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-muted-foreground">Financial Details</h2>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Amount Allocated</p>
                            <p class="text-base font-medium text-foreground">{{ formatPhilippinePeso(fundingSource.amount_allocated) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Amount Released</p>
                            <p class="text-base font-medium text-foreground">{{ formatPhilippinePeso(fundingSource.amount_released) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Date Released</p>
                            <p class="text-base font-medium text-foreground">{{ formatDate(fundingSource.date_released) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Status</p>
                            <p class="text-base font-medium text-foreground">{{ fundingSource.status }}</p>
                        </div>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold text-muted-foreground">Additional Details</h2>
                    <div class="grid grid-cols-1 gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5 md:col-span-2">
                            <p class="text-sm text-muted-foreground">Remarks</p>
                            <p class="text-base font-medium text-foreground whitespace-pre-line">{{ fundingSource.remarks || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5 md:col-span-2">
                            <p class="text-sm text-muted-foreground">Files</p>
                            <div class="text-base font-medium text-foreground">
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    @click="isFilesDialogOpen = true"
                                >
                                    Files
                                </Button>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Created</p>
                            <p class="text-base font-medium text-foreground">{{ formatDate(fundingSource.created_at) }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm text-muted-foreground">Last Updated</p>
                            <p class="text-base font-medium text-foreground">{{ formatDate(fundingSource.updated_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>

    <Dialog v-model:open="isFilesDialogOpen">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Funding Source Files</DialogTitle>
                <DialogDescription>Files uploaded for this funding source.</DialogDescription>
            </DialogHeader>
            <div class="space-y-2">
                <div v-if="attachmentList.length === 0" class="text-sm text-muted-foreground">
                    No files uploaded yet.
                </div>
                <ul v-else class="space-y-2">
                    <li v-for="file in attachmentList" :key="file.name" class="rounded-md border border-border px-3 py-2 text-sm">
                        <a v-if="file.url" :href="file.url" class="text-primary underline" target="_blank" rel="noreferrer">
                            {{ file.name }}
                        </a>
                        <span v-else>{{ file.name }}</span>
                    </li>
                </ul>
            </div>
        </DialogContent>
    </Dialog>
</template>
