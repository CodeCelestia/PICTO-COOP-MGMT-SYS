<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    Check,
    CheckCircle2,
    Download,
    Eye,
    FileX,
    GraduationCap,
    Image,
    Lock,
    MinusCircle,
    Monitor,
    Plus,
    Save,
    Search,
    Trash2,
    Upload,
    UserCheck,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Swal from 'sweetalert2';
import { useFormUx } from '@/composables/useFormUx';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import TargetGroupMultiSelectDialog from '@/components/Trainings/TargetGroupMultiSelectDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    coop_id: number;
}

interface CooperativeMembersGroup {
    id: number;
    name: string;
    members: Member[];
}

interface MembersByCooperativesResponse {
    cooperatives: CooperativeMembersGroup[];
}

interface StoredAttachment {
    filename: string;
    url: string;
    size?: number | null;
    path?: string | null;
}

interface StoredImageAttachment {
    id?: number | null;
    filename: string;
    url: string;
    size?: number | null;
    path?: string | null;
}

interface FileDisplayItem {
    name: string;
    sizeLabel: string;
    extension: string;
    previewUrl: string;
    pendingIndex?: number;
    url?: string;
    path?: string | null;
    isExisting: boolean;
}

interface PreviewUnavailableFile {
    name: string;
    url: string;
}

interface Training {
    id: number;
    coop_id: number;
    title: string;
    date_conducted: string | null;
    facilitator: string | null;
    skills_targeted: string | null;
    venue: string | null;
    target_group: string;
    no_of_participants: number | null;
    follow_up_needed: boolean;
    follow_up_date: string | null;
    follow_up_remarks: string | null;
    outcomes_attachment_path?: string | null;
    outcomes_attachments?: StoredAttachment[];
    image_attachments?: StoredImageAttachment[];
    status: string;
}

interface Props {
    training: Training;
    cooperatives: Cooperative[];
    selected_member_ids: number[];
    isCooperativeContext?: boolean;
    contextCooperativeId?: number | null;
    assigned_coop_ids?: number[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateTraining = computed(() => permissions.value.includes('update training-&-capacity'));
const { cooperativeLabel } = useCoopLabel();
const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext && props.contextCooperativeId));
const lockedCooperativeId = computed(() => {
    if (!isCooperativeContext.value || !props.contextCooperativeId) return '';
    return String(props.contextCooperativeId);
});

const normalizeDateInput = (value: string | null | undefined) => dateInputValue(value);

const form = useForm({
    coop_id: String(props.training.coop_id),
    coop_ids: (props.assigned_coop_ids && props.assigned_coop_ids.length > 0)
        ? [...new Set(props.assigned_coop_ids.map((id) => String(id)))]
        : [String(props.training.coop_id)],
    member_ids: [...new Set(props.selected_member_ids || [])],
    title: props.training.title,
    date_conducted: normalizeDateInput(props.training.date_conducted),
    facilitator: props.training.facilitator || '',
    skills_targeted: props.training.skills_targeted || '',
    venue: props.training.venue || '',
    target_group: props.training.target_group,
    no_of_participants: props.training.no_of_participants?.toString() || '',
    follow_up_needed: props.training.follow_up_needed ?? false,
    follow_up_date: normalizeDateInput(props.training.follow_up_date),
    follow_up_remarks: props.training.follow_up_remarks || '',
    outcomes_attachments: [] as File[],
    removed_outcomes_attachment_paths: [] as string[],
    image_attachments: [] as File[],
    removed_image_attachment_paths: [] as string[],
    status: props.training.status,
});

const trainingTargetGroupOptions = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'FisherFolk', 'New Members', 'Other'];
const parseTargetGroups = (value: string | null | undefined) => {
    return [...new Set(String(value || '').split(',').map((item) => item.trim()).filter(Boolean))];
};
const isTargetGroupDialogOpen = ref(false);
const selectedTargetGroups = ref<string[]>(parseTargetGroups(props.training.target_group));

const statusOptions = ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'];
const MAX_OUTCOMES_FILES = 3;
const MAX_IMAGES = 3;
const MAX_FILE_SIZE_MB = 5;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;

// Initialize useFormUx for UX handling (dirty tracking, error classes, shake/scroll, cancel)
const { isDirty, isPreFilling, markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, showErrorShake } = useFormUx(form);

// Mark the form as clean after initial prefill (so changes are only tracked from here on)
onMounted(async () => {
    isPreFilling.value = true;
    await nextTick();
    isPreFilling.value = false;
    markClean();
});

const isCooperativeDialogOpen = ref(false);
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
const memberSearch = ref('');
const membersByCoop = ref<Record<number, CooperativeMembersGroup>>({});
const activeCoopId = ref<number | null>(null);
const isLoadingMembers = ref(false);
const fetchVersion = ref(0);
const outcomesFileInputRef = ref<HTMLInputElement | null>(null);
const imageFileInputRef = ref<HTMLInputElement | null>(null);
const fileObjectUrls = new Map<File, string>();

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

    router.get(`/trainings/${props.training.id}`);
};

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const returnToHref = computed(() => {
    const href = queryParams.value.get('return_to') || '';
    if (!href || !href.startsWith('/') || href.startsWith('//')) return '';
    return href;
});

// ✅ FIX: Always navigate to the trainings index, never use document.referrer
const selectedCooperatives = computed(() => {
    const selectedSet = new Set(selectedCoopIds.value);
    return props.cooperatives.filter((coop) => selectedSet.has(String(coop.id)));
});

const selectedTargetGroupLabel = computed(() => {
    if (!selectedTargetGroups.value.length) {
        return 'Select target groups';
    }

    return selectedTargetGroups.value.join(', ');
});

watch(selectedTargetGroups, (values) => {
    form.target_group = values.join(',');
    clearError('target_group');
}, { deep: true, immediate: true });

const showFileSizeError = (file: File) => {
    Swal.fire({
        icon: 'error',
        title: 'File Too Large',
        html: `
            <p>The file you selected is too large:</p>
            <p class="font-semibold mt-1">"${file.name}"</p>
            <p class="text-sm text-gray-500 mt-1">File size: ${(file.size / 1024 / 1024).toFixed(2)}MB</p>
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

    showFileSizeError(file);
    return true;
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

const outcomesExistingAttachments = computed<FileDisplayItem[]>(() => {
    const storedAttachments = props.training.outcomes_attachments || [];
    const fallbackAttachment = props.training.outcomes_attachment_path
        ? [{
            path: props.training.outcomes_attachment_path,
            filename: props.training.outcomes_attachment_path.split('/').pop() || 'Outcomes attachment',
            url: `/storage/${props.training.outcomes_attachment_path}`,
            size: 0,
        }]
        : [];

    return [...storedAttachments, ...fallbackAttachment]
        .filter((attachment) => !form.removed_outcomes_attachment_paths.includes(attachment.path || ''))
        .map((attachment) => ({
            name: attachment.filename,
            sizeLabel: attachment.size ? formatFileSize(attachment.size) : 'Saved file',
            extension: getFileExtension(attachment.filename),
            previewUrl: attachment.url,
            url: attachment.url,
            path: attachment.path,
            isExisting: true,
        }));
});

const outcomesFiles = computed<FileDisplayItem[]>(() => [
    ...outcomesExistingAttachments.value,
    ...form.outcomes_attachments.map((file, pendingIndex) => ({
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

        form.outcomes_attachments.push(file);
    }

    if (input) input.value = '';
    if (files.length > 0 && form.outcomes_attachments.length > 0) {
        notifySuccess('Outcomes attachment(s) added.');
    }
};

const triggerOutcomesFilePicker = () => {
    outcomesFileInputRef.value?.click();
};

const removeNewOutcomesAttachment = async (index: number) => {
    if (index < 0 || index >= form.outcomes_attachments.length) return;
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will remove the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    const file = form.outcomes_attachments[index];
    const url = fileObjectUrls.get(file);
    if (url) {
        URL.revokeObjectURL(url);
        fileObjectUrls.delete(file);
    }

    form.outcomes_attachments.splice(index, 1);
};

const removeExistingOutcomesAttachment = async (path: string) => {
    if (!path) return;
    const ok = await confirmAction({
        title: 'Remove attachment?',
        text: 'This will remove the selected outcomes file.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;

    if (!form.removed_outcomes_attachment_paths.includes(path)) {
        form.removed_outcomes_attachment_paths.push(path);
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

const existingImages = computed(() => (props.training.image_attachments || []).map((img, index) => ({
    id: img.id ?? index,
    name: img.filename,
    size: img.size,
    previewUrl: img.url,
    path: img.path,
    isExisting: true,
})));

const displayedExistingImages = computed(() => {
    return existingImages.value.filter((img) => !form.removed_image_attachment_paths.includes(img.path || ''));
});

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

const removeExistingImage = async (path: string) => {
    if (!path) return;
    const ok = await confirmAction({
        title: 'Delete image?',
        text: 'This will permanently delete the image.',
        confirmButtonText: 'Delete image',
    });
    if (!ok) return;

    if (!form.removed_image_attachment_paths.includes(path)) {
        form.removed_image_attachment_paths.push(path);
    }
};


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

const syncSelectedCooperatives = (ids: string[]) => {
    if (lockedCooperativeId.value) {
        selectedCoopIds.value = [lockedCooperativeId.value];
        form.coop_id = lockedCooperativeId.value;
        form.coop_ids = [lockedCooperativeId.value];
        form.clearErrors('coop_id');
        return;
    }

    selectedCoopIds.value = [...new Set(ids)];
    form.coop_id = selectedCoopIds.value[0] || '';
    form.coop_ids = [...selectedCoopIds.value];
    form.clearErrors('coop_id');
};

const sortMembers = (members: Member[]) => {
    return [...members].sort((a, b) => {
        const lastNameCompare = (a.last_name || '').localeCompare(b.last_name || '');
        if (lastNameCompare !== 0) return lastNameCompare;
        return (a.first_name || '').localeCompare(b.first_name || '');
    });
};

const formatMemberName = (member: Member) => {
    const lastName = (member.last_name || '').trim();
    const firstName = (member.first_name || '').trim();

    if (lastName && firstName) {
        return `${lastName}, ${firstName}`;
    }

    return lastName || firstName || 'Unnamed Member';
};

const getMemberInitials = (member: Member) => {
    const firstInitial = (member.first_name || '').trim().charAt(0);
    const lastInitial = (member.last_name || '').trim().charAt(0);
    const initials = `${firstInitial}${lastInitial}`.toUpperCase().trim();
    return initials || 'M';
};

const selectedMemberSet = computed(() => new Set(form.member_ids));

const selectedCooperativeGroups = computed(() => {
    return selectedCooperatives.value.map((coop) => ({
        id: coop.id,
        name: coop.name,
        members: sortMembers(membersByCoop.value[coop.id]?.members || []),
    }));
});

const hasCooperativeSidebar = computed(() => selectedCooperativeGroups.value.length > 1);

const activeCooperativeGroup = computed(() => {
    if (!activeCoopId.value) return null;
    return selectedCooperativeGroups.value.find((group) => group.id === activeCoopId.value) || null;
});

const activeMembers = computed(() => activeCooperativeGroup.value?.members || []);

const selectedMembersForCoop = computed(() => activeMembers.value.filter((member) => selectedMemberSet.value.has(member.id)));

const unselectedMembersForCoop = computed(() => activeMembers.value.filter((member) => !selectedMemberSet.value.has(member.id)));

const filteredAvailableMembers = computed(() => {
    const searchTerm = memberSearch.value.trim().toLowerCase();
    if (!searchTerm) return unselectedMembersForCoop.value;

    return unselectedMembersForCoop.value.filter((member) => {
        const formattedName = formatMemberName(member).toLowerCase();
        return formattedName.includes(searchTerm);
    });
});

const selectedCountByCoop = computed(() => {
    const counts: Record<number, number> = {};
    selectedCooperativeGroups.value.forEach((group) => {
        counts[group.id] = group.members.filter((member) => selectedMemberSet.value.has(member.id)).length;
    });
    return counts;
});

const selectedCoopCount = computed(() => {
    return selectedCooperativeGroups.value.filter((group) => (selectedCountByCoop.value[group.id] || 0) > 0).length;
});

const selectAllLabel = computed(() => {
    const searchTerm = memberSearch.value.trim();
    if (searchTerm) {
        return `Select Filtered (${filteredAvailableMembers.value.length})`;
    }

    return 'Select All';
});

const availableScrollRef = ref<HTMLElement | null>(null);
const selectedScrollRef = ref<HTMLElement | null>(null);
const hasAvailableOverflow = ref(false);
const hasSelectedOverflow = ref(false);

const updatePanelOverflow = () => {
    const availableEl = availableScrollRef.value;
    const selectedEl = selectedScrollRef.value;

    hasAvailableOverflow.value = Boolean(availableEl && availableEl.scrollHeight > availableEl.clientHeight + 1);
    hasSelectedOverflow.value = Boolean(selectedEl && selectedEl.scrollHeight > selectedEl.clientHeight + 1);
};

const isAllSelectedForCoop = (coopId: number) => {
    const group = selectedCooperativeGroups.value.find((item) => item.id === coopId);
    if (!group || group.members.length === 0) return false;
    return (selectedCountByCoop.value[coopId] || 0) === group.members.length;
};

const isPartialSelectedForCoop = (coopId: number) => {
    const group = selectedCooperativeGroups.value.find((item) => item.id === coopId);
    if (!group || group.members.length === 0) return false;
    const selectedCount = selectedCountByCoop.value[coopId] || 0;
    return selectedCount > 0 && selectedCount < group.members.length;
};

const isMemberSelected = (memberId: number) => form.member_ids.includes(memberId);

const addMemberSelection = (memberId: number) => {
    if (!form.member_ids.includes(memberId)) {
        form.member_ids.push(memberId);
    }
};

const removeMemberSelection = (memberId: number) => {
    form.member_ids = form.member_ids.filter((id) => id !== memberId);
};

const selectAllInCurrentCoop = () => {
    const source = memberSearch.value.trim() ? filteredAvailableMembers.value : unselectedMembersForCoop.value;
    form.member_ids = [...new Set([...form.member_ids, ...source.map((member) => member.id)])];
};

const openTargetGroupDialog = () => {
    isTargetGroupDialogOpen.value = true;
};

const confirmTargetGroups = (values: string[]) => {
    selectedTargetGroups.value = [...new Set(values)];
};

const deselectAllInCurrentCoop = () => {
    const idsToRemove = new Set(selectedMembersForCoop.value.map((member) => member.id));
    form.member_ids = form.member_ids.filter((id) => !idsToRemove.has(id));
};

const setActiveCooperative = (coopId: number) => {
    activeCoopId.value = coopId;
    memberSearch.value = '';
    nextTick(() => {
        availableScrollRef.value?.scrollTo({ top: 0 });
        selectedScrollRef.value?.scrollTo({ top: 0 });
        updatePanelOverflow();
    });
};

const fetchMembersForCooperatives = async (coopIds: string[]) => {
    const requestId = ++fetchVersion.value;
    isLoadingMembers.value = true;

    try {
        const params = new URLSearchParams();
        coopIds.forEach((id) => {
            params.append('cooperative_ids[]', String(Number(id)));
        });

        const response = await fetch(`/trainings/members/by-cooperatives?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to fetch members for selected cooperatives.');
        }

        const data = (await response.json()) as MembersByCooperativesResponse;

        if (requestId !== fetchVersion.value) {
            return;
        }

        const nextMembersByCoop: Record<number, CooperativeMembersGroup> = {};

        data.cooperatives.forEach((cooperative) => {
            nextMembersByCoop[cooperative.id] = {
                id: cooperative.id,
                name: cooperative.name,
                members: sortMembers(cooperative.members || []),
            };
        });

        membersByCoop.value = nextMembersByCoop;

        if (!activeCoopId.value || !coopIds.includes(String(activeCoopId.value))) {
            const firstCoopId = Number(coopIds[0]);
            activeCoopId.value = Number.isNaN(firstCoopId) ? null : firstCoopId;
        }

        await nextTick();
        updatePanelOverflow();
    } catch {
        if (requestId !== fetchVersion.value) {
            return;
        }

        membersByCoop.value = {};
        form.member_ids = [];
        notifyError('Unable to load members for selected cooperatives.');
    } finally {
        if (requestId === fetchVersion.value) {
            isLoadingMembers.value = false;
        }
    }
};

watch(lockedCooperativeId, (newValue) => {
    if (!newValue) return;

    selectedCoopIds.value = [newValue];
    form.coop_id = newValue;
    form.coop_ids = [newValue];
    form.clearErrors('coop_id');
}, { immediate: true });

watch(activeCoopId, () => {
    memberSearch.value = '';
    nextTick(() => {
        availableScrollRef.value?.scrollTo({ top: 0 });
        selectedScrollRef.value?.scrollTo({ top: 0 });
        updatePanelOverflow();
    });
});

watch(selectedCoopIds, async (ids) => {
    form.coop_id = ids[0] || '';
    form.coop_ids = [...ids];

    if (ids.length === 0) {
        membersByCoop.value = {};
        form.member_ids = [];
        activeCoopId.value = null;
        memberSearch.value = '';
        return;
    }

    if (!activeCoopId.value || !ids.includes(String(activeCoopId.value))) {
        activeCoopId.value = Number(ids[0]);
    }

    await fetchMembersForCooperatives(ids);

    const allowedMemberIds = new Set(
        Object.values(membersByCoop.value).flatMap((group) => group.members.map((member) => member.id))
    );

    form.member_ids = form.member_ids.filter((memberId) => allowedMemberIds.has(memberId));
    await nextTick();
    updatePanelOverflow();

    form.clearErrors('coop_id', 'coop_ids');
}, { deep: true, immediate: true });

watch(
    () => form.member_ids.length,
    (count) => {
        form.no_of_participants = String(count);
    },
    { immediate: true }
);

watch(
    () => [filteredAvailableMembers.value.length, selectedMembersForCoop.value.length, isLoadingMembers.value, activeCoopId.value, memberSearch.value],
    async () => {
        await nextTick();
        updatePanelOverflow();
    },
    { deep: true, immediate: true }
);

onMounted(() => {
    updatePanelOverflow();
});

const openCooperativePicker = () => {
    if (lockedCooperativeId.value || !props.cooperatives.length) return;
    isCooperativeDialogOpen.value = true;
};

const submit = () => {
    if (!canUpdateTraining.value) return;

    if (!form.coop_id) {
        form.setError('coop_id', 'Please select a cooperative.');
        return;
    }

    form.transform((data) => ({
        ...data,
        coop_id: selectedCoopIds.value[0] || '',
        coop_ids: [...selectedCoopIds.value],
        target_group: selectedTargetGroups.value.join(','),
        no_of_participants: String(form.member_ids.length),
        return_to: returnToHref.value,
        outcomes_attachments: data.outcomes_attachments,
        removed_outcomes_attachment_paths: data.removed_outcomes_attachment_paths || [],
        image_attachments: data.image_attachments || [],
        removed_image_attachment_paths: data.removed_image_attachment_paths || [],
    })).put(`/trainings/${props.training.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
            notifySuccess('Training updated successfully.');
        },
        onError: (errors) => {
            triggerErrorShake();
            const firstError = Object.values(errors)[0];
            notifyError(firstError || 'Unable to update training. Please check the form and try again.');
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
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <Badge variant="outline" class="mb-2">Training &amp; Capacity Building</Badge>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Edit Training</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Update a training or capacity building session.</p>
                        </div>
                        <!-- Back removed per UX rules for Edit pages -->
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                <Card class="w-full flex max-h-[760px] flex-col overflow-hidden border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <GraduationCap class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>Capture the training title, type, date, and delivery details.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex min-h-0 flex-1 flex-col gap-5 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="title">Title <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" placeholder="Enter training title" :class="inputErrorClass('title')" @input="clearError('title')" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <Label for="target_group">Target Group <span class="text-red-500">*</span></Label>
                                <Button id="target_group" type="button" variant="outline" class="h-11 w-full justify-between gap-3" :class="inputErrorClass('target_group')" @click="openTargetGroupDialog">
                                    <span class="min-w-0 truncate text-left text-sm">{{ selectedTargetGroupLabel }}</span>
                                    <span class="shrink-0 text-xs text-muted-foreground">{{ selectedTargetGroups.length }} selected</span>
                                </Button>
                                <div v-if="selectedTargetGroups.length" class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge v-for="group in selectedTargetGroups" :key="group" variant="secondary" class="max-w-full truncate">{{ group }}</Badge>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Choose one or more target groups from the modal.</p>
                                <p v-if="form.errors.target_group" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.target_group }}</p>
                            </div>
                            <div>
                                <Label for="date_conducted">Date Conducted <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="date_conducted" v-model="form.date_conducted" type="date" :class="inputErrorClass('date_conducted')" @input="clearError('date_conducted')" />
                                <p v-if="form.errors.date_conducted" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.date_conducted }}</p>
                            </div>
                            <div>
                                <Label for="venue">Venue <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="venue" v-model="form.venue" placeholder="Training venue" :class="inputErrorClass('venue')" @input="clearError('venue')" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.venue }}</p>
                            </div>
                            <div>
                                <Label for="facilitator">Facilitator <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="facilitator" v-model="form.facilitator" placeholder="Provider or facilitator" :class="inputErrorClass('facilitator')" @input="clearError('facilitator')" />
                                <p v-if="form.errors.facilitator" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.facilitator }}</p>
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
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.status }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="skills_targeted">Skills Targeted <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Textarea id="skills_targeted" v-model="form.skills_targeted" placeholder="Skills targeted by the training" :class="inputErrorClass('skills_targeted')" @input="clearError('skills_targeted')" />
                                <p v-if="form.errors.skills_targeted" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.skills_targeted }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Cooperative</CardTitle>
                        <CardDescription>Choose the cooperative or keep the locked context selection.</CardDescription>
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
                                    { 'border-red-500 focus-visible:ring-red-500': form.errors.coop_id },
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
                        </div>
                    </CardContent>
                </Card>

                <Card class="w-full min-h-87.5 border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Participants</CardTitle>
                        <CardDescription>Select the members who attended the training.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div
                            v-if="selectedCoopIds.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            <Users class="mx-auto mb-2 h-6 w-6" />
                            <p class="font-medium">No cooperative selected yet</p>
                            <p>Please select a cooperative above to view and add participants.</p>
                        </div>

                        <div v-else class="grid min-h-0 gap-4" :class="hasCooperativeSidebar ? 'md:grid-cols-[300px_minmax(0,1fr)] xl:grid-cols-[360px_minmax(0,1fr)]' : 'grid-cols-1'">
                            <div v-if="hasCooperativeSidebar" class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Cooperatives</p>
                                <div class="max-h-[24rem] space-y-2 overflow-y-auto rounded-xl border border-border bg-card p-2.5">
                                    <button
                                        v-for="group in selectedCooperativeGroups"
                                        :key="group.id"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg border-2 px-3.5 py-3 text-left transition-all duration-150 cursor-pointer"
                                        :class="activeCoopId === group.id
                                            ? 'border-primary bg-primary/15 text-foreground shadow-sm hover:shadow-md hover:bg-primary/20'
                                            : 'border-border/60 bg-white text-foreground hover:border-primary/50 hover:bg-primary/5 hover:shadow-sm dark:bg-muted/30 dark:border-border dark:hover:border-primary/40 dark:hover:bg-primary/10'"
                                        @click="setActiveCooperative(group.id)"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-sm leading-5 wrap-break-word">{{ group.name }}</p>
                                            <p class="text-xs text-muted-foreground mt-0.5">
                                                {{ selectedCountByCoop[group.id] || 0 }} / {{ group.members.length }} selected
                                            </p>
                                        </div>
                                        <div class="ml-2 shrink-0 flex items-center justify-center">
                                            <CheckCircle2
                                                v-if="isAllSelectedForCoop(group.id)"
                                                class="h-5 w-5 text-emerald-600 dark:text-emerald-500"
                                            />
                                            <MinusCircle
                                                v-else-if="isPartialSelectedForCoop(group.id)"
                                                class="h-5 w-5 text-amber-600 dark:text-amber-500"
                                            />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="flex min-h-0 w-full flex-col space-y-3 rounded-xl border border-border bg-card p-3" :class="hasCooperativeSidebar ? '' : 'md:col-span-2'">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-foreground">
                                            {{ activeCooperativeGroup?.name || selectedCooperatives[0]?.name }}
                                        </h3>
                                        <p class="text-sm text-muted-foreground">{{ activeMembers.length }} members total</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="button" variant="outline" size="sm" class="h-8 gap-1.5 text-xs" @click="selectAllInCurrentCoop">
                                            <Check class="h-3.5 w-3.5" />
                                            {{ selectAllLabel }}
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" class="h-8 gap-1.5 text-xs" @click="deselectAllInCurrentCoop">
                                            <X class="h-3.5 w-3.5" />
                                            Deselect All
                                        </Button>
                                    </div>
                                </div>

                                <div class="relative">
                                    <Search class="absolute left-3 top-2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="memberSearch"
                                        placeholder="Search available members..."
                                        class="h-8 w-full pl-9 text-sm"
                                    />
                                </div>

                                <div class="grid min-h-0 gap-0 overflow-hidden rounded-xl border border-border bg-card md:grid-cols-2 md:divide-x">
                                    <div class="relative w-full">
                                        <div class="flex items-center justify-between border-b bg-muted/30 p-3">
                                            <div class="flex items-center gap-2">
                                                <Users class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm font-semibold">Available Members</span>
                                            </div>
                                            <Badge variant="secondary">{{ unselectedMembersForCoop.length }} members</Badge>
                                        </div>
                                        <div ref="availableScrollRef" class="relative min-h-50 max-h-100 overflow-y-auto">
                                            <div v-if="isLoadingMembers" class="space-y-2 p-2">
                                                <div v-for="index in 8" :key="`available-skeleton-${index}`" class="h-10 animate-pulse rounded-md bg-muted/50" />
                                            </div>
                                            <div v-else-if="memberSearch.trim() && filteredAvailableMembers.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                                                No members found matching "{{ memberSearch.trim() }}"
                                            </div>
                                            <div v-else-if="unselectedMembersForCoop.length === 0" class="flex flex-col items-center py-8 text-muted-foreground">
                                                <CheckCircle2 class="mb-2 h-8 w-8 text-green-400" />
                                                <p class="text-sm font-medium">All members selected</p>
                                                <p class="text-xs">Use Deselect All to start over</p>
                                            </div>
                                            <div v-else>
                                                <div
                                                    v-for="(member, index) in filteredAvailableMembers"
                                                    :key="member.id"
                                                    role="button"
                                                    tabindex="0"
                                                    class="flex w-full cursor-pointer items-center gap-3 px-3 py-2 text-left transition-colors duration-150"
                                                    :class="[
                                                        index % 2 === 0 ? 'bg-white dark:bg-background' : 'bg-gray-50/80 dark:bg-muted/20',
                                                        'hover:bg-blue-50/60 dark:hover:bg-blue-900/10',
                                                    ]"
                                                    @click="addMemberSelection(member.id)"
                                                    @keydown.enter.prevent="addMemberSelection(member.id)"
                                                    @keydown.space.prevent="addMemberSelection(member.id)"
                                                >
                                                    <Checkbox
                                                        :model-value="false"
                                                        @update:model-value="() => addMemberSelection(member.id)"
                                                        class="h-4 w-4"
                                                    />
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ getMemberInitials(member) }}
                                                    </div>
                                                    <p class="min-w-0 truncate text-sm text-foreground">{{ formatMemberName(member) }}</p>
                                                </div>
                                            </div>
                                            <div v-if="hasAvailableOverflow" class="pointer-events-none absolute bottom-0 left-0 right-0 h-6 bg-linear-to-t from-background to-transparent" />
                                        </div>
                                    </div>

                                    <div class="relative w-full">
                                        <div class="flex items-center justify-between border-b bg-blue-50 p-3 dark:bg-blue-900/10">
                                            <div class="flex items-center gap-2">
                                                <UserCheck class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Selected Members</span>
                                            </div>
                                            <Badge class="border-blue-200 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ selectedMembersForCoop.length }} selected</Badge>
                                        </div>
                                        <div ref="selectedScrollRef" class="relative min-h-50 max-h-100 overflow-y-auto">
                                            <div v-if="isLoadingMembers" class="space-y-2 p-2">
                                                <div v-for="index in 8" :key="`selected-skeleton-${index}`" class="h-10 animate-pulse rounded-md bg-muted/50" />
                                            </div>
                                            <div v-else-if="selectedMembersForCoop.length === 0" class="flex flex-col items-center py-8 text-muted-foreground">
                                                <UserPlus class="mb-2 h-8 w-8 text-blue-300" />
                                                <p class="text-sm font-medium">No members selected yet</p>
                                                <p class="text-xs">Click members on the left to add them</p>
                                            </div>
                                            <div v-else>
                                                <div
                                                    v-for="(member, index) in selectedMembersForCoop"
                                                    :key="member.id"
                                                    role="button"
                                                    tabindex="0"
                                                    class="flex w-full cursor-pointer items-center gap-3 px-3 py-2 text-left transition-colors duration-150"
                                                    :class="[
                                                        index % 2 === 0 ? 'bg-blue-50/40 dark:bg-blue-900/5' : 'bg-blue-50/70 dark:bg-blue-900/10',
                                                        'hover:bg-blue-100/60 dark:hover:bg-blue-900/20',
                                                    ]"
                                                    @click="removeMemberSelection(member.id)"
                                                    @keydown.enter.prevent="removeMemberSelection(member.id)"
                                                    @keydown.space.prevent="removeMemberSelection(member.id)"
                                                >
                                                    <Checkbox
                                                        :model-value="true"
                                                        @update:model-value="() => removeMemberSelection(member.id)"
                                                        class="h-4 w-4"
                                                    />
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                        {{ getMemberInitials(member) }}
                                                    </div>
                                                    <p class="min-w-0 truncate text-sm font-medium text-foreground">{{ formatMemberName(member) }}</p>
                                                    <button
                                                        type="button"
                                                        class="ml-auto rounded p-1 text-muted-foreground transition-colors hover:text-red-500"
                                                        title="Remove from selection"
                                                        @click.stop="removeMemberSelection(member.id)"
                                                    >
                                                        <X class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div v-if="hasSelectedOverflow" class="pointer-events-none absolute bottom-0 left-0 right-0 h-6 bg-linear-to-t from-background to-transparent" />
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-3 text-sm font-medium text-muted-foreground">
                                    Total selected: {{ form.member_ids.length }} members across {{ selectedCoopCount }} cooperatives
                                </div>

                                <!-- summary badges removed to reduce clutter when many cooperatives are selected -->

                                <p v-if="form.errors.member_ids" class="text-sm text-red-500">{{ form.errors.member_ids }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Attachments / Supporting Documents</CardTitle>
                        <CardDescription>Manage outcome documents and image attachments for this training.</CardDescription>
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
                                            <p class="text-xs text-muted-foreground">Upload outcome documents. Max 3 files, 5MB each.</p>
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
                                    <p v-if="form.errors.outcomes_attachments" class="text-sm text-red-500">{{ form.errors.outcomes_attachments }}</p>
                                </div>

                                <div class="space-y-4 rounded-xl border border-dashed border-border/70 bg-muted/20 p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background text-purple-500">
                                            <Image class="h-5 w-5" />
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-foreground">Photo / Image Attachments <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></h3>
                                            <p class="text-xs text-muted-foreground">Upload photos. Max 3 images, 5MB each. JPG, PNG, GIF, WEBP only.</p>
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
                                                @click="removeExistingImage(image.path || '')"
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
                                                <p class="text-xs text-white/70">{{ formatFileSize(image.size || 0) }}</p>
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
                        <CardDescription>Keep follow-up notes and scheduling details together.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="no_of_participants">No. of Participants <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                    <Input id="no_of_participants" v-model="form.no_of_participants" type="number" min="0" placeholder="0" readonly class="bg-muted/50" :class="inputErrorClass('no_of_participants')" />
                                <p v-if="form.errors.no_of_participants" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.no_of_participants }}</p>
                            </div>
                            <div>
                                <Label for="follow_up_date">Follow-up Date <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="follow_up_date" v-model="form.follow_up_date" type="date" :class="inputErrorClass('follow_up_date')" @input="clearError('follow_up_date')" />
                                <p v-if="form.errors.follow_up_date" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_date }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_remarks">Follow-up Remarks <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input id="follow_up_remarks" v-model="form.follow_up_remarks" placeholder="Follow-up remarks" :class="inputErrorClass('follow_up_remarks')" @input="clearError('follow_up_remarks')" />
                                <p v-if="form.errors.follow_up_remarks" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_remarks }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_needed" class="flex items-center gap-2 text-sm text-foreground/80">
                                    <Checkbox id="follow_up_needed" v-model:checked="form.follow_up_needed" @update:checked="clearError('follow_up_needed')" />
                                    <span>Follow-up needed <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></span>
                                </Label>
                                <p v-if="form.errors.follow_up_needed" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_needed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="cancel" type="button" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Cancel
                    </Button>
                    <Button v-if="canUpdateTraining" type="submit" :disabled="form.processing" class="gap-2">
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
            description="Search and filter cooperatives, then choose the cooperative for this training record."
            confirm-label="Confirm"
            cancel-label="Cancel"
            @update:open="(value) => isCooperativeDialogOpen = value"
            @confirm="syncSelectedCooperatives"
        />

        <TargetGroupMultiSelectDialog
            :open="isTargetGroupDialogOpen"
            :selected-values="selectedTargetGroups"
            :options="trainingTargetGroupOptions"
            title="Select Target Groups"
            description="Choose one or more target groups for this training session."
            @update:open="(value) => isTargetGroupDialogOpen = value"
            @confirm="confirmTargetGroups"
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