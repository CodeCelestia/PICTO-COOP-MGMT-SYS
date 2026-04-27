<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { ArrowLeft, Eye, File, FileText, Image, Plus, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';

type LoanAttachment = {
    path: string;
    name: string;
    url: string;
    extension: string;
};

const props = defineProps<{
    loan: {
        id: number;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        status: string;
        attachments?: LoanAttachment[];
    };
    from?: string | null;
    cooperative_id?: number | null;
}>();

const form = useForm({
    interest_rate: Number(props.loan.interest_rate),
    term_months: props.loan.term_months,
    purpose: props.loan.purpose || '',
    status: props.loan.status,
    attachments: [] as File[],
    attachments_removed: [] as string[],
});

const attachmentInputRef = ref<HTMLInputElement | null>(null);
const previewUrls = new Map<File, string>();
const existingAttachments = ref<LoanAttachment[]>([...(props.loan.attachments || [])]);
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;

const backHref = computed(() => `/finance/loans/${props.loan.id}${props.from === 'coop' && props.cooperative_id ? `?from=coop&cooperative_id=${props.cooperative_id}` : ''}`);

const getAttachmentLabel = (attachment: LoanAttachment) => {
    const extension = attachment.extension.toUpperCase();

    if (['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'BMP', 'SVG'].includes(extension)) {
        return 'IMG';
    }

    if (extension === 'PDF') {
        return 'PDF';
    }

    return extension || 'FILE';
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

const getFileLabel = (name: string) => {
    const extension = name.split('.').pop()?.toUpperCase() || 'FILE';

    if (['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'BMP', 'SVG'].includes(extension)) {
        return 'IMG';
    }

    if (extension === 'PDF') {
        return 'PDF';
    }

    return extension.slice(0, 4) || 'FILE';
};

const getFileIcon = (name: string) => {
    const extension = name.split('.').pop()?.toLowerCase() || '';

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
        return Image;
    }

    if (extension === 'pdf') {
        return FileText;
    }

    return File;
};

const getAttachmentPreviewUrl = (file: File) => {
    const existing = previewUrls.get(file);
    if (existing) {
        return existing;
    }

    const url = URL.createObjectURL(file);
    previewUrls.set(file, url);
    return url;
};

const openAttachmentPreview = (url: string) => {
    window.open(url, '_blank', 'noopener,noreferrer');
};

const existingAttachmentItems = computed(() => existingAttachments.value.map((attachment) => ({
    ...attachment,
    label: getAttachmentLabel(attachment),
    icon: getFileIcon(attachment.name),
})));

const newAttachmentItems = computed(() => form.attachments.map((file, index) => ({
    file,
    index,
    name: file.name,
    sizeLabel: formatFileSize(file.size),
    label: getFileLabel(file.name),
    icon: getFileIcon(file.name),
})));

const onAttachmentsChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const selectedFiles = target.files ? Array.from(target.files) : [];

    selectedFiles.forEach((file) => {
        if (file.size > MAX_FILE_SIZE_BYTES) {
            return;
        }

        form.attachments.push(file);
    });

    target.value = '';
};

const triggerAttachmentPicker = () => {
    attachmentInputRef.value?.click();
};

const removeNewAttachment = (index: number) => {
    form.attachments.splice(index, 1);
};

const removeExistingAttachment = (path: string) => {
    existingAttachments.value = existingAttachments.value.filter((attachment) => attachment.path !== path);
    if (!form.attachments_removed.includes(path)) {
        form.attachments_removed.push(path);
    }
};

onUnmounted(() => {
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls.clear();
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        attachments: data.attachments.filter((file): file is File => Boolean(file)),
        attachments_removed: data.attachments_removed,
    })).put(`/finance/loans/${props.loan.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Finance - Edit Loan #${loan.id}`" />

    <FinanceShellLayout active-tab="loans">
        <div class="max-w-2xl space-y-6">
            <h1 class="text-2xl font-semibold">Edit Loan #{{ loan.id }}</h1>

            <form class="space-y-4 rounded-lg border bg-card p-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Interest Rate (%)</label>
                    <input v-model.number="form.interest_rate" type="number" min="0" max="50" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Term (Months)</label>
                    <input v-model.number="form.term_months" type="number" min="1" max="60" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Status</label>
                    <select v-model="form.status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="Defaulted">Defaulted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Purpose</label>
                    <textarea v-model="form.purpose" rows="3" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm">File Attachments</label>
                        <span class="text-xs text-muted-foreground">Maximum file size: 5MB per file</span>
                    </div>

                    <input ref="attachmentInputRef" type="file" multiple class="hidden" @change="onAttachmentsChange" />

                    <div class="space-y-3 rounded-lg border border-dashed border-border bg-muted/20 p-4">
                        <div class="flex items-center gap-2">
                            <button type="button" class="inline-flex items-center gap-2 rounded-md bg-foreground px-3 py-2 text-sm font-medium text-background hover:bg-foreground/90" @click="triggerAttachmentPicker">
                                <Plus class="h-4 w-4" />
                                Add File
                            </button>
                            <p class="text-xs text-muted-foreground">You may upload one or more supporting files.</p>
                        </div>

                        <div v-if="existingAttachmentItems.length === 0 && newAttachmentItems.length === 0" class="rounded-md border border-border bg-background px-4 py-6 text-center text-sm text-muted-foreground">
                            No files attached yet.
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="attachment in existingAttachmentItems" :key="attachment.path" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">{{ attachment.label }}</Badge>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ attachment.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ attachment.path }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground hover:bg-muted" @click="openAttachmentPreview(attachment.url)">
                                        <Eye class="h-3.5 w-3.5" />
                                        Preview
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-destructive/30 px-3 py-2 text-xs font-medium text-destructive hover:bg-destructive/5" @click="removeExistingAttachment(attachment.path)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                </div>
                            </div>

                            <div v-for="attachment in newAttachmentItems" :key="`${attachment.name}-${attachment.index}`" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">{{ attachment.label }}</Badge>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ attachment.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ attachment.sizeLabel }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground hover:bg-muted" @click="openAttachmentPreview(getAttachmentPreviewUrl(attachment.file))">
                                        <Eye class="h-3.5 w-3.5" />
                                        Preview
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-destructive/30 px-3 py-2 text-xs font-medium text-destructive hover:bg-destructive/5" @click="removeNewAttachment(attachment.index)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        Save
                    </button>
                    <Link :href="backHref" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
