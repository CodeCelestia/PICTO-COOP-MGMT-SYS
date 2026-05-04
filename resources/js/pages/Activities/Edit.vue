<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ClipboardList, Download, Eye, FileX, Image, Lock, Monitor, Plus, Save, Trash2, Upload, X } from 'lucide-vue-next';
import { computed, onUnmounted, onMounted, ref, watch, nextTick } from 'vue';
import Swal from 'sweetalert2';
import { useFormUx } from '@/composables/useFormUx';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useCoopLabel } from '@/composables/useCoopLabel';
import { useCreateBack } from '@/composables/useCreateBack';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    getFileExtension,
    getFileTypeConfig,
    getLegendFileTypeGroups,
    getPreviewSuggestion,
} from '@/lib/activityFileTypes';
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
    outcomes_attachments?: StoredAttachment[];
    remarks: string | null;
    funding_sources?: FundingSourceRecord[];
    image_attachments?: ImageAttachment[];
}

interface StoredAttachment {
    id?: number;
    path: string;
    filename: string;
    url: string;
    size: number;
}

interface ImageAttachment {
    id: number;
    filename: string;
    url: string;
    size: number;
}

interface FundingSourceRecord {
    id: number;
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
}

interface FundingSourceFormRow {
    id?: number;
    coop_id?: number;
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

interface FileDisplayItem {
    name: string;
    sizeLabel: string;
    extension: string;
    previewUrl: string;
    pendingIndex?: number;
    url?: string;
    path?: string;
    isExisting: boolean;
}

interface PreviewUnavailableFile {
    name: string;
    url: string;
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
    outcomes_attachment: [] as File[],
    removed_outcomes_paths: [] as string[],
    outcomes_attachment_removed: false,
    image_attachments: [] as File[],
    removed_image_ids: [] as number[],
    remarks: props.activity.remarks || '',
    funding_sources: (props.activity.funding_sources || []).map((source) => ({
        id: source.id,
        coop_id: source.coop_id,
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

// Initialize useFormUx for UX handling (dirty tracking, error classes, shake/scroll)
const { isDirty, isPreFilling, markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, handleCancel, showErrorShake } = useFormUx(form);
const maxFundingSourceFiles = 3;
const MAX_OUTCOMES_FILES = 3;
const MAX_IMAGES = 3;
const MAX_FILE_SIZE_MB = 5;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const fundingFileInputRefs = ref<Record<number, HTMLInputElement | null>>({});
const outcomesFileInputRef = ref<HTMLInputElement | null>(null);
const imageFileInputRef = ref<HTMLInputElement | null>(null);
const fileObjectUrls = new Map<File, string>();

const initialSnapshot = JSON.stringify(form);

const hasUnsavedChanges = () => JSON.stringify(form) !== initialSnapshot;

const cancel = async () => {
    // If form has unsaved changes, show SweetAlert first
    if (isDirty.value) {
        const result = await Swal.fire({
            title: 'Discard changes?',
            text: 'You have unsaved changes. Are you sure you want to discard them?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing',
        });

        if (!result.isConfirmed) return;  // User chose to keep editing
        // User chose to discard, proceed with navigation
    }

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

    router.get(`/activities/${props.activity.id}`);
};

// Mark the form as clean after initial prefill (so changes are only tracked from here on)
onMounted(async () => {
    isPreFilling.value = true;
    await nextTick();
    isPreFilling.value = false;
    markClean();
});

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
const { goBack, returnToHref } = useCreateBack({ fallbackHref: '/activities' });

// ✅ FIX: Always navigate to the activities index, never use document.referrer
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
        coop_id: Number(selectedCoopIds.value[0] || form.coop_id || props.activity.coop_id),
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
    Swal.fire({
        icon: 'error',
        title: 'File Too Large',
        html: `
            <p>The file you selected is too large:</p>
            <p class="font-semibold mt-1">"${fileName}"</p>
            <p class="mt-2">Maximum allowed size is <b>${MAX_FILE_SIZE_MB}MB</b> per file. Please choose a smaller file.</p>
        `,
        confirmButtonText: 'OK, got it',
        confirmButtonColor: '#ef4444',
    });
};

const showImageSizeError = (fileName: string) => {
    Swal.fire({
        icon: 'error',
        title: 'Image Too Large',
        html: `
            <p>The image you selected is too large:</p>
            <p class="font-semibold mt-1">"${fileName}"</p>
            <p class="mt-2">Maximum allowed size is <b>${MAX_FILE_SIZE_MB}MB</b> per image. Please choose a smaller image.</p>
        `,
        confirmButtonText: 'OK, got it',
        confirmButtonColor: '#ef4444',
    });
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

const getAttachmentPreviewUrl = (file: File) => {
    const existing = fileObjectUrls.get(file);
    if (existing) {
        return existing;
    }

    const url = URL.createObjectURL(file);
    fileObjectUrls.set(file, url);
    return url;
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

const fundingSourceFiles = (source: FundingSourceFormRow): FileDisplayItem[] => {
    const existing = (source.attachment_names || []).map((name, idx) => {
        const path = source.attachment_paths?.[idx] || '';
        const extension = getFileExtension(name);
        return {
            name,
            sizeLabel: 'Saved file',
            extension,
            previewUrl: path ? `/storage/${path}` : '',
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
            extension: getFileExtension(file.name),
            previewUrl: getAttachmentPreviewUrl(file),
            pendingIndex,
            isExisting: false,
        });

        return items;
    }, [] as FileDisplayItem[]);

    return [...existing, ...pending];
};

const outcomesExistingAttachments = computed<FileDisplayItem[]>(() => {
    const storedAttachments = props.activity.outcomes_attachments || [];
    const fallbackAttachment = props.activity.outcomes_attachment_path
        ? [{
            path: props.activity.outcomes_attachment_path,
            filename: props.activity.outcomes_attachment_path.split('/').pop() || 'Outcomes attachment',
            url: `/storage/${props.activity.outcomes_attachment_path}`,
            size: 0,
        }]
        : [];

    return [...storedAttachments, ...fallbackAttachment]
        .filter((attachment) => !form.removed_outcomes_paths.includes(attachment.path))
        .map((attachment) => ({
            name: attachment.filename,
            sizeLabel: attachment.size > 0 ? formatFileSize(attachment.size) : 'Saved file',
            extension: getFileExtension(attachment.filename),
            previewUrl: attachment.url,
            url: attachment.url,
            path: attachment.path,
            isExisting: true,
        }));
});

const outcomesFiles = computed<FileDisplayItem[]>(() => [
    ...outcomesExistingAttachments.value,
    ...form.outcomes_attachment.map((file, pendingIndex) => ({
        name: file.name,
        sizeLabel: formatFileSize(file.size),
        extension: getFileExtension(file.name),
        previewUrl: getAttachmentPreviewUrl(file),
        pendingIndex,
        isExisting: false,
    })),
]);

onUnmounted(() => {
    fileObjectUrls.forEach((url) => URL.revokeObjectURL(url));
    fileObjectUrls.clear();
});

const updateOutcomesAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const files = input?.files ? Array.from(input.files) : [];

    for (const file of files) {
        if (outcomesFiles.value.length >= MAX_OUTCOMES_FILES) {
            break;
        }

        if (isFileTooLarge(file)) {
            continue;
        }

        form.outcomes_attachment.push(file);
    }

    if (input) input.value = '';
    if (files.length > 0 && form.outcomes_attachment.length > 0) {
        form.outcomes_attachment_removed = false;
        notifySuccess('Outcomes attachment(s) added.');
    }
};

const triggerOutcomesFilePicker = () => {
    outcomesFileInputRef.value?.click();
};

const removeNewOutcomesAttachment = async (index: number) => {
    if (index < 0 || index >= form.outcomes_attachment.length) return;
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will remove the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    const file = form.outcomes_attachment[index];
    const url = fileObjectUrls.get(file);
    if (url) {
        URL.revokeObjectURL(url);
        fileObjectUrls.delete(file);
    }

    form.outcomes_attachment.splice(index, 1);
};

const removeExistingOutcomesAttachment = async (path: string) => {
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will remove the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    if (!form.removed_outcomes_paths.includes(path)) {
        form.removed_outcomes_paths.push(path);
    }
};

const restoreExistingOutcomesAttachment = (path: string) => {
    const index = form.removed_outcomes_paths.indexOf(path);
    if (index > -1) {
        form.removed_outcomes_paths.splice(index, 1);
    }
};

const getImageExtension = (filename: string) => {
    return filename.split('.').pop()?.toUpperCase() || 'IMG';
};

const showImageTypeError = (fileName: string) => {
    Swal.fire({
        icon: 'warning',
        title: 'Invalid File Type',
        html: `
            <p>"${fileName}" is not a supported image format.</p>
            <p class="mt-2">Accepted formats: <b>JPG, JPEG, PNG, GIF, WEBP</b></p>
        `,
        confirmButtonText: 'OK',
        confirmButtonColor: '#f59e0b',
    });
};

const isValidImageType = (file: File): boolean => {
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showImageTypeError(file.name);
        return false;
    }
    return true;
};

const existingImages = computed(() => (props.activity.image_attachments || []).map((img) => ({
    id: img.id,
    name: img.filename,
    size: img.size,
    previewUrl: img.url,
    isExisting: true,
})));

const imageGridClass = computed(() => {
    const count = displayedExistingImages.value.length + form.image_attachments.length;
    if (count === 1) return 'grid grid-cols-1 gap-2';
    if (count === 2) return 'grid grid-cols-2 gap-2';
    if (count === 3) return 'grid grid-cols-3 gap-2';
    return 'grid grid-cols-1 gap-2';
});

const triggerImageFilePicker = () => {
    imageFileInputRef.value?.click();
};

const updateImageAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    const files = input?.files ? Array.from(input.files) : [];
    
    for (const file of files) {
        if (displayedExistingImages.value.length + form.image_attachments.length >= MAX_IMAGES) break;
        
        if (!isValidImageType(file)) {
            continue;
        }
        
        if (file.size > MAX_FILE_SIZE_BYTES) {
            showImageSizeError(file.name);
            continue;
        }
        
        form.image_attachments.push(file);
    }
    
    if (input) input.value = '';
    if (files.length > 0) notifySuccess('Image(s) added successfully.');
};

const removeImageAttachment = async (index: number) => {
    const ok = await confirmAction({
        title: 'Remove image?',
        text: 'This will remove the selected image.',
        confirmButtonText: 'Remove image',
    });
    if (!ok) return;
    
    const file = form.image_attachments[index];
    if (file) {
        const url = fileObjectUrls.get(file);
        if (url) {
            URL.revokeObjectURL(url);
            fileObjectUrls.delete(file);
        }
    }
    
    form.image_attachments.splice(index, 1);
};

const removeExistingImage = async (imageId: number) => {
    const ok = await confirmAction({
        title: 'Delete image?',
        text: 'This will permanently delete the image.',
        confirmButtonText: 'Delete image',
    });
    if (!ok) return;
    
    if (!form.removed_image_ids.includes(imageId)) {
        form.removed_image_ids.push(imageId);
    }
};

const restoreExistingImage = (imageId: number) => {
    const index = form.removed_image_ids.indexOf(imageId);
    if (index > -1) {
        form.removed_image_ids.splice(index, 1);
    }
};

const displayedExistingImages = computed(() => {
    return existingImages.value.filter((img) => !form.removed_image_ids.includes(img.id));
});

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
        return_to: returnToHref.value,
        responsible_officer_id: data.responsible_officer_id === 'none' ? '' : data.responsible_officer_id,
        funding_source: data.funding_source || data.funding_sources[0]?.funder_name || '',
        funding_sources: data.funding_sources.map((source) => ({
            ...source,
            coop_id: source.coop_id || Number(selectedCoopIds.value[0] || data.coop_id || props.activity.coop_id),
            amount_allocated: source.amount_allocated || null,
            amount_released: source.amount_released || null,
            date_released: source.date_released || null,
            remarks: source.remarks || null,
            attachments: source.attachments.filter((file): file is File => Boolean(file)),
            attachments_removed: source.attachments_removed || [],
        })),
        outcomes_attachment: data.outcomes_attachment,
        removed_outcomes_paths: data.removed_outcomes_paths || [],
        image_attachments: data.image_attachments || [],
        removed_image_ids: data.removed_image_ids || [],
    })).put(`/activities/${props.activity.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
            notifySuccess('Activity updated successfully.');
        },
        onError: (errors) => {
            triggerErrorShake();
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
                        <!-- Back removed per UX rules for Edit pages -->
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
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
                                <Input id="title" v-model="form.title" :class="inputErrorClass('title')" @input="clearError('title')" placeholder="Enter activity title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <Label for="category">Category <span class="text-red-500">*</span></Label>
                                <Select v-model="form.category" @update:modelValue="clearError('category')">
                                    <SelectTrigger id="category" :class="inputErrorClass('category')">
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
                                <Select v-model="form.status" @update:modelValue="clearError('status')">
                                    <SelectTrigger id="status" :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                            </div>
                            <div>
                                <Label for="date_started">Start Date <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="date_started" v-model="form.date_started" type="date" :class="inputErrorClass('date_started')" @input="clearError('date_started')" />
                                <p v-if="form.errors.date_started" class="mt-1 text-sm text-red-500">{{ form.errors.date_started }}</p>
                            </div>
                            <div>
                                <Label for="date_ended">End Date <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="date_ended" v-model="form.date_ended" type="date" :class="inputErrorClass('date_ended')" @input="clearError('date_ended')" />
                                <p v-if="form.errors.date_ended" class="mt-1 text-sm text-red-500">{{ form.errors.date_ended }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="description">Description / Objective <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Textarea id="description" v-model="form.description" :class="inputErrorClass('description')" @input="clearError('description')" placeholder="Brief description or objective of the activity" />
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">{{ form.errors.description }}</p>
                            </div>
                            <div>
                                <Label for="venue">Venue <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="venue" v-model="form.venue" :class="inputErrorClass('venue')" @input="clearError('venue')" placeholder="Enter venue or location" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">{{ form.errors.venue }}</p>
                            </div>
                            <div>
                                <Label for="implementing_partner">Implementing Partner <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="implementing_partner" v-model="form.implementing_partner" :class="inputErrorClass('implementing_partner')" @input="clearError('implementing_partner')" placeholder="Enter implementing partner" />
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
                            <Label for="coop_picker">{{ cooperativeLabel }} <span class="text-red-500">*</span></Label>
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
                            <Label for="responsible_officer_id">Responsible Officer <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                            <Select v-model="form.responsible_officer_id" @update:modelValue="clearError('responsible_officer_id')">
                                <SelectTrigger id="responsible_officer_id" :class="inputErrorClass('responsible_officer_id')">
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
                                        <Label for="budget">Budget <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="budget" v-model="form.budget" type="number" min="0" step="0.01" :class="inputErrorClass('budget')" @input="clearError('budget')" placeholder="0.00" />
                                        <p v-if="form.errors.budget" class="mt-1 text-sm text-red-500">{{ form.errors.budget }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_expense">Actual Expense <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="actual_expense" v-model="form.actual_expense" type="number" min="0" step="0.01" :class="inputErrorClass('actual_expense')" @input="clearError('actual_expense')" placeholder="0.00" />
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
                                        <Label for="target_member_beneficiaries">Target Member Beneficiaries <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="target_member_beneficiaries" v-model="form.target_member_beneficiaries" type="number" min="0" :class="inputErrorClass('target_member_beneficiaries')" @input="clearError('target_member_beneficiaries')" placeholder="0" />
                                        <p v-if="form.errors.target_member_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.target_member_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_member_beneficiaries">Actual Member Beneficiaries <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="actual_member_beneficiaries" v-model="form.actual_member_beneficiaries" type="number" min="0" :class="inputErrorClass('actual_member_beneficiaries')" @input="clearError('actual_member_beneficiaries')" placeholder="0" />
                                        <p v-if="form.errors.actual_member_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.actual_member_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="target_community_beneficiaries">Target Community Beneficiaries <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="target_community_beneficiaries" v-model="form.target_community_beneficiaries" type="number" min="0" :class="inputErrorClass('target_community_beneficiaries')" @input="clearError('target_community_beneficiaries')" placeholder="0" />
                                        <p v-if="form.errors.target_community_beneficiaries" class="mt-1 text-sm text-red-500">{{ form.errors.target_community_beneficiaries }}</p>
                                    </div>
                                    <div>
                                        <Label for="actual_community_beneficiaries">Actual Community Beneficiaries <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                        <Input id="actual_community_beneficiaries" v-model="form.actual_community_beneficiaries" type="number" min="0" :class="inputErrorClass('actual_community_beneficiaries')" @input="clearError('actual_community_beneficiaries')" placeholder="0" />
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
                    <CardContent class="pt-0">
                        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_14rem]">
                            <div class="space-y-6">
                                <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-muted-foreground">
                                            <Upload class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-foreground">Outcomes Attachment <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></h3>
                                            <p class="text-xs text-muted-foreground">PDF, Word, Excel, presentation, or image files are supported.</p>
                                        </div>
                                    </div>
                                    <input ref="outcomesFileInputRef" type="file" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp" multiple @change="updateOutcomesAttachment" />
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Button v-if="outcomesFiles.length < MAX_OUTCOMES_FILES" type="button" variant="outline" size="sm" class="gap-1" @click="triggerOutcomesFilePicker">
                                            <Plus class="h-3.5 w-3.5" />
                                            Add File
                                        </Button>
                                        <span class="text-xs text-muted-foreground">{{ outcomesFiles.length }}/3 files</span>
                                    </div>

                                    <div v-if="outcomesFiles.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-4 text-sm text-muted-foreground">
                                        No file added yet. Use the file input above to add a supporting document.
                                    </div>

                                    <div v-else class="space-y-2">
                                        <div v-for="(file, index) in outcomesFiles" :key="`${file.name}-${index}`" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
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
                                                        <Button type="button" size="sm" class="h-7 gap-1 border border-red-200 bg-red-50 px-2 text-xs text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400" @click="file.isExisting ? removeExistingOutcomesAttachment(file.path || '') : removeNewOutcomesAttachment(file.pendingIndex ?? 0)">
                                                            <Trash2 class="h-3.5 w-3.5" />
                                                            Remove
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Remove this file</TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                    <p v-if="outcomesFiles.length >= MAX_OUTCOMES_FILES" class="text-xs text-muted-foreground text-center">
                                        Maximum 3 files reached. Remove a file to add another.
                                    </p>
                                    <p v-if="form.errors.outcomes_attachment" class="text-sm text-red-500">{{ form.errors.outcomes_attachment }}</p>
                                </div>

                                <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-purple-500">
                                            <Image class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-foreground">Photo / Image Attachments <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></h3>
                                            <p class="text-xs text-muted-foreground">Upload photos or images related to this activity. Maximum 3 images, up to 5MB each. Supported formats: JPG, JPEG, PNG, GIF, WEBP</p>
                                        </div>
                                    </div>
                                    <input ref="imageFileInputRef" type="file" class="hidden" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" multiple @change="updateImageAttachment" />
                                    <div v-if="displayedExistingImages.length === 0 && form.image_attachments.length === 0" class="rounded-lg border border-dashed border-border/70 bg-background p-8 text-center text-sm text-muted-foreground">
                                        <div class="flex flex-col items-center">
                                            <Image class="h-10 w-10 mb-3 opacity-30" />
                                            <p class="font-medium">No images added yet</p>
                                            <p class="text-xs mt-1">Click "Add Image" to upload photos</p>
                                            <Button type="button" variant="outline" size="sm" class="mt-4 gap-1" @click="triggerImageFilePicker">
                                                <Plus class="h-3.5 w-3.5" />
                                                Add Image
                                            </Button>
                                        </div>
                                    </div>
                                    <div v-else :class="imageGridClass" class="h-48">
                                        <div v-for="image in displayedExistingImages" :key="`existing-${image.id}`" class="group relative overflow-hidden rounded-lg border bg-muted shadow-sm h-full">
                                            <img :src="image.previewUrl" :alt="image.name" class="h-full w-full object-cover" />
                                            <div class="absolute inset-0 bg-black/0 transition-all duration-200 group-hover:bg-black/50" />
                                            <button
                                                @click="removeExistingImage(image.id)"
                                                type="button"
                                                class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white transition-colors duration-150 hover:bg-red-500"
                                                title="Delete image">
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                            <div class="absolute left-2 top-2 z-10">
                                                <span class="inline-flex items-center rounded-md bg-purple-500/90 px-1.5 py-0.5 text-xs font-bold text-white backdrop-blur-sm">
                                                    {{ getImageExtension(image.name) }}
                                                </span>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black/70 to-transparent p-2 pb-2.5 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                                <p class="truncate text-xs font-medium text-white" :title="image.name">{{ image.name }}</p>
                                                <p class="text-xs text-white/70">{{ formatFileSize(image.size) }}</p>
                                            </div>
                                        </div>
                                        <div v-for="(file, index) in form.image_attachments" :key="`new-${file.name}-${index}`" class="group relative overflow-hidden rounded-lg border bg-muted shadow-sm h-full">
                                            <img :src="getAttachmentPreviewUrl(file)" :alt="file.name" class="h-full w-full object-cover" />
                                            <div class="absolute inset-0 bg-black/0 transition-all duration-200 group-hover:bg-black/50" />
                                            <button
                                                @click="removeImageAttachment(index)"
                                                type="button"
                                                class="absolute right-2 top-2 z-10 flex h-7 w-7 items-center justify-center rounded-full bg-black/60 text-white transition-colors duration-150 hover:bg-red-500"
                                                title="Remove image">
                                                <X class="h-3.5 w-3.5" />
                                            </button>
                                            <div class="absolute left-2 top-2 z-10">
                                                <span class="inline-flex items-center rounded-md bg-purple-500/90 px-1.5 py-0.5 text-xs font-bold text-white backdrop-blur-sm">
                                                    {{ getImageExtension(file.name) }}
                                                </span>
                                            </div>
                                            <div class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-black/70 to-transparent p-2 pb-2.5 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                                <p class="truncate text-xs font-medium text-white" :title="file.name">{{ file.name }}</p>
                                                <p class="text-xs text-white/70">{{ formatFileSize(file.size) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="displayedExistingImages.length + form.image_attachments.length > 0" class="flex flex-wrap items-center gap-2">
                                        <Button v-if="displayedExistingImages.length + form.image_attachments.length < MAX_IMAGES" type="button" variant="outline" size="sm" class="gap-1" @click="triggerImageFilePicker">
                                            <Plus class="h-3.5 w-3.5" />
                                            Add Image
                                        </Button>
                                        <span class="text-xs text-muted-foreground">{{ displayedExistingImages.length + form.image_attachments.length }} / {{ MAX_IMAGES }} images</span>
                                    </div>
                                    <p v-if="displayedExistingImages.length + form.image_attachments.length >= MAX_IMAGES" class="text-xs text-muted-foreground text-center mt-2 py-2">
                                        Maximum 3 images reached. Remove an image to add another.
                                    </p>
                                </div>

                                <div class="space-y-4 rounded-xl border border-border/60 bg-muted/20 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-foreground">Funding Source Attachments <span class="text-xs text-muted-foreground font-normal ml-1 normal-case">(Optional)</span></h3>
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
                                                <Label :for="`funding_allocated_${index}`">Amount Allocated <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                                <Input :id="`funding_allocated_${index}`" v-model="source.amount_allocated" type="number" min="0" step="0.01" placeholder="0.00" />
                                            </div>
                                            <div>
                                                <Label :for="`funding_released_${index}`">Amount Released <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                                <Input :id="`funding_released_${index}`" v-model="source.amount_released" type="number" min="0" step="0.01" placeholder="0.00" />
                                            </div>
                                        </div>

                                        <div>
                                            <Label :for="`funding_remarks_${index}`">Notes <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                            <Input :id="`funding_remarks_${index}`" v-model="source.remarks" placeholder="Optional notes" />
                                        </div>

                                            <div>
                                                <Label class="mb-2 block">Files <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
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
                                                    <div v-for="file in fundingSourceFiles(source)" :key="`${file.name}-${file.isExisting ? file.path : file.pendingIndex}`" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3 transition-colors hover:bg-muted/50">
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
                                                                    <Button
                                                                        type="button"
                                                                        size="sm"
                                                                        class="h-7 gap-1 border border-red-200 bg-red-50 px-2 text-xs text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"
                                                                        @click="file.isExisting && file.path ? removeExistingFundingSourceFile(index, file.path) : removeFundingSourceAttachment(index, file.pendingIndex ?? 0)"
                                                                    >
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
                        <CardDescription>Track outcomes and additional remarks for this activity.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div>
                            <Label for="outcomes">Outcomes <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                            <Textarea id="outcomes" v-model="form.outcomes" :class="inputErrorClass('outcomes')" @input="clearError('outcomes')" placeholder="Key outputs or outcomes" />
                            <p v-if="form.errors.outcomes" class="mt-1 text-sm text-red-500">{{ form.errors.outcomes }}</p>
                        </div>
                        <div>
                            <Label for="remarks">Remarks <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                            <Textarea id="remarks" v-model="form.remarks" :class="inputErrorClass('remarks')" @input="clearError('remarks')" placeholder="Additional notes" />
                            <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">{{ form.errors.remarks }}</p>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="cancel" type="button" variant="outline" class="gap-2">
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