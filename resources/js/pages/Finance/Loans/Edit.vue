<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { ArrowLeft, Eye, File, FileText, Image, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { useCreateBack } from '@/composables/useCreateBack';

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
        cooperative?: { id: number; name: string } | null;
    };
    from?: string | null;
    cooperative_id?: number | null;
}>();

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const coopContextId = computed(() => coopIdFromUrl.value || props.loan.cooperative?.id || props.cooperative_id || props.loan.coop_id || null);
const isCoopContext = computed(() => coopContextId.value !== null);

const backHref = computed(() => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance`;
    }

    return `/finance/loans/${props.loan.id}${props.from === 'coop' && props.cooperative_id ? `?coop_id=${props.cooperative_id}` : ''}`;
});

const fallbackHref = computed(() => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance`;
    }

    return `/finance/loans/${props.loan.id}${props.from === 'coop' && props.cooperative_id ? `?coop_id=${props.cooperative_id}` : ''}`;
});

const { goBack, returnToHref } = useCreateBack({ fallbackHref });

const form = useForm({
    return_to: returnToHref.value,
    interest_rate: Number(props.loan.interest_rate),
    term_months: props.loan.term_months,
    purpose: props.loan.purpose || '',
    status: props.loan.status,
    attachments: [] as File[],
    attachments_removed: [] as string[],
});

const previewUrls = new Map<File, string>();
const existingAttachments = ref<LoanAttachment[]>([...(props.loan.attachments || [])]);
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;

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

const newAttachmentItems = computed(() => form.attachments.map((file, index) => ({
    file,
    index,
    name: file.name,
    sizeLabel: formatFileSize(file.size),
    label: getFileLabel(file.name),
    icon: getFileIcon(file.name),
})));

const handleNewFiles = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const selectedFiles = target.files ? Array.from(target.files) : [];

    form.attachments = selectedFiles.filter((file) => file.size <= MAX_FILE_SIZE_BYTES);

    target.value = '';
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

    <FinanceShellLayout active-tab="loans" :hide-tabs="isCoopContext">
        <div class="max-w-2xl space-y-6">
            <div v-if="isCoopContext" class="flex items-center justify-between gap-4">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ loan.cooperative?.name || 'Cooperative' }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Edit Loan #{{ loan.id }}</span>
                </nav>
                <Link :href="backHref">
                    <Button variant="outline" size="sm" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Button>
                </Link>
            </div>
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
                    <div v-if="existingAttachments.length > 0">
                        <p class="mb-2 text-sm font-medium">Current Attachments</p>
                        <ul class="space-y-1">
                            <li v-for="file in existingAttachments" :key="file.path" class="flex items-center gap-2">
                                <a :href="file.url" target="_blank" class="text-primary text-sm underline">{{ file.name }}</a>
                                <button type="button" @click="removeExistingAttachment(file.path)" class="text-xs text-red-500">Remove</button>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-3">
                        <label class="text-sm font-medium">Add New Files (Optional)</label>
                        <input type="file" multiple @change="handleNewFiles" class="mt-1 block w-full text-sm" />
                    </div>

                    <div v-if="newAttachmentItems.length > 0" class="mt-3 space-y-2">
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

                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        Save
                    </button>
                    <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="goBack">Cancel</button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
