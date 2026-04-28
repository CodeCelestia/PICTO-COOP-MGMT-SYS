<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ClipboardList, Download, Eye, FileX, Lock, Monitor, Plus, Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
// Cancel navigation is handled explicitly below (no history.back/document.referrer)
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    getFileExtension,
    getFileTypeConfig,
    getLegendFileTypeGroups,
    getPreviewSuggestion,
} from '@/lib/activityFileTypes';
import { confirmAction, notifyError, notifySuccess } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string | null;
    region?: string | null;
    classification?: string | null;
}

interface OfficerOption {
    id: number;
    name: string | null;
    coop_id: number;
}

interface Props {
    cooperatives: Cooperative[];
    officers: OfficerOption[];
    isCooperativeContext?: boolean;
    contextCooperativeId?: number | null;
}

interface FundingSourceFormRow {
    id?: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string;
    amount_released: string;
    date_released: string;
    status: string;
    remarks: string;
    attachments: File[];
    is_saved?: boolean;
}

interface FileDisplayItem {
    name: string;
    sizeLabel: string;
    extension: string;
    previewUrl: string;
    pendingIndex: number;
}

interface PreviewUnavailableFile {
    name: string;
    url: string;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateActivity = computed(() => permissions.value.includes('create activities-&-projects'));
const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext && props.contextCooperativeId));
const lockedCooperativeId = computed(() => {
    if (!isCooperativeContext.value || !props.contextCooperativeId) return '';
    return String(props.contextCooperativeId);
});
const initialCooperativeIds = computed(() => {
    if (lockedCooperativeId.value) {
        return [lockedCooperativeId.value];
    }

    return [];
});

const form = useForm({
    coop_id: initialCooperativeIds.value[0] || '',
    coop_ids: [...initialCooperativeIds.value],
    title: '',
    description: '',
    category: 'Project',
    date_started: '',
    date_ended: '',
    status: 'Planned',
    responsible_officer_id: 'none',
    funding_source: '',
    budget: '',
    actual_expense: '',
    target_member_beneficiaries: '',
    target_community_beneficiaries: '',
    actual_member_beneficiaries: '',
    actual_community_beneficiaries: '',
    venue: '',
    implementing_partner: '',
    outcomes: '',
    outcomes_attachment: null as File | null,
    remarks: '',
    funding_sources: [] as FundingSourceFormRow[],
});

const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const statusOptions = ['Planned', 'In Progress', 'Completed', 'Archived', 'Cancelled'];
const funderTypeOptions = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const fundingStatusOptions = ['Released', 'Pending', 'Partially Released'];
const maxFundingSourceFiles = 3;
const MAX_FILE_SIZE_MB = 5;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const isCooperativeDialogOpen = ref(false);
const selectedCoopIds = ref<string[]>(form.coop_ids);
const activityFormRef = ref<HTMLFormElement | null>(null);
const cooperativeSectionRef = ref<HTMLElement | null>(null);
const fundingFileInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const outcomesFileInputRef = ref<HTMLInputElement | null>(null);
const fileObjectUrls = new Map<File, string>();
// Note: cancel() below will navigate to a validated return_to, cooperative show, or activities index.

const showFileSizeError = (fileName: string) => {
    notifyError(`"${fileName}" exceeds the ${MAX_FILE_SIZE_MB}MB limit. Please upload a smaller file.`);
};

const isFileTooLarge = (file: File) => {
    if (file.size <= MAX_FILE_SIZE_BYTES) {
        return false;
    }

    showFileSizeError(file.name);
    return true;
};

const selectedCooperatives = computed(() => {
    const selectedSet = new Set(selectedCoopIds.value);
    return props.cooperatives.filter((coop) => selectedSet.has(String(coop.id)));
});
const lockedCooperative = computed(() => {
    if (!lockedCooperativeId.value) return null;
    return props.cooperatives.find((coop) => String(coop.id) === lockedCooperativeId.value) || null;
});

const selectedCooperativeSummary = computed(() => {
    const count = selectedCoopIds.value.length;

    if (count === 0) return 'No cooperatives selected';
    if (count === 1) return selectedCooperatives.value[0]?.name || '1 cooperative selected';

    return `${count} cooperatives selected`;
});

const singleSelectedCoopId = computed(() => (
    selectedCoopIds.value.length === 1 ? selectedCoopIds.value[0] : ''
));

const syncSelectedCooperatives = (ids: string[]) => {
    if (lockedCooperativeId.value) {
        selectedCoopIds.value = [lockedCooperativeId.value];
        form.coop_ids = [lockedCooperativeId.value];
        form.coop_id = lockedCooperativeId.value;
        form.clearErrors('coop_id', 'coop_ids', 'responsible_officer_id');
        return;
    }

    selectedCoopIds.value = [...new Set(ids)];
    form.coop_ids = [...selectedCoopIds.value];
    form.coop_id = selectedCoopIds.value[0] || '';

    if (selectedCoopIds.value.length !== 1) {
        form.responsible_officer_id = 'none';
    }

    form.clearErrors('coop_id', 'coop_ids', 'responsible_officer_id');
};

const openCooperativePicker = () => {
    if (lockedCooperativeId.value || !props.cooperatives.length) return;
    isCooperativeDialogOpen.value = true;
};

watch(lockedCooperativeId, (newValue) => {
    if (!newValue) return;

    selectedCoopIds.value = [newValue];
    form.coop_ids = [newValue];
    form.coop_id = newValue;
    form.clearErrors('coop_id', 'coop_ids');
}, { immediate: true });

watch(singleSelectedCoopId, (newValue) => {
    form.coop_id = newValue;

    if (!newValue) {
        form.responsible_officer_id = 'none';
    }
});

const filteredOfficers = computed(() => {
    if (!singleSelectedCoopId.value) return [];
    return props.officers.filter((officer) => officer.coop_id.toString() === singleSelectedCoopId.value);
});

const isFundingSourceValid = (source: FundingSourceFormRow) => {
    return (
        source.funder_name.trim() !== '' &&
        source.funder_type.trim() !== '' &&
        source.amount_allocated !== '' &&
        source.amount_released !== '' &&
        source.status.trim() !== ''
    );
};

const saveFundingSource = (index: number) => {
    const source = form.funding_sources[index];
    if (!isFundingSourceValid(source)) {
        notifySuccess('Please fill all required funding source fields before saving.');
        return;
    }
    source.is_saved = true;
    notifySuccess('Funding source saved.');
};

const addFundingSource = () => {
    form.funding_sources.push({
        funder_name: '',
        funder_type: 'Government',
        amount_allocated: '',
        amount_released: '',
        date_released: '',
        status: 'Pending',
        remarks: '',
        attachments: [],
        is_saved: false,
    });
};

const setFundingFileInputRef = (index: number, element: HTMLInputElement | null) => {
    fundingFileInputRefs.value[index] = element;
};

const triggerFundingSourceFilePicker = (index: number) => {
    const source = form.funding_sources[index];
    if (!source) return;

    if (source.attachments.length >= maxFundingSourceFiles) {
        return;
    }

    fundingFileInputRefs.value[index]?.click();
};

const updateFundingSourceAttachment = (event: Event, index: number) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0];
    if (!nextFile) return;

    const source = form.funding_sources[index];
    if (!source) return;

    if (source.attachments.length >= maxFundingSourceFiles) {
        input.value = '';
        return;
    }

    if (isFileTooLarge(nextFile)) {
        input.value = '';
        return;
    }

    source.attachments.push(nextFile);
    input.value = '';
    notifySuccess('File added to funding source.');
};

const removeFundingSourceAttachment = async (index: number, fileIndex: number) => {
    const ok = await confirmAction({
        title: 'Remove file?',
        text: 'This will remove the selected file from this funding source.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;
    form.funding_sources[index].attachments.splice(fileIndex, 1);
};

const formatFileSize = (bytes: number) => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
};

const getAttachmentPreviewUrl = (file: File) => {
    const existing = fileObjectUrls.get(file);
    if (existing) {
        return existing;
    }

    const url = URL.createObjectURL(file);
    fileObjectUrls.set(file, url);
    return url;
};

const buildFileDisplayItem = (file: File, pendingIndex: number): FileDisplayItem => ({
    name: file.name,
    sizeLabel: formatFileSize(file.size),
    extension: getFileExtension(file.name),
    previewUrl: getAttachmentPreviewUrl(file),
    pendingIndex,
});

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

const handleAttachmentPreview = (name: string, url: string) => {
    const config = getFileTypeConfig(name);
    if (config.previewable) {
        openAttachmentPreview(url);
        return;
    }

    previewUnavailableFile.value = { name, url };
    showPreviewUnavailableModal.value = true;
};

const closePreviewUnavailableModal = () => {
    showPreviewUnavailableModal.value = false;
    previewUnavailableFile.value = null;
};

const downloadFromUrl = (url: string, name: string) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = name;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const downloadPreviewUnavailableFile = () => {
    if (!previewUnavailableFile.value) {
        return;
    }

    downloadFromUrl(previewUnavailableFile.value.url, previewUnavailableFile.value.name);
    closePreviewUnavailableModal();
};

onUnmounted(() => {
    fileObjectUrls.forEach((url) => URL.revokeObjectURL(url));
    fileObjectUrls.clear();
});

const fundingSourceFiles = (source: FundingSourceFormRow) => source.attachments
    .map((file, pendingIndex) => buildFileDisplayItem(file, pendingIndex));

const outcomesAttachment = computed(() => (
    form.outcomes_attachment ? buildFileDisplayItem(form.outcomes_attachment, 0) : null
));

const updateOutcomesAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0] || null;

    if (nextFile && isFileTooLarge(nextFile)) {
        if (input) input.value = '';
        return;
    }

    form.outcomes_attachment = nextFile;
    if (input) input.value = '';
    if (nextFile) notifySuccess('Outcomes attachment added.');
};

const triggerOutcomesFilePicker = () => {
    outcomesFileInputRef.value?.click();
};

const removeOutcomesAttachment = async () => {
    if (!form.outcomes_attachment) return;
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will clear the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;
    form.outcomes_attachment = null;
};

const removeFundingSource = async (index: number) => {
    const ok = await confirmAction({
        title: 'Delete funding source?',
        text: 'This will remove the entire funding source entry.',
        confirmButtonText: 'Delete',
    });
    if (!ok) return;
    form.funding_sources.splice(index, 1);
};

const scrollToFirstInvalidField = async () => {
    await nextTick();

    const firstInvalid = activityFormRef.value?.querySelector<HTMLElement>(':invalid');
    if (!firstInvalid) return;

    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    firstInvalid.focus();
};

const scrollToCooperativeSection = async () => {
    await nextTick();

    cooperativeSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const picker = document.getElementById('coop_picker');
    picker?.focus();
};

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const returnToHref = computed(() => {
    const href = queryParams.value.get('return_to') || '';
    if (!href || !href.startsWith('/') || href.startsWith('//')) return '';
    return href;
});

const submit = async () => {
    if (!canCreateActivity.value) return;

    if (activityFormRef.value && !activityFormRef.value.reportValidity()) {
        await scrollToFirstInvalidField();
        return;
    }

    if (!selectedCoopIds.value.length) {
        form.setError('coop_ids', 'Please select at least one cooperative.');
        await scrollToCooperativeSection();
        return;
    }

    if (selectedCoopIds.value.length !== 1) {
        form.responsible_officer_id = 'none';
    }

    form.transform((data) => ({
        ...data,
        coop_id: selectedCoopIds.value[0] || '',
        coop_ids: [...selectedCoopIds.value],
        return_to: returnToHref.value,
        responsible_officer_id: data.responsible_officer_id === 'none' ? '' : data.responsible_officer_id,
        funding_source: data.funding_source || data.funding_sources[0]?.funder_name || '',
        funding_sources: data.funding_sources.map((source) => ({
            ...source,
            amount_allocated: source.amount_allocated || null,
            amount_released: source.amount_released || null,
            date_released: source.date_released || null,
            remarks: source.remarks || null,
            attachments: source.attachments,
        })),
        outcomes_attachment: data.outcomes_attachment || null,
    })).post('/activities', {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Activity saved successfully.');
        },
        onError: async (errors) => {
            if (errors.coop_ids || errors.coop_id) {
                await scrollToCooperativeSection();
            } else {
                await scrollToFirstInvalidField();
            }

            const firstError = Object.values(errors)[0];
            notifyError(firstError || 'Unable to save activity. Please check the form and try again.');
        },
    });
};

const cancel = () => {
    // Prefer a validated return_to if provided, otherwise use cooperative context or global index
    const params = new URLSearchParams(page.url.split('?')[1] || '');
    const returnTo = params.get('return_to');

    const isValidReturnTo = (href: string | null) => {
        if (!href) return false;
        try {
            const url = new URL(href, window.location.origin);
            return url.origin === window.location.origin && url.pathname.startsWith('/');
        } catch (e) {
            return false;
        }
    };

    if (isValidReturnTo(returnTo)) {
        router.get(returnTo as string);
        return;
    }

    if (isCooperativeContext) {
        const coopId = lockedCooperative.value?.id || (selectedCoopIds.value.length ? selectedCoopIds.value[0] : null);
        if (coopId) {
            router.get(`/cooperatives/${coopId}`);
            return;
        }
    }

    // fallback to activities index (hardcoded)
    router.get('/activities');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <ClipboardList class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <Badge variant="outline" class="mb-2">Activities &amp; Projects</Badge>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Activity</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Record a cooperative activity or project.</p>
                        </div>
                        <!-- Back removed per UX rules for Create pages -->
                    </div>
                </CardContent>
            </Card>

            <form ref="activityFormRef" @submit.prevent="submit" class="space-y-6">
                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <ClipboardList class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>Enter the activity details, schedule, and status.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="title">Title <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" required placeholder="Enter activity title" :class="{ 'border-red-500': form.errors.title }" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <Label for="category">Category <span class="text-red-500">*</span></Label>
                                <Select v-model="form.category">
                                    <SelectTrigger id="category" :class="{ 'border-red-500': form.errors.category }">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in categoryOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="mt-1 text-sm text-red-500">{{ form.errors.category }}</p>
                            </div>
                            <div>
                                <Label for="status">Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                            </div>
                            <div>
                                <Label for="date_started">Start Date</Label>
                                <Input id="date_started" v-model="form.date_started" type="date" :class="{ 'border-red-500': form.errors.date_started }" />
                                <p v-if="form.errors.date_started" class="mt-1 text-sm text-red-500">{{ form.errors.date_started }}</p>
                            </div>
                            <div>
                                <Label for="date_ended">End Date</Label>
                                <Input id="date_ended" v-model="form.date_ended" type="date" :class="{ 'border-red-500': form.errors.date_ended }" />
                                <p v-if="form.errors.date_ended" class="mt-1 text-sm text-red-500">{{ form.errors.date_ended }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="description">Description / Objective</Label>
                                <Textarea id="description" v-model="form.description" placeholder="Brief description or objective of the activity" />
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                            </div>
                            <div>
                                <Label for="venue">Venue</Label>
                                <Input id="venue" v-model="form.venue" placeholder="Enter venue or location" :class="{ 'border-red-500': form.errors.venue }" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">{{ form.errors.venue }}</p>
                            </div>
                            <div>
                                <Label for="implementing_partner">Implementing Partner</Label>
                                <Input id="implementing_partner" v-model="form.implementing_partner" placeholder="Enter implementing partner" :class="{ 'border-red-500': form.errors.implementing_partner }" />
                                <p v-if="form.errors.implementing_partner" class="mt-1 text-sm text-red-500">{{ form.errors.implementing_partner }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Cooperative</CardTitle>
                        <CardDescription>Select the cooperatives this activity belongs to.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div ref="cooperativeSectionRef">
                            <Label for="coop_picker">Cooperatives</Label>
                            <Button
                                id="coop_picker"
                                type="button"
                                variant="outline"
                                :disabled="isCooperativeContext"
                                class="h-11 w-full items-center justify-between"
                                :class="[
                                    { 'border-red-500 focus-visible:ring-red-500': form.errors.coop_ids || form.errors.coop_id },
                                    isCooperativeContext ? 'cursor-not-allowed bg-muted opacity-60' : '',
                                ]"
                                @click="openCooperativePicker"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <Lock v-if="isCooperativeContext" class="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span class="truncate text-left">{{ isCooperativeContext ? (lockedCooperative?.name || selectedCooperativeSummary) : selectedCooperativeSummary }}</span>
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">
                                    {{ isCooperativeContext ? 'Locked' : (selectedCoopIds.length ? `${selectedCoopIds.length} selected` : 'Select') }}
                                </span>
                            </Button>
                            <p v-if="isCooperativeContext" class="mt-1 text-xs text-muted-foreground">
                                Cooperative is automatically set based on your current context.
                            </p>
                            <div v-if="selectedCooperatives.length" class="mt-3 flex flex-wrap gap-1.5">
                                <Badge v-for="coop in selectedCooperatives.slice(0, 4)" :key="coop.id" variant="secondary" class="max-w-full truncate">
                                    {{ coop.name }}
                                </Badge>
                                <Badge v-if="selectedCooperatives.length > 4" variant="outline">+{{ selectedCooperatives.length - 4 }} more</Badge>
                            </div>
                            <p v-if="!isCooperativeContext && selectedCoopIds.length > 1" class="mt-2 text-xs text-muted-foreground">
                                Responsible officer is available only when one cooperative is selected.
                            </p>
                            <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">{{ form.errors.coop_id }}</p>
                            <p v-if="form.errors.coop_ids" class="mt-1 text-sm text-red-500">{{ form.errors.coop_ids }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Budget & Beneficiaries</CardTitle>
                        <CardDescription>Capture the budget, expense, and beneficiary counts.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Budget & Expense</h3>
                                    <p class="mt-1 text-xs text-muted-foreground">Financial values for the activity.</p>
                                </div>
                                <div class="grid gap-4">
                                    <div>
                                        <Label for="budget">Budget</Label>
                                        <Input id="budget" v-model="form.budget" type="number" min="0" step="0.01" placeholder="0.00" />
                                        <p v-if="form.errors.budget" class="mt-1 text-sm text-red-500">{{ form.errors.budget }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_expense">Actual Expense</Label>
                                        <Input id="actual_expense" v-model="form.actual_expense" type="number" min="0" step="0.01" placeholder="0.00" />
                                        <p v-if="form.errors.actual_expense" class="mt-1 text-sm text-red-500">{{ form.errors.actual_expense }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Beneficiaries</h3>
                                    <p class="mt-1 text-xs text-muted-foreground">Target and actual beneficiary counts.</p>
                                </div>
                                <div class="grid gap-4">
                                    <div>
                                        <Label for="target_member_beneficiaries">Target Member Beneficiaries</Label>
                                        <Input id="target_member_beneficiaries" v-model="form.target_member_beneficiaries" type="number" min="0" placeholder="0" />
                                        <p v-if="form.errors.target_member_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.target_member_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_member_beneficiaries">Actual Member Beneficiaries</Label>
                                        <Input id="actual_member_beneficiaries" v-model="form.actual_member_beneficiaries" type="number" min="0" placeholder="0" />
                                        <p v-if="form.errors.actual_member_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.actual_member_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="target_community_beneficiaries">Target Community Beneficiaries</Label>
                                        <Input id="target_community_beneficiaries" v-model="form.target_community_beneficiaries" type="number" min="0" placeholder="0" />
                                        <p v-if="form.errors.target_community_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.target_community_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_community_beneficiaries">Actual Community Beneficiaries</Label>
                                        <Input id="actual_community_beneficiaries" v-model="form.actual_community_beneficiaries" type="number" min="0" placeholder="0" />
                                        <p v-if="form.errors.actual_community_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.actual_community_beneficiaries }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Attachments / Supporting Documents</CardTitle>
                        <CardDescription>Add the main attachment and supporting funding source files.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_14rem]">
                            <div class="space-y-6">
                                <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-muted-foreground">
                                            <Upload class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-foreground">Outcomes Attachment</h3>
                                            <p class="text-xs text-muted-foreground">PDF, Word, Excel, presentation, or image files are supported.</p>
                                        </div>
                                    </div>
                                    <input ref="outcomesFileInputRef" type="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" @change="updateOutcomesAttachment" />
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Button type="button" variant="outline" size="sm" class="gap-1" @click="triggerOutcomesFilePicker">
                                            <Plus class="h-3.5 w-3.5" />
                                            Add File
                                        </Button>
                                        <span class="text-xs text-muted-foreground">Maximum file size: {{ MAX_FILE_SIZE_MB }}MB per file</span>
                                    </div>
                                    <div v-if="!outcomesAttachment" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                        No file added yet. Use the file input above to add a supporting document.
                                    </div>
                                    <div v-else class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <component :is="getFileTypeConfig(outcomesAttachment.name).icon" class="h-8 w-8 shrink-0" :class="getFileTypeConfig(outcomesAttachment.name).iconColorClass" />
                                            <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getFileTypeConfig(outcomesAttachment.name).badgeClass">
                                                {{ outcomesAttachment.extension }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-foreground" :title="outcomesAttachment.name">{{ outcomesAttachment.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ outcomesAttachment.sizeLabel }}</p>
                                            </div>
                                        </div>
                                        <TooltipProvider>
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-sky-200 bg-sky-50 px-2 text-xs text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30" @click="handleAttachmentPreview(outcomesAttachment.name, outcomesAttachment.previewUrl)">
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
                                                    <Button type="button" size="sm" class="h-7 gap-1 border border-red-200 bg-red-50 px-2 text-xs text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" @click="removeOutcomesAttachment">
                                                        <Trash2 class="h-3.5 w-3.5" />
                                                        Remove
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>Remove this file</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </div>

                                <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Funding Source Attachments</h3>
                                            <p class="mt-1 text-xs text-muted-foreground">Each funding source can include supporting files.</p>
                                        </div>
                                        <Button type="button" variant="outline" class="gap-2" @click="addFundingSource">
                                            <Plus class="h-4 w-4" />
                                            Add Funding Source
                                        </Button>
                                    </div>

                                    <div v-if="form.funding_sources.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                        No funding sources yet. Add one above to attach supporting files.
                                    </div>

                                    <div v-else class="space-y-4">
                                        <Card v-for="(source, index) in form.funding_sources" :key="index" class="border-border/70 bg-background shadow-none">
                                            <CardContent class="space-y-4 p-4">
                                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                            <div>
                                                <Label :for="`funding_name_${index}`">Funding Source Name <span class="text-red-500">*</span></Label>
                                                <Input :id="`funding_name_${index}`" v-model="source.funder_name" placeholder="e.g., DA Region V" />
                                            </div>
                                            <div>
                                                <Label :for="`funding_type_${index}`">Type <span class="text-red-500">*</span></Label>
                                                <Select v-model="source.funder_type">
                                                    <SelectTrigger :id="`funding_type_${index}`">
                                                        <SelectValue placeholder="Select type" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="option in funderTypeOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                            <div>
                                                <Label :for="`funding_status_${index}`">Status <span class="text-red-500">*</span></Label>
                                                <Select v-model="source.status">
                                                    <SelectTrigger :id="`funding_status_${index}`">
                                                        <SelectValue placeholder="Select status" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem v-for="option in fundingStatusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <Label :for="`funding_allocated_${index}`">Amount Allocated</Label>
                                                <Input :id="`funding_allocated_${index}`" v-model="source.amount_allocated" type="number" min="0" step="0.01" placeholder="0.00" />
                                            </div>
                                            <div>
                                                <Label :for="`funding_released_${index}`">Amount Released</Label>
                                                <Input :id="`funding_released_${index}`" v-model="source.amount_released" type="number" min="0" step="0.01" placeholder="0.00" />
                                            </div>
                                        </div>

                                        <div>
                                            <Label :for="`funding_remarks_${index}`">Notes</Label>
                                            <Input :id="`funding_remarks_${index}`" v-model="source.remarks" placeholder="Optional notes" />
                                        </div>

                                            <div>
                                                <Label class="mb-2 block">Files</Label>
                                                <div class="rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                                                    <input :ref="(el) => setFundingFileInputRef(index, el as HTMLInputElement | null)" type="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" @change="updateFundingSourceAttachment($event, index)" />

                                                    <div class="mb-3 flex flex-wrap items-center gap-2">
                                                        <Button v-if="source.attachments.length < maxFundingSourceFiles" type="button" variant="outline" size="sm" class="gap-1" @click="triggerFundingSourceFilePicker(index)">
                                                            <Plus class="h-3.5 w-3.5" />
                                                            Add File
                                                        </Button>
                                                        <span class="text-xs text-muted-foreground">{{ source.attachments.length }} of {{ maxFundingSourceFiles }} files added</span>
                                                        <span class="text-xs text-muted-foreground">Maximum file size: {{ MAX_FILE_SIZE_MB }}MB per file</span>
                                                    </div>

                                                    <div v-if="source.attachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                                        No files added yet.
                                                    </div>

                                                    <div v-else class="space-y-3">
                                                        <div v-for="file in fundingSourceFiles(source)" :key="`${file.name}-${file.pendingIndex}`" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
                                                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                                                <component :is="getFileTypeConfig(file.name).icon" class="h-8 w-8 shrink-0" :class="getFileTypeConfig(file.name).iconColorClass" />
                                                                <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getFileTypeConfig(file.name).badgeClass">
                                                                    {{ file.extension }}
                                                                </span>
                                                                <div class="min-w-0">
                                                                    <p class="truncate font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                                                    <p class="text-xs text-muted-foreground">{{ file.sizeLabel }}</p>
                                                                </div>
                                                            </div>
                                                            <TooltipProvider>
                                                                <Tooltip>
                                                                    <TooltipTrigger as-child>
                                                                        <Button type="button" size="sm" class="h-7 gap-1 border border-sky-200 bg-sky-50 px-2 text-xs text-sky-700 hover:bg-sky-100 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-400 dark:hover:bg-sky-900/30" @click="handleAttachmentPreview(file.name, file.previewUrl)">
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
                                                                        <Button type="button" size="sm" class="h-7 gap-1 border border-red-200 bg-red-50 px-2 text-xs text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" @click="removeFundingSourceAttachment(index, file.pendingIndex)">
                                                                            <Trash2 class="h-3.5 w-3.5" />
                                                                            Remove
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>Remove this file</TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                                                <span v-if="source.is_saved" class="inline-flex items-center rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">Saved</span>
                                                <div class="flex flex-wrap gap-2">
                                                    <Button v-if="!source.is_saved" type="button" variant="secondary" size="sm" class="gap-1" @click="saveFundingSource(index)">
                                                        Save
                                                    </Button>
                                                    <Button type="button" variant="outline" size="sm" @click="removeFundingSource(index)">
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>

                                <p v-if="form.errors.funding_sources" class="mt-1 text-sm text-red-500">{{ form.errors.funding_sources }}</p>
                            </div>
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
                        <CardTitle class="text-xl">Additional Notes / Remarks</CardTitle>
                        <CardDescription>Capture any remaining notes and summary remarks.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div>
                            <Label for="remarks">Remarks</Label>
                            <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                            <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">{{ form.errors.remarks }}</p>
                        </div>
                        <div>
                            <Label for="responsible_officer_id">Responsible Officer</Label>
                            <Select v-model="form.responsible_officer_id" :disabled="!singleSelectedCoopId">
                                <SelectTrigger id="responsible_officer_id" :class="{ 'border-red-500': form.errors.responsible_officer_id }">
                                    <SelectValue placeholder="Select officer" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No officer</SelectItem>
                                    <SelectItem v-for="officer in filteredOfficers" :key="officer.id" :value="officer.id.toString()">{{ officer.name || 'Unknown' }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.responsible_officer_id" class="mt-1 text-sm text-red-500">{{ form.errors.responsible_officer_id }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="cancel" type="button" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Cancel
                    </Button>
                    <Button v-if="canCreateActivity" type="submit" :disabled="form.processing" class="gap-2">
                        <Save class="h-4 w-4" />
                        Save Activity
                    </Button>
                </div>
            </form>
        </div>

        <CooperativeMultiSelectDialog
            v-if="!isCooperativeContext"
            :open="isCooperativeDialogOpen"
            :cooperatives="cooperatives"
            :selected-ids="selectedCoopIds"
            title="Choose Cooperatives"
            description="Search and filter cooperatives, then choose one or more entries for this activity."
            confirm-label="Confirm"
            cancel-label="Cancel"
            @update:open="(value) => isCooperativeDialogOpen = value"
            @confirm="syncSelectedCooperatives"
        />

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
