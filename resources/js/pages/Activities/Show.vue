<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    ClipboardList,
    Download,
    Eye,
    FileX,
    Monitor,
    Pencil,
    Search,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    getFileExtension,
    getFileTypeConfig,
    getLegendFileTypeGroups,
    getPreviewSuggestion,
} from '@/lib/activityFileTypes';

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

interface PreviewUnavailableFile {
    name: string;
    url: string;
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

const legendGroups = getLegendFileTypeGroups();
const showPreviewUnavailableModal = ref(false);
const previewUnavailableFile = ref<PreviewUnavailableFile | null>(null);

const previewUnavailableFileConfig = computed(() => {
    if (!previewUnavailableFile.value) {
        return null;
    }

    return getFileTypeConfig(previewUnavailableFile.value.name);
});

const previewUnavailableSuggestion = computed(() => {
    if (!previewUnavailableFile.value) {
        return '';
    }

    return getPreviewSuggestion(previewUnavailableFile.value.name);
});

const openAttachmentPreview = (url: string) => {
    window.open(url, '_blank', 'noopener,noreferrer');
};

const downloadAttachment = (file: AttachmentItem) => {
    if (!file.url) return;

    const link = document.createElement('a');
    link.href = file.url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.download = file.name || 'attachment';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadFileFromUrl = (url: string, name: string) => {
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.download = name || 'attachment';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const handleAttachmentPreview = (file: AttachmentItem) => {
    if (!file.url) {
        return;
    }

    const config = getFileTypeConfig(file.name);
    if (config.previewable) {
        openAttachmentPreview(file.url);
        return;
    }

    previewUnavailableFile.value = { name: file.name, url: file.url };
    showPreviewUnavailableModal.value = true;
};

const closePreviewUnavailableModal = () => {
    showPreviewUnavailableModal.value = false;
    previewUnavailableFile.value = null;
};

const downloadPreviewUnavailableFile = () => {
    if (!previewUnavailableFile.value) {
        return;
    }

    downloadFileFromUrl(previewUnavailableFile.value.url, previewUnavailableFile.value.name);
    closePreviewUnavailableModal();
};

const cooperativeSearch = ref('');
const cooperativesScrollRef = ref<HTMLElement | null>(null);
const mobileCooperativesExpanded = ref(false);
const hasMoreCooperatives = ref(false);

const showCooperativeSearch = computed(() => props.cooperatives.length >= 5);

const filteredCooperatives = computed(() => {
    const keyword = cooperativeSearch.value.trim().toLowerCase();
    if (!keyword) {
        return props.cooperatives;
    }

    return props.cooperatives.filter((coop) => {
        return [
            coop.name,
            coop.registration_number,
            coop.region,
            coop.classification,
            coop.status,
        ].some((value) => (value || '').toLowerCase().includes(keyword));
    });
});

const mobileVisibleCooperatives = computed(() => {
    if (mobileCooperativesExpanded.value) {
        return filteredCooperatives.value;
    }

    return filteredCooperatives.value.slice(0, 3);
});

const hiddenMobileCooperativeCount = computed(() => Math.max(filteredCooperatives.value.length - 3, 0));

const updateCooperativeScrollHint = () => {
    const container = cooperativesScrollRef.value;
    if (!container) {
        hasMoreCooperatives.value = false;
        return;
    }

    hasMoreCooperatives.value = container.scrollTop + container.clientHeight < container.scrollHeight - 2;
};

onMounted(() => {
    nextTick(updateCooperativeScrollHint);
});

watch(filteredCooperatives, () => {
    nextTick(() => {
        if (cooperativesScrollRef.value) {
            cooperativesScrollRef.value.scrollTop = 0;
        }
        updateCooperativeScrollHint();
    });
});

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
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <CardTitle class="text-xl">Cooperatives Participating</CardTitle>
                        <div class="flex items-center gap-2">
                            <Badge class="border border-blue-200 bg-blue-100 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200">
                                {{ filteredCooperatives.length }} cooperatives
                            </Badge>
                            <span v-if="cooperatives.length >= 10" class="text-xs text-muted-foreground">Scroll to see all</span>
                        </div>
                    </div>
                    <CardDescription>Compact list of all assigned cooperatives for this activity.</CardDescription>
                </CardHeader>
                <CardContent class="pt-0">
                    <div v-if="cooperatives.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                        No cooperatives linked.
                    </div>
                    <div v-else class="space-y-3">
                        <div v-if="showCooperativeSearch" class="relative">
                            <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input v-model="cooperativeSearch" placeholder="Search cooperatives..." class="h-8 pl-9 text-sm" />
                        </div>

                        <div class="sm:hidden space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="coop in mobileVisibleCooperatives" :key="`mobile-${coop.id}`" variant="secondary" class="max-w-full truncate">
                                    {{ coop.name }}
                                </Badge>
                            </div>
                            <Button v-if="hiddenMobileCooperativeCount > 0 && !mobileCooperativesExpanded" type="button" variant="outline" size="sm" @click="mobileCooperativesExpanded = true">
                                +{{ hiddenMobileCooperativeCount }} more
                            </Button>
                        </div>

                        <div class="relative hidden sm:block">
                            <div ref="cooperativesScrollRef" class="max-h-80 overflow-y-auto rounded-xl border border-border/70 bg-background" @scroll="updateCooperativeScrollHint">
                                <table class="w-full border-collapse text-sm">
                                    <thead class="sticky top-0 z-10 bg-muted/70 backdrop-blur">
                                        <tr class="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                            <th class="px-3 py-2 font-medium">#</th>
                                            <th class="px-3 py-2 font-medium">Cooperative Name</th>
                                            <th class="px-3 py-2 font-medium">Region</th>
                                            <th class="px-3 py-2 font-medium">Classification</th>
                                            <th class="px-3 py-2 font-medium">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(coop, index) in filteredCooperatives" :key="coop.id" class="border-t border-border/60 odd:bg-background even:bg-muted/20 hover:bg-muted/40">
                                            <td class="px-3 py-2">{{ index + 1 }}</td>
                                            <td class="px-3 py-2">
                                                <p class="font-medium text-foreground">{{ coop.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ coop.registration_number || EMPTY_VALUE }}</p>
                                            </td>
                                            <td class="px-3 py-2">
                                                <Badge class="border border-blue-200 bg-blue-100 text-blue-700 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-200">
                                                    {{ coop.region || EMPTY_VALUE }}
                                                </Badge>
                                            </td>
                                            <td class="px-3 py-2">
                                                <Badge variant="outline" class="bg-muted/40">
                                                    {{ cooperativeClassificationLabel(coop.classification) }}
                                                </Badge>
                                            </td>
                                            <td class="px-3 py-2">
                                                <Badge :class="cooperativeStatusClass(coop.status)">
                                                    {{ coop.status || EMPTY_VALUE }}
                                                </Badge>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredCooperatives.length === 0">
                                            <td colspan="5" class="px-3 py-6 text-center text-sm text-muted-foreground">No cooperatives match your search.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="hasMoreCooperatives" class="pointer-events-none absolute bottom-0 left-0 right-0 h-8 bg-linear-to-t from-background to-transparent" />
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
                <CardContent class="pt-0">
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_14rem]">
                        <div class="space-y-6">
                            <section class="space-y-3">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Outcomes Attachments</h3>
                                <div v-if="outcomesAttachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                    No attachments uploaded
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="file in outcomesAttachments" :key="`outcomes-${file.path}`" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <component :is="getFileTypeConfig(file.name).icon" class="h-8 w-8 shrink-0" :class="getFileTypeConfig(file.name).iconColorClass" />
                                            <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getFileTypeConfig(file.name).badgeClass">
                                                {{ getFileExtension(file.name) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ formatAttachmentSize(file.size) }}</p>
                                            </div>
                                        </div>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-sky-200 bg-sky-50 px-2 text-xs text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30" @click="handleAttachmentPreview(file)">
                                                        <Eye class="h-3.5 w-3.5" />
                                                        Preview
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Preview file in new tab</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-green-200 bg-green-50 px-2 text-xs text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400" @click="downloadAttachment(file)">
                                                        <Download class="h-3.5 w-3.5" />
                                                        Download
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Download this file</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </div>
                            </section>

                            <section class="space-y-3">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Funding Source Attachments</h3>
                                <div v-if="fundingAttachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                    No attachments uploaded
                                </div>
                                <div v-else class="space-y-2">
                                    <div v-for="file in fundingAttachments" :key="`funding-${file.path}-${file.funding_source_id || 0}`" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <component :is="getFileTypeConfig(file.name).icon" class="h-8 w-8 shrink-0" :class="getFileTypeConfig(file.name).iconColorClass" />
                                            <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getFileTypeConfig(file.name).badgeClass">
                                                {{ getFileExtension(file.name) }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ formatAttachmentSize(file.size) }}
                                                    <span v-if="file.funder_name"> • {{ file.funder_name }}</span>
                                                </p>
                                            </div>
                                        </div>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-sky-200 bg-sky-50 px-2 text-xs text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30" @click="handleAttachmentPreview(file)">
                                                        <Eye class="h-3.5 w-3.5" />
                                                        Preview
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Preview file in new tab</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-green-200 bg-green-50 px-2 text-xs text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400" @click="downloadAttachment(file)">
                                                        <Download class="h-3.5 w-3.5" />
                                                        Download
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Download this file</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <aside class="rounded-lg border border-border/70 bg-muted/20 p-4 lg:border-l lg:pl-4">
                            <h4 class="mb-3 text-sm font-semibold text-muted-foreground">File Types</h4>
                            <div class="space-y-2">
                                <div v-for="group in legendGroups" :key="group.key" class="rounded-md border border-border/60 bg-background/70 px-2 py-2">
                                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">{{ group.label }}</p>
                                    <div class="flex items-center gap-2">
                                        <component :is="group.icon" class="h-8 w-8 shrink-0" :class="group.iconColorClass" />
                                        <Badge variant="outline" class="font-semibold" :class="group.badgeClass">{{ group.badgeText }}</Badge>
                                    </div>
                                </div>
                            </div>
                        </aside>
                    </div>
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

        <Dialog :open="showPreviewUnavailableModal" @update:open="(open: boolean) => !open && closePreviewUnavailableModal()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <FileX class="h-4 w-4 text-amber-500" />
                        Preview Not Available
                    </DialogTitle>
                    <DialogDescription>
                        This file type cannot be previewed in the browser.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="previewUnavailableFile && previewUnavailableFileConfig" class="space-y-3">
                    <div class="rounded-md border border-border/70 bg-muted/30 p-3">
                        <p class="truncate text-sm font-medium text-foreground" :title="previewUnavailableFile.name">{{ previewUnavailableFile.name }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <component :is="previewUnavailableFileConfig.icon" class="h-8 w-8" :class="previewUnavailableFileConfig.iconColorClass" />
                            <Badge variant="outline" class="font-semibold" :class="previewUnavailableFileConfig.badgeClass">{{ previewUnavailableFileConfig.extension }}</Badge>
                        </div>
                    </div>

                    <div class="rounded-md border border-amber-200 bg-amber-50 p-3 text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-300">
                        <p class="flex items-center gap-2 text-sm font-medium">
                            <AlertCircle class="h-4 w-4" />
                            Suggested app
                        </p>
                        <p class="mt-1 flex items-center gap-2 text-sm">
                            <Monitor class="h-4 w-4" />
                            {{ previewUnavailableSuggestion }}
                        </p>
                    </div>
                </div>

                <DialogFooter class="gap-2 sm:justify-end">
                    <Button type="button" variant="outline" @click="closePreviewUnavailableModal">Cancel</Button>
                    <Button type="button" class="gap-2" @click="downloadPreviewUnavailableFile">
                        <Download class="h-4 w-4" />
                        Download
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
