<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Cloud, File, FileSpreadsheet, FileText, Lock, Plus, Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { useCoopLabel } from '@/composables/useCoopLabel';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction, notifyError, notifySuccess } from '@/lib/alerts';
import { dateInputValue } from '@/utils/date';

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

interface Activity {
    id: number;
    coop_id: number;
    title: string;
    description: string | null;
    category: string;
    date_started: string | null;
    date_ended: string | null;
    status: string;
    responsible_officer_id: number | null;
    funding_source: string | null;
    budget: string | null;
    actual_expense: string | null;
    target_member_beneficiaries: number | null;
    target_community_beneficiaries: number | null;
    actual_member_beneficiaries: number | null;
    actual_community_beneficiaries: number | null;
    venue: string | null;
    implementing_partner: string | null;
    outcomes: string | null;
    outcomes_attachment_path?: string | null;
    remarks: string | null;
    funding_sources?: FundingSourceRecord[];
}

interface FundingSourceRecord {
    id: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string | null;
    amount_released: string | null;
    date_released: string | null;
    status: string;
    remarks: string | null;
    attachment_paths?: string[] | null;
    attachment_names?: string[] | null;
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
    attachment_paths?: string[] | null;
    attachment_names?: string[] | null;
    attachments_removed?: string[];
    is_saved?: boolean;
}

type AttachmentKind = 'pdf' | 'word' | 'excel' | 'image' | 'other';

interface FileDisplayItem {
    name: string;
    sizeLabel: string;
    kind: AttachmentKind;
    previewUrl: string;
    pendingIndex?: number;
    url?: string;
    path?: string;
    isExisting: boolean;
}

interface Props {
    activity: Activity;
    cooperatives: Cooperative[];
    officers: OfficerOption[];
    isCooperativeContext?: boolean;
    contextCooperativeId?: number | null;
    assigned_coop_ids?: number[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateActivity = computed(() => permissions.value.includes('update activities-&-projects'));
const { cooperativeLabel } = useCoopLabel();

const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext && props.contextCooperativeId));
const lockedCooperativeId = computed(() => {
    if (!isCooperativeContext.value || !props.contextCooperativeId) return '';
    return String(props.contextCooperativeId);
});

const normalizeDateInput = (value: string | null | undefined) => dateInputValue(value);

const form = useForm({
    coop_id: String(props.activity.coop_id),
    coop_ids: (props.assigned_coop_ids && props.assigned_coop_ids.length > 0)
        ? [...new Set(props.assigned_coop_ids.map((id) => String(id)))]
        : [String(props.activity.coop_id)],
    title: props.activity.title,
    description: props.activity.description || '',
    category: props.activity.category,
    date_started: normalizeDateInput(props.activity.date_started),
    date_ended: normalizeDateInput(props.activity.date_ended),
    status: props.activity.status || 'Planned',
    responsible_officer_id: props.activity.responsible_officer_id?.toString() || 'none',
    funding_source: props.activity.funding_source || '',
    budget: props.activity.budget || '',
    actual_expense: props.activity.actual_expense || '',
    target_member_beneficiaries: props.activity.target_member_beneficiaries?.toString() || '',
    target_community_beneficiaries: props.activity.target_community_beneficiaries?.toString() || '',
    actual_member_beneficiaries: props.activity.actual_member_beneficiaries?.toString() || '',
    actual_community_beneficiaries: props.activity.actual_community_beneficiaries?.toString() || '',
    venue: props.activity.venue || '',
    implementing_partner: props.activity.implementing_partner || '',
    outcomes: props.activity.outcomes || '',
    outcomes_attachment: null as File | null,
    outcomes_attachment_removed: false,
    remarks: props.activity.remarks || '',
    funding_sources: (props.activity.funding_sources || []).map((source) => ({
        id: source.id,
        funder_name: source.funder_name,
        funder_type: source.funder_type,
        amount_allocated: source.amount_allocated || '',
        amount_released: source.amount_released || '',
        date_released: normalizeDateInput(source.date_released),
        status: source.status,
        remarks: source.remarks || '',
        attachments: [],
        attachment_paths: source.attachment_paths || [],
        attachment_names: source.attachment_names || [],
        attachments_removed: [],
        is_saved: true,
    })) as FundingSourceFormRow[],
});

const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const statusOptions = ['Planned', 'In Progress', 'Completed', 'Archived', 'Cancelled'];
const funderTypeOptions = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const fundingStatusOptions = ['Released', 'Pending', 'Partially Released'];
const maxFundingSourceFiles = 3;
const MAX_FILE_SIZE_MB = 5;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const fundingFileInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const outcomesFileInputRef = ref<HTMLInputElement | null>(null);

const initialSelectedCoopIds = computed(() => {
    if (lockedCooperativeId.value) {
        return [lockedCooperativeId.value];
    }

    const validAssignedCoopIds = (props.assigned_coop_ids || [])
        .map((id) => String(id))
        .filter((id) => props.cooperatives.some((coop) => String(coop.id) === id));

    if (validAssignedCoopIds.length > 0) {
        return [...new Set(validAssignedCoopIds)];
    }

    return form.coop_id ? [form.coop_id] : [];
});

const selectedCoopIds = ref<string[]>(initialSelectedCoopIds.value);
const isCooperativeDialogOpen = ref(false);

// ✅ FIX: Always navigate to the activities index, never use document.referrer
const goBack = () => {
    router.get('/activities');
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

    if (count === 0) return 'Choose Cooperative(s)';
    if (count === 1) return selectedCooperatives.value[0]?.name || '1 cooperative selected';

    return `${count} cooperatives selected`;
});

const singleSelectedCoopId = computed(() => (
    selectedCoopIds.value.length === 1 ? selectedCoopIds.value[0] : ''
));

const syncSelectedCooperatives = (ids: string[]) => {
    if (lockedCooperativeId.value) {
        selectedCoopIds.value = [lockedCooperativeId.value];
        form.coop_id = lockedCooperativeId.value;
        form.coop_ids = [lockedCooperativeId.value];
        form.clearErrors('coop_id', 'responsible_officer_id');
        return;
    }

    selectedCoopIds.value = [...new Set(ids)];
    form.coop_id = selectedCoopIds.value[0] || '';
    form.coop_ids = [...selectedCoopIds.value];

    if (selectedCoopIds.value.length !== 1) {
        form.responsible_officer_id = 'none';
    }

    form.clearErrors('coop_id', 'responsible_officer_id');
};

const openCooperativePicker = () => {
    if (lockedCooperativeId.value || !props.cooperatives.length) return;
    isCooperativeDialogOpen.value = true;
};

watch(lockedCooperativeId, (newValue) => {
    if (!newValue) return;

    selectedCoopIds.value = [newValue];
    form.coop_id = newValue;
    form.coop_ids = [newValue];
    form.clearErrors('coop_id');
}, { immediate: true });

watch(selectedCoopIds, (ids) => {
    form.coop_id = ids[0] || '';
    form.coop_ids = [...ids];

    if (ids.length !== 1) {
        form.responsible_officer_id = 'none';
    }

    form.clearErrors('coop_id', 'coop_ids');
});

const filteredOfficers = computed(() => {
    if (!singleSelectedCoopId.value) return [];
    return props.officers.filter((officer) => officer.coop_id.toString() === singleSelectedCoopId.value);
});

const isFundingSourceValid = (source: FundingSourceFormRow) => (
    source.funder_name.trim() !== '' &&
    source.funder_type.trim() !== '' &&
    source.amount_allocated !== '' &&
    source.amount_released !== '' &&
    source.status.trim() !== ''
);

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
        attachment_paths: [],
        attachment_names: [],
        attachments_removed: [],
        is_saved: false,
    });
};

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

const setFundingFileInputRef = (index: number, element: HTMLInputElement | null) => {
    fundingFileInputRefs.value[index] = element;
};

const triggerFundingSourceFilePicker = (index: number) => {
    const source = form.funding_sources[index];
    if (!source) return;

    const existingCount = source.attachment_names?.length || 0;
    if (existingCount + source.attachments.length >= maxFundingSourceFiles) {
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

    const existingCount = source.attachment_names?.length || 0;
    if (existingCount + source.attachments.length >= maxFundingSourceFiles) {
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

const removeExistingFundingSourceFile = async (index: number, path: string) => {
    const ok = await confirmAction({
        title: 'Remove file?',
        text: 'This will remove the file from this funding source.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    const source = form.funding_sources[index];
    const pathIndex = source.attachment_paths?.indexOf(path) ?? -1;
    if (pathIndex === -1) return;

    const removedPath = source.attachment_paths?.[pathIndex];
    if (removedPath) {
        source.attachments_removed = [...(source.attachments_removed || []), removedPath];
    }

    source.attachment_paths?.splice(pathIndex, 1);
    source.attachment_names?.splice(pathIndex, 1);
};

const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
};

const fileKindFromName = (name: string): AttachmentKind => {
    const extension = name.split('.').pop()?.toLowerCase() || '';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) return 'image';
    if (extension === 'pdf') return 'pdf';
    if (['doc', 'docx'].includes(extension)) return 'word';
    if (['xls', 'xlsx'].includes(extension)) return 'excel';
    return 'other';
};

const getAttachmentPreviewUrl = (file: File) => (fileKindFromName(file.name) === 'image' ? URL.createObjectURL(file) : '');

const getAttachmentToneClasses = (kind: AttachmentKind) => {
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

const fundingSourceFiles = (source: FundingSourceFormRow): FileDisplayItem[] => {
    const existing = (source.attachment_names || []).map((name, idx) => {
        const path = source.attachment_paths?.[idx] || '';
        return {
            name,
            sizeLabel: 'Saved file',
            kind: fileKindFromName(name),
            previewUrl: fileKindFromName(name) === 'image' && path ? `/storage/${path}` : '',
            url: path ? `/storage/${path}` : undefined,
            path,
            isExisting: true,
        };
    });

    const pending: FileDisplayItem[] = source.attachments.reduce((items, file, pendingIndex) => {
        if (!file) {
            return items;
        }

        items.push({
            name: file.name,
            sizeLabel: formatFileSize(file.size),
            kind: fileKindFromName(file.name),
            previewUrl: getAttachmentPreviewUrl(file),
            pendingIndex,
            isExisting: false,
        });

        return items;
    }, [] as FileDisplayItem[]);

    return [...existing, ...pending];
};

const outcomesExistingName = computed(() => {
    const path = props.activity.outcomes_attachment_path;
    return path ? path.split('/').pop() || 'Outcomes attachment' : '';
});

const outcomesExistingFile = computed<FileDisplayItem | null>(() => {
    const path = props.activity.outcomes_attachment_path;
    if (!path || form.outcomes_attachment_removed || form.outcomes_attachment) return null;

    const name = outcomesExistingName.value;
    return {
        name,
        sizeLabel: 'Saved file',
        kind: fileKindFromName(name),
        previewUrl: fileKindFromName(name) === 'image' ? `/storage/${path}` : '',
        url: `/storage/${path}`,
        path,
        isExisting: true,
    };
});

const outcomesNewFile = computed<FileDisplayItem | null>(() => {
    if (!form.outcomes_attachment) return null;

    return {
        name: form.outcomes_attachment.name,
        sizeLabel: formatFileSize(form.outcomes_attachment.size),
        kind: fileKindFromName(form.outcomes_attachment.name),
        previewUrl: getAttachmentPreviewUrl(form.outcomes_attachment),
        pendingIndex: 0,
        isExisting: false,
    };
});

const updateOutcomesAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0] || null;

    if (nextFile && isFileTooLarge(nextFile)) {
        if (input) input.value = '';
        return;
    }

    form.outcomes_attachment = nextFile;
    if (input) input.value = '';

    if (nextFile) {
        form.outcomes_attachment_removed = false;
        notifySuccess('Outcomes attachment added.');
    }
};

const triggerOutcomesFilePicker = () => {
    outcomesFileInputRef.value?.click();
};

const removeOutcomesAttachment = async () => {
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will clear the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    form.outcomes_attachment = null;
    form.outcomes_attachment_removed = true;
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

const submit = () => {
    if (!canUpdateActivity.value) return;

    if (!form.coop_id) {
        form.setError('coop_id', 'Please select a cooperative.');
        return;
    }

    form.transform((data) => ({
        ...data,
        coop_id: selectedCoopIds.value[0] || '',
        coop_ids: [...selectedCoopIds.value],
        responsible_officer_id: data.responsible_officer_id === 'none' ? '' : data.responsible_officer_id,
        funding_source: data.funding_source || data.funding_sources[0]?.funder_name || '',
        funding_sources: data.funding_sources.map((source) => ({
            ...source,
            amount_allocated: source.amount_allocated || null,
            amount_released: source.amount_released || null,
            date_released: source.date_released || null,
            remarks: source.remarks || null,
            attachments: source.attachments.filter((file): file is File => Boolean(file)),
            attachments_removed: source.attachments_removed || [],
        })),
        outcomes_attachment: data.outcomes_attachment || null,
        outcomes_attachment_removed: data.outcomes_attachment_removed || false,
    })).put(`/activities/${props.activity.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Activity updated successfully.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            notifyError(firstError || 'Unable to update activity. Please check the form and try again.');
        },
    });
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
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Edit Activity</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Update a cooperative activity or project.</p>
                        </div>
                        <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6">
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
                                <Input id="title" v-model="form.title" placeholder="Enter activity title" :class="{ 'border-red-500': form.errors.title }" />
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
                        <CardDescription>Select the cooperative this activity belongs to.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div>
                            <Label for="coop_picker">{{ cooperativeLabel }}</Label>
                            <Button
                                id="coop_picker"
                                type="button"
                                variant="outline"
                                :disabled="isCooperativeContext"
                                class="h-11 w-full items-center justify-between"
                                :class="[
                                    { 'border-red-500 focus-visible:ring-red-500': form.errors.coop_id || form.errors.coop_ids },
                                    isCooperativeContext ? 'cursor-not-allowed bg-muted opacity-60' : '',
                                ]"
                                @click="openCooperativePicker"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <Lock v-if="isCooperativeContext" class="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span class="truncate text-left">{{ isCooperativeContext ? (lockedCooperative?.name || selectedCooperativeSummary) : selectedCooperativeSummary }}</span>
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">{{ isCooperativeContext ? 'Locked' : 'Select' }}</span>
                            </Button>
                            <p v-if="isCooperativeContext" class="mt-1 text-xs text-muted-foreground">Cooperative is automatically set based on your current context.</p>
                            <div v-if="selectedCooperatives.length" class="mt-3 flex flex-wrap gap-1.5">
                                <Badge v-for="coop in selectedCooperatives" :key="coop.id" variant="secondary" class="max-w-full truncate">{{ coop.name }}</Badge>
                            </div>
                            <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">{{ form.errors.coop_id }}</p>
                            <p v-if="form.errors.coop_ids" class="mt-1 text-sm text-red-500">{{ form.errors.coop_ids }}</p>
                        </div>

                        <div>
                            <Label for="responsible_officer_id">Responsible Officer</Label>
                            <Select v-model="form.responsible_officer_id">
                                <SelectTrigger id="responsible_officer_id" :class="{ 'border-red-500': form.errors.responsible_officer_id }">
                                    <SelectValue placeholder="Select officer" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">No officer</SelectItem>
                                    <SelectItem v-for="officer in filteredOfficers" :key="officer.id" :value="officer.id.toString()">
                                        {{ officer.name || 'Unknown' }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.responsible_officer_id" class="mt-1 text-sm text-red-500">{{ form.errors.responsible_officer_id }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Budget &amp; Beneficiaries</CardTitle>
                        <CardDescription>Capture the budget, expense, and beneficiary counts.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Budget &amp; Expense</h3>
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
                        <CardDescription>Manage the outcomes attachment and supporting funding source files.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6 pt-0">
                        <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-muted-foreground">
                                    <Upload class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Outcomes Attachment</h3>
                                    <p class="text-xs text-muted-foreground">PDF, Word, Excel, and image files are supported.</p>
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

                            <div v-if="!outcomesExistingFile && !outcomesNewFile" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                No file added yet. Use the file input above to add a supporting document.
                            </div>

                            <div v-if="outcomesExistingFile" class="overflow-hidden rounded-xl border border-border bg-background">
                                <div class="flex items-center gap-3 p-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border" :class="getAttachmentToneClasses(outcomesExistingFile.kind)">
                                        <img v-if="outcomesExistingFile.kind === 'image' && outcomesExistingFile.previewUrl" :src="outcomesExistingFile.previewUrl" :alt="outcomesExistingFile.name" class="h-full w-full object-cover" />
                                        <FileText v-else-if="outcomesExistingFile.kind === 'pdf'" class="h-5 w-5" />
                                        <FileText v-else-if="outcomesExistingFile.kind === 'word'" class="h-5 w-5" />
                                        <FileSpreadsheet v-else-if="outcomesExistingFile.kind === 'excel'" class="h-5 w-5" />
                                        <File v-else class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <a v-if="outcomesExistingFile.url" :href="outcomesExistingFile.url" target="_blank" rel="noreferrer" class="truncate font-medium text-primary underline" :title="outcomesExistingFile.name">{{ outcomesExistingFile.name }}</a>
                                            <p v-else class="truncate font-medium text-foreground" :title="outcomesExistingFile.name">{{ outcomesExistingFile.name }}</p>
                                            <Badge variant="outline" class="shrink-0">Saved</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ outcomesExistingFile.sizeLabel }}</p>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-muted-foreground" @click="removeOutcomesAttachment">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div v-if="outcomesNewFile" class="overflow-hidden rounded-xl border border-border bg-background">
                                <div class="flex items-center gap-3 p-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border" :class="getAttachmentToneClasses(outcomesNewFile.kind)">
                                        <img v-if="outcomesNewFile.kind === 'image' && outcomesNewFile.previewUrl" :src="outcomesNewFile.previewUrl" :alt="outcomesNewFile.name" class="h-full w-full object-cover" />
                                        <FileText v-else-if="outcomesNewFile.kind === 'pdf'" class="h-5 w-5" />
                                        <FileText v-else-if="outcomesNewFile.kind === 'word'" class="h-5 w-5" />
                                        <FileSpreadsheet v-else-if="outcomesNewFile.kind === 'excel'" class="h-5 w-5" />
                                        <File v-else class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate font-medium text-foreground" :title="outcomesNewFile.name">{{ outcomesNewFile.name }}</p>
                                            <Badge variant="secondary" class="shrink-0">New</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ outcomesNewFile.sizeLabel }}</p>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-muted-foreground" @click="removeOutcomesAttachment">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                            <p v-if="form.errors.outcomes_attachment" class="text-sm text-red-500">{{ form.errors.outcomes_attachment }}</p>
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
                                <Card v-for="(source, index) in form.funding_sources" :key="source.id ?? index" class="border-border/70 bg-background shadow-none">
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
                                                    <Button v-if="(source.attachment_names?.length || 0) + source.attachments.length < maxFundingSourceFiles" type="button" variant="outline" size="sm" class="gap-1" @click="triggerFundingSourceFilePicker(index)">
                                                        <Plus class="h-3.5 w-3.5" />
                                                        Add File
                                                    </Button>
                                                    <span class="text-xs text-muted-foreground">{{ (source.attachment_names?.length || 0) + source.attachments.length }} of {{ maxFundingSourceFiles }} files added</span>
                                                    <span class="text-xs text-muted-foreground">Maximum file size: {{ MAX_FILE_SIZE_MB }}MB per file</span>
                                                </div>

                                                <div v-if="fundingSourceFiles(source).length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                                    No files added yet.
                                                </div>

                                                <div v-else class="space-y-3">
                                                    <div v-for="file in fundingSourceFiles(source)" :key="`${file.name}-${file.isExisting ? file.path : file.pendingIndex}`" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center">
                                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border" :class="getAttachmentToneClasses(file.kind)">
                                                                <img v-if="file.kind === 'image' && file.previewUrl" :src="file.previewUrl" :alt="file.name" class="h-full w-full object-cover" />
                                                                <FileText v-else-if="file.kind === 'pdf'" class="h-5 w-5" />
                                                                <FileText v-else-if="file.kind === 'word'" class="h-5 w-5" />
                                                                <FileSpreadsheet v-else-if="file.kind === 'excel'" class="h-5 w-5" />
                                                                <File v-else class="h-5 w-5" />
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <div class="flex items-center gap-2">
                                                                    <a v-if="file.url" :href="file.url" target="_blank" rel="noreferrer" class="truncate font-medium text-primary underline" :title="file.name">{{ file.name }}</a>
                                                                    <p v-else class="truncate font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                                                    <Badge v-if="file.isExisting" variant="outline" class="shrink-0 gap-1"><Cloud class="h-3 w-3" />Saved</Badge>
                                                                    <Badge v-else variant="secondary" class="shrink-0">New</Badge>
                                                                </div>
                                                                <p class="text-xs text-muted-foreground">{{ file.sizeLabel }}</p>
                                                            </div>
                                                        </div>
                                                        <Button
                                                            v-if="!file.isExisting && typeof file.pendingIndex === 'number'"
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-8 px-2 text-muted-foreground"
                                                            @click="removeFundingSourceAttachment(index, file.pendingIndex)"
                                                        >
                                                            <X class="h-4 w-4" />
                                                        </Button>
                                                        <Button
                                                            v-else-if="file.isExisting && file.path"
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            class="h-8 px-2 text-muted-foreground"
                                                            @click="removeExistingFundingSourceFile(index, file.path)"
                                                        >
                                                            <X class="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span v-if="source.is_saved" class="inline-flex items-center rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">Saved</span>
                                            <Button v-else type="button" variant="secondary" size="sm" class="gap-1" @click="saveFundingSource(index)">Save</Button>
                                            <Button type="button" variant="outline" size="sm" class="gap-2" @click="removeFundingSource(index)">
                                                <Trash2 class="h-4 w-4" />
                                                Remove Source
                                            </Button>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                            <p v-if="form.errors.funding_sources" class="mt-1 text-sm text-red-500">{{ form.errors.funding_sources }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Additional Notes / Remarks</CardTitle>
                        <CardDescription>Track outcomes and additional remarks for this activity.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div>
                            <Label for="outcomes">Outcomes</Label>
                            <Textarea id="outcomes" v-model="form.outcomes" placeholder="Key outputs or outcomes" />
                            <p v-if="form.errors.outcomes" class="mt-1 text-sm text-red-500">{{ form.errors.outcomes }}</p>
                        </div>
                        <div>
                            <Label for="remarks">Remarks</Label>
                            <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                            <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">{{ form.errors.remarks }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="goBack" type="button" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Cancel
                    </Button>
                    <Button v-if="canUpdateActivity" type="submit" :disabled="form.processing" class="gap-2">
                        <Save class="h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>

        <CooperativeMultiSelectDialog
            v-if="!isCooperativeContext"
            :open="isCooperativeDialogOpen"
            :cooperatives="cooperatives"
            :selected-ids="selectedCoopIds"
            title="Choose Cooperative"
            description="Search and filter cooperatives, then choose the cooperative for this activity record."
            confirm-label="Confirm"
            cancel-label="Cancel"
            @update:open="(value) => isCooperativeDialogOpen = value"
            @confirm="syncSelectedCooperatives"
        />
    </AppLayout>
</template>