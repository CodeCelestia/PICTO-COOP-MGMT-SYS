<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ClipboardList,
    Cloud,
    Download,
    File,
    FileSpreadsheet,
    FileText,
    Pencil,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface ActivityDetails {
    id: number;
    title: string;
    description: string | null;
    category: string | null;
    status: string | null;
    date_started: string | null;
    date_ended: string | null;
    venue: string | null;
    implementing_partner: string | null;
    funding_source: string | null;
    budget: string | number | null;
    actual_expense: string | number | null;
    target_member_beneficiaries: number | null;
    actual_member_beneficiaries: number | null;
    target_community_beneficiaries: number | null;
    actual_community_beneficiaries: number | null;
    outcomes: string | null;
    remarks: string | null;
    responsible_officer: string | null;
    cooperatives_count: number;
}

interface CooperativeSummary {
    id: number;
    name: string;
    registration_number?: string | null;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface AttachmentItem {
    path: string;
    name: string;
    url: string | null;
    size: number | null;
    source_activity_id?: number | null;
    funding_source_id?: number | null;
    funder_name?: string | null;
}

const props = defineProps<{
    activity: ActivityDetails;
    cooperatives: CooperativeSummary[];
    outcomesAttachments: AttachmentItem[];
    fundingAttachments: AttachmentItem[];
}>();

const EMPTY_VALUE = '—';

const goBack = () => {
    window.history.back();
};

const formatDateLong = (value: string | null) => {
    if (!value) return EMPTY_VALUE;

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return EMPTY_VALUE;

    return parsed.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatMoney = (value: string | number | null) => {
    if (value === null || value === undefined || value === '') return EMPTY_VALUE;

    const numeric = Number(value);
    if (Number.isNaN(numeric)) return EMPTY_VALUE;

    return numeric.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatCount = (value: number | null) => {
    if (value === null || value === undefined) return EMPTY_VALUE;
    return Number(value).toLocaleString('en-US');
};

const formatAttachmentSize = (bytes: number | null) => {
    if (bytes === null || bytes === undefined) return 'Size unavailable';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
};

const fileKindFromName = (name: string) => {
    const extension = name.split('.').pop()?.toLowerCase() || '';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) return 'image';
    if (extension === 'pdf') return 'pdf';
    if (['doc', 'docx'].includes(extension)) return 'word';
    if (['xls', 'xlsx', 'csv'].includes(extension)) return 'excel';
    return 'other';
};

const attachmentToneClasses = (kind: string) => {
    switch (kind) {
        case 'pdf':
            return 'border-red-200 bg-red-50 text-red-600 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-300';
        case 'word':
            return 'border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300';
        case 'excel':
            return 'border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300';
        case 'image':
            return 'border-violet-200 bg-violet-50 text-violet-600 dark:border-violet-500/40 dark:bg-violet-500/10 dark:text-violet-300';
        default:
            return 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-500/40 dark:bg-slate-500/10 dark:text-slate-300';
    }
};

const activityStatusClass = computed(() => {
    switch (props.activity.status) {
        case 'Completed':
            return 'border border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/20 dark:text-emerald-200';
        case 'In Progress':
            return 'border border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-400/40 dark:bg-blue-500/20 dark:text-blue-200';
        case 'Cancelled':
            return 'border border-red-300 bg-red-100 text-red-800 dark:border-red-400/40 dark:bg-red-500/20 dark:text-red-200';
        case 'Archived':
            return 'border border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-400/40 dark:bg-slate-500/20 dark:text-slate-200';
        default:
            return 'border border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-200';
    }
});

const cooperativeStatusClass = (status: string | null | undefined) => {
    switch (status) {
        case 'Active':
            return 'border border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/20 dark:text-emerald-200';
        case 'Inactive':
            return 'border border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-400/40 dark:bg-slate-500/20 dark:text-slate-200';
        case 'Suspended':
            return 'border border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-200';
        case 'Dissolved':
            return 'border border-red-300 bg-red-100 text-red-800 dark:border-red-400/40 dark:bg-red-500/20 dark:text-red-200';
        default:
            return 'border border-border bg-muted text-foreground';
    }
};

const cooperativeClassificationLabel = (classification: string | null | undefined) => {
    if (!classification) return EMPTY_VALUE;
    return classification.charAt(0).toUpperCase() + classification.slice(1);
};

const textOrDash = (value: string | null | undefined) => {
    return value && value.trim() ? value : EMPTY_VALUE;
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <ClipboardList class="h-5 w-5" />
                            </div>
                            <div class="space-y-1">
                                <Badge variant="outline">Activity Details</Badge>
                                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ activity.title || 'Untitled Activity' }}</h1>
                                <p class="text-sm text-muted-foreground">Reference #{{ activity.id }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge class="text-sm font-semibold" :class="activityStatusClass">
                                {{ activity.status || EMPTY_VALUE }}
                            </Badge>
                            <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />
                                Back
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Basic Information</CardTitle>
                    <CardDescription>Core details and schedule of this activity.</CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <dl class="grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm text-muted-foreground">Activity Title</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.title) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Category / Type</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.category) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Status</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.status) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Responsible Officer</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.responsible_officer) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Start Date</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatDateLong(activity.date_started) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">End Date</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatDateLong(activity.date_ended) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Venue</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.venue) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Implementing Partner</dt>
                            <dd class="text-sm font-medium text-foreground">{{ textOrDash(activity.implementing_partner) }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-sm text-muted-foreground">Description / Objective</dt>
                            <dd class="whitespace-pre-wrap text-sm font-medium text-foreground">{{ textOrDash(activity.description) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Cooperatives Participating</CardTitle>
                    <CardDescription>
                        <Badge variant="secondary">{{ cooperatives.length }} Cooperatives Participating</Badge>
                    </CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <div v-if="cooperatives.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                        No cooperatives linked.
                    </div>
                    <div v-else class="divide-y divide-border/70 rounded-xl border border-border/70 bg-background">
                        <div v-for="coop in cooperatives" :key="coop.id" class="grid gap-3 p-4 md:grid-cols-[1.4fr_1fr_auto] md:items-center">
                            <div>
                                <p class="text-sm font-semibold text-foreground">{{ coop.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ coop.registration_number || EMPTY_VALUE }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge class="border border-blue-300 bg-blue-100 text-blue-800 dark:border-blue-400/40 dark:bg-blue-500/20 dark:text-blue-200">
                                    {{ coop.region || EMPTY_VALUE }}
                                </Badge>
                                <Badge variant="outline">
                                    {{ cooperativeClassificationLabel(coop.classification) }}
                                </Badge>
                            </div>
                            <div class="md:justify-self-end">
                                <Badge :class="cooperativeStatusClass(coop.status)">
                                    {{ coop.status || EMPTY_VALUE }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Budget & Beneficiaries</CardTitle>
                    <CardDescription>Financial figures and beneficiary counts.</CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <dt class="text-sm text-muted-foreground">Budget</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatMoney(activity.budget) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Actual Expense</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatMoney(activity.actual_expense) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Target Member Beneficiaries</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatCount(activity.target_member_beneficiaries) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Actual Member Beneficiaries</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatCount(activity.actual_member_beneficiaries) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Target Community Beneficiaries</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatCount(activity.target_community_beneficiaries) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-muted-foreground">Actual Community Beneficiaries</dt>
                            <dd class="text-sm font-medium text-foreground">{{ formatCount(activity.actual_community_beneficiaries) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Attachments / Supporting Documents</CardTitle>
                    <CardDescription>Download uploaded outcomes and funding source files.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6 pt-0">
                    <section class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Outcomes Attachments</h3>
                        <div v-if="outcomesAttachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Cloud class="h-4 w-4" />
                                No attachments uploaded
                            </div>
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="file in outcomesAttachments" :key="`outcomes-${file.path}`" class="flex flex-col gap-3 rounded-lg border border-border/70 bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border" :class="attachmentToneClasses(fileKindFromName(file.name))">
                                        <FileText v-if="fileKindFromName(file.name) === 'pdf' || fileKindFromName(file.name) === 'word'" class="h-5 w-5" />
                                        <FileSpreadsheet v-else-if="fileKindFromName(file.name) === 'excel'" class="h-5 w-5" />
                                        <Cloud v-else-if="fileKindFromName(file.name) === 'image'" class="h-5 w-5" />
                                        <File v-else class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatAttachmentSize(file.size) }}</p>
                                    </div>
                                </div>
                                <Button v-if="file.url" as-child variant="outline" size="sm" class="gap-2">
                                    <a :href="file.url" target="_blank" rel="noreferrer">
                                        <Download class="h-4 w-4" />
                                        Download
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Funding Source Attachments</h3>
                        <div v-if="fundingAttachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Cloud class="h-4 w-4" />
                                No attachments uploaded
                            </div>
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="file in fundingAttachments" :key="`funding-${file.path}-${file.funding_source_id || 0}`" class="flex flex-col gap-3 rounded-lg border border-border/70 bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border" :class="attachmentToneClasses(fileKindFromName(file.name))">
                                        <FileText v-if="fileKindFromName(file.name) === 'pdf' || fileKindFromName(file.name) === 'word'" class="h-5 w-5" />
                                        <FileSpreadsheet v-else-if="fileKindFromName(file.name) === 'excel'" class="h-5 w-5" />
                                        <Cloud v-else-if="fileKindFromName(file.name) === 'image'" class="h-5 w-5" />
                                        <File v-else class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ formatAttachmentSize(file.size) }}
                                            <span v-if="file.funder_name"> • {{ file.funder_name }}</span>
                                        </p>
                                    </div>
                                </div>
                                <Button v-if="file.url" as-child variant="outline" size="sm" class="gap-2">
                                    <a :href="file.url" target="_blank" rel="noreferrer">
                                        <Download class="h-4 w-4" />
                                        Download
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </section>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Remarks / Additional Notes</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <p class="whitespace-pre-wrap text-sm text-foreground">
                        {{ activity.remarks && activity.remarks.trim() ? activity.remarks : 'No remarks added' }}
                    </p>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Button variant="outline" class="gap-2" type="button" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
                <Link :href="`/activities/${activity.id}/edit`">
                    <Button class="gap-2">
                        <Pencil class="h-4 w-4" />
                        Edit Activity
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
