<script setup lang="ts">
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { useCreateBack } from '@/composables/useCreateBack';
import { Head, Link, router } from '@inertiajs/vue3';
import { Separator } from '@/components/ui/separator';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { computed as vueComputed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';

const isFromCoopContext = vueComputed(() => {
    // Check URL path for coop context
    if (window.location.pathname.startsWith('/cooperatives/')) {
        return true;
    }
    // Also check query parameter for backward compatibility
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = vueComputed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const coopSlug = vueComputed(() => usePage().props.auth?.user?.coop_slug ?? 'my');
const currentUrl = window.location.pathname + window.location.search;
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
const handleBackClick = () => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        router.get(`/cooperatives/${coopIdFromUrl.value}?tab=finance&subtab=funding-sources`);
        return;
    }

    goBack();
};

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

const statusBadgeClass = (status: string) => {
    switch ((status || '').toLowerCase()) {
        case 'released':
        case 'approved':
        case 'completed':
            return 'border border-green-200 bg-green-100 text-green-800';
        case 'pending':
        case 'draft':
            return 'border border-amber-200 bg-amber-100 text-amber-800';
        case 'inactive':
        case 'cancelled':
        case 'rejected':
            return 'border border-red-200 bg-red-100 text-red-800';
        default:
            return 'border border-gray-200 bg-gray-100 text-gray-800';
    }
};

const editHref = vueComputed(() => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        return `/cooperatives/${coopIdFromUrl.value}/finance/funding-sources/${props.fundingSource.id}/edit?return_to=${encodeURIComponent(currentUrl)}`;
    }

    return currentUrl
        ? `/finance/funding-sources/${props.fundingSource.id}/edit?return_to=${encodeURIComponent(currentUrl)}`
        : `/finance/funding-sources/${props.fundingSource.id}/edit`;
});

const displayText = (value?: string | null) => value && String(value).trim() ? value : '—';
</script>

<template>
    <Head :title="`Finance - Funding Source #${fundingSource.id}`" />

    <FinanceShellLayout active-tab="funding-sources" :hide-tabs="isFromCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex items-start justify-between gap-4 py-4">
                    <div>
                        <div v-if="isFromCoopContext" class="mb-2 text-sm text-muted-foreground">
                            <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                            <span class="mx-2">/</span>
                            <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">Cooperative</a>
                            <span class="mx-2">/</span>
                            <span>Funding Source</span>
                        </div>
                        <h1 class="text-xl font-semibold">Funding Source Details</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Read-only funding source information.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" type="button" @click="handleBackClick">Back</Button>
                        <Link :href="editHref">
                            <Button type="button" class="gap-2">
                                Edit
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                        <CardDescription>Core funding source details.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Funding Source ID</p>
                                <p class="mt-1 text-sm font-medium">{{ fundingSource.id }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Cooperative</p>
                                <p class="mt-1 text-sm font-medium">{{ displayText(fundingSource.cooperative?.name) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Category</p>
                                <p class="mt-1 text-sm font-medium">{{ categoryLabel(fundingSource.category) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Activity</p>
                                <p class="mt-1 text-sm font-medium">{{ activityLabel(fundingSource) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Funder Name</p>
                                <p class="mt-1 text-sm font-medium">{{ displayText(fundingSource.funder_name) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Funder Type</p>
                                <p class="mt-1 text-sm font-medium">{{ displayText(fundingSource.funder_type) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Status</p>
                                <Badge :class="[statusBadgeClass(fundingSource.status), 'mt-1 rounded-md px-2 py-0.5 text-xs font-medium']">
                                    {{ fundingSource.status }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Financial Summary</CardTitle>
                        <CardDescription>Amounts and timing.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Amount Allocated</p>
                            <p class="mt-1 text-sm font-medium">{{ formatPhilippinePeso(fundingSource.amount_allocated) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Amount Released</p>
                            <p class="mt-1 text-sm font-medium">{{ formatPhilippinePeso(fundingSource.amount_released) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Date Released</p>
                            <p class="mt-1 text-sm font-medium">{{ displayText(formatDate(fundingSource.date_released)) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-3">
                    <CardHeader>
                        <CardTitle>Additional Details</CardTitle>
                        <CardDescription>Remarks, timestamps, and attachments.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Remarks</p>
                                <p class="mt-1 whitespace-pre-line text-sm font-medium">{{ displayText(fundingSource.remarks) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Created</p>
                                <p class="mt-1 text-sm font-medium">{{ displayText(formatDate(fundingSource.created_at)) }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Last Updated</p>
                                <p class="mt-1 text-sm font-medium">{{ displayText(formatDate(fundingSource.updated_at)) }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Files</p>
                                <div class="mt-2">
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
                        </div>
                    </CardContent>
                </Card>
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
