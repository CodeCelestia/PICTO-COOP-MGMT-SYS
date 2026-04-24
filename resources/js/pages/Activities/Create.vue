<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, File, FileImage, FileSpreadsheet, FileText, Lock, Plus, Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction, notifyError, notifySuccess } from '@/lib/alerts';
import { useCoopLabel } from '@/composables/useCoopLabel';

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
    attachments: Array<File | null>;
    is_saved?: boolean;
}

type AttachmentKind = 'pdf' | 'word' | 'excel' | 'image' | 'other';

interface FileDisplayItem {
    name: string;
    sizeLabel: string;
    kind: AttachmentKind;
    previewUrl: string;
    pendingIndex: number;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateActivity = computed(() => permissions.value.includes('create activities-&-projects'));
const { cooperativeLabel } = useCoopLabel();
const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext && props.contextCooperativeId));
const lockedCooperativeId = computed(() => {
    if (!isCooperativeContext.value || !props.contextCooperativeId) return '';
    return String(props.contextCooperativeId);
});
const initialCooperativeIds = computed(() => {
    if (lockedCooperativeId.value) {
        return [lockedCooperativeId.value];
    }

    const firstCoopId = props.cooperatives[0]?.id;
    return firstCoopId ? [String(firstCoopId)] : [];
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
const isCooperativeDialogOpen = ref(false);
const selectedCoopIds = ref<string[]>(form.coop_ids);
const goBack = () => {
    window.history.back();
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

const addFundingSourceAttachment = (index: number) => {
    if (form.funding_sources[index].attachments.length >= maxFundingSourceFiles) return;
    form.funding_sources[index].attachments.push(null);
};

const updateFundingSourceAttachment = (event: Event, index: number, fileIndex: number) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0] || null;
    form.funding_sources[index].attachments[fileIndex] = nextFile;
    if (nextFile) notifySuccess('File added to funding source.');
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

const fileKindFromName = (name: string) => {
    const extension = name.split('.').pop()?.toLowerCase() || '';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) return 'image';
    if (extension === 'pdf') return 'pdf';
    if (['doc', 'docx'].includes(extension)) return 'word';
    if (['xls', 'xlsx'].includes(extension)) return 'excel';
    return 'other';
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

const getAttachmentKind = (file: File): AttachmentKind => {
    const extension = file.name.split('.').pop()?.toLowerCase() || '';
    if (file.type.startsWith('image/') || ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) return 'image';
    if (file.type === 'application/pdf' || extension === 'pdf') return 'pdf';
    if (file.type.includes('word') || ['doc', 'docx'].includes(extension)) return 'word';
    if (file.type.includes('spreadsheet') || file.type.includes('excel') || ['xls', 'xlsx'].includes(extension)) return 'excel';
    return 'other';
};

const getAttachmentPreviewUrl = (file: File) => (getAttachmentKind(file) === 'image' ? URL.createObjectURL(file) : '');

const buildFileDisplayItem = (file: File, pendingIndex: number): FileDisplayItem => ({
    name: file.name,
    sizeLabel: formatFileSize(file.size),
    kind: getAttachmentKind(file),
    previewUrl: getAttachmentPreviewUrl(file),
    pendingIndex,
});

const getAttachmentLabel = (kind: AttachmentKind) => {
    switch (kind) {
        case 'pdf':
            return 'PDF';
        case 'word':
            return 'Word';
        case 'excel':
            return 'Excel';
        case 'image':
            return 'Image';
        default:
            return 'File';
    }
};

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

const fundingSourceFiles = (source: FundingSourceFormRow) => source.attachments
    .map((file, pendingIndex) => (file ? buildFileDisplayItem(file, pendingIndex) : null))
    .filter((entry): entry is FileDisplayItem => Boolean(entry));

const outcomesAttachment = computed(() => (
    form.outcomes_attachment ? buildFileDisplayItem(form.outcomes_attachment, 0) : null
));

const updateOutcomesAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0] || null;
    form.outcomes_attachment = nextFile;
    if (nextFile) notifySuccess('Outcomes attachment added.');
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

const submit = () => {
    if (!canCreateActivity.value) return;
    if (!selectedCoopIds.value.length) {
        form.setError('coop_ids', 'Please select at least one cooperative.');
        return;
    }

    if (selectedCoopIds.value.length !== 1) {
        form.responsible_officer_id = 'none';
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
        })),
        outcomes_attachment: data.outcomes_attachment || null,
    })).post('/activities', {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Activity saved successfully.');
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            notifyError(firstError || 'Unable to save activity. Please check the form and try again.');
        },
    });
};

const cancel = () => {
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
                        <CardDescription>Select the cooperatives this activity belongs to.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div>
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
                    <CardContent class="space-y-6 pt-0">
                        <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-muted-foreground">
                                    <Upload class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">Outcomes Attachment</h3>
                                    <p class="text-xs text-muted-foreground">PDF, Word, Excel, or image files are supported.</p>
                                </div>
                            </div>
                            <Input id="outcomes_attachment" type="file" class="min-w-56 flex-1" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" @change="updateOutcomesAttachment" />
                            <div v-if="!outcomesAttachment" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                No file added yet. Use the file input above to add a supporting document.
                            </div>
                            <div v-else class="overflow-hidden rounded-xl border border-border bg-background">
                                <div class="flex items-center gap-3 p-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border" :class="getAttachmentToneClasses(outcomesAttachment.kind)">
                                        <img v-if="outcomesAttachment.kind === 'image' && outcomesAttachment.previewUrl" :src="outcomesAttachment.previewUrl" :alt="outcomesAttachment.name" class="h-full w-full object-cover" />
                                        <FileText v-else-if="outcomesAttachment.kind === 'pdf'" class="h-5 w-5" />
                                        <FileText v-else-if="outcomesAttachment.kind === 'word'" class="h-5 w-5" />
                                        <FileText v-else-if="outcomesAttachment.kind === 'excel'" class="h-5 w-5" />
                                        <File v-else class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate font-medium text-foreground" :title="outcomesAttachment.name">{{ outcomesAttachment.name }}</p>
                                            <Badge variant="outline" class="shrink-0">{{ getAttachmentLabel(outcomesAttachment.kind) }}</Badge>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ outcomesAttachment.sizeLabel }}</p>
                                    </div>
                                    <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-muted-foreground" @click="removeOutcomesAttachment">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
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
                                                <div v-if="source.attachments.length === 0" class="flex flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground">
                                                    <Upload class="h-5 w-5" />
                                                    <p>No files added yet.</p>
                                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                                        <Input type="file" class="min-w-56 flex-1" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" @change="updateFundingSourceAttachment($event, index, 0)" />
                                                        <Button type="button" variant="outline" size="sm" class="gap-1" :disabled="source.attachments.length >= maxFundingSourceFiles" @click="addFundingSourceAttachment(index)">
                                                            <Plus class="h-3.5 w-3.5" />
                                                            Add File
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div v-else class="space-y-3">
                                                    <div v-for="(file, fileIndex) in fundingSourceFiles(source)" :key="`${file.name}-${file.pendingIndex}`" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center">
                                                        <div class="flex min-w-0 flex-1 items-center gap-3">
                                                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border" :class="getAttachmentToneClasses(file.kind)">
                                                                <img v-if="file.kind === 'image' && file.previewUrl" :src="file.previewUrl" :alt="file.name" class="h-full w-full object-cover" />
                                                                <FileText v-else-if="file.kind === 'pdf'" class="h-5 w-5" />
                                                                <FileText v-else-if="file.kind === 'word'" class="h-5 w-5" />
                                                                <FileText v-else-if="file.kind === 'excel'" class="h-5 w-5" />
                                                                <File v-else class="h-5 w-5" />
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <p class="truncate font-medium text-foreground" :title="file.name">{{ file.name }}</p>
                                                                <p class="text-xs text-muted-foreground">{{ file.sizeLabel }}</p>
                                                            </div>
                                                            <Badge variant="outline" class="shrink-0">{{ getAttachmentLabel(file.kind) }}</Badge>
                                                        </div>
                                                        <div class="flex items-center gap-2 sm:justify-end">
                                                            <Button type="button" variant="ghost" size="sm" class="h-8 px-2 text-muted-foreground" @click="removeFundingSourceAttachment(index, file.pendingIndex)">
                                                                <X class="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                v-if="fileIndex === source.attachments.length - 1 && source.attachments.length < maxFundingSourceFiles"
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                class="gap-1"
                                                                @click="addFundingSourceAttachment(index)"
                                                            >
                                                                <Plus class="h-3.5 w-3.5" />
                                                                Add File
                                                            </Button>
                                                        </div>
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

    </AppLayout>
</template>
