<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import { ArrowLeft, Eye, File, FileText, Image, Plus, Trash2 } from 'lucide-vue-next';
import { useCreateBack } from '@/composables/useCreateBack';

const props = defineProps<{
    members: Array<{
        id: number;
        first_name: string;
        last_name: string;
        coop_id: number;
        cooperative?: {
            classification: 'micro' | 'small' | 'medium' | 'large' | null;
        } | null;
    }>;
    loanTypes: Array<{
        id: number;
        name: string;
        cooperative_id: number;
        classification: 'micro' | 'small' | 'medium' | 'large' | null;
    }>;
    cooperatives: Array<{
        id: number;
        name: string;
        classification: 'micro' | 'small' | 'medium' | 'large' | null;
        members: Array<{ id: number; first_name: string; last_name: string; coop_id: number }>;
        loan_types: Array<{ id: number; name: string; cooperative_id: number; classification: 'micro' | 'small' | 'medium' | 'large' | null }>;
    }>;
    showCooperativePicker: boolean;
    preselectedCoopId?: number | null;
    preselectedMemberId?: number | null;
    preselectedCoop?: { id: number; name: string; members: any[]; loanTypes: any[] } | null;
}>();

const { goBack, returnToHref } = useCreateBack({ fallbackHref: '/finance/loans' });

const form = useForm({
    coop_id: props.preselectedCoopId ?? '',
    return_to: returnToHref.value,
    member_id: props.preselectedMemberId ?? '',
    loan_type_id: '',
    principal: '',
    purpose: '',
    attachments: [] as File[],
});

const attachmentInputRef = ref<HTMLInputElement | null>(null);
const previewUrls = new Map<File, string>();
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;

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

const removeAttachment = (index: number) => {
    form.attachments.splice(index, 1);
};

const getAttachmentPreviewUrl = (file: File) => {
    const existingUrl = previewUrls.get(file);
    if (existingUrl) {
        return existingUrl;
    }

    const objectUrl = URL.createObjectURL(file);
    previewUrls.set(file, objectUrl);
    return objectUrl;
};

const openAttachmentPreview = (file: File) => {
    window.open(getAttachmentPreviewUrl(file), '_blank', 'noopener,noreferrer');
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

const getFileCardIcon = (name: string) => {
    const extension = name.split('.').pop()?.toLowerCase() || '';

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
        return Image;
    }

    if (extension === 'pdf') {
        return FileText;
    }

    return File;
};

const attachmentItems = computed(() => form.attachments.map((file, index) => ({
    file,
    index,
    label: getFileLabel(file.name),
    sizeLabel: formatFileSize(file.size),
    icon: getFileCardIcon(file.name),
})));

onUnmounted(() => {
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls.clear();
});

const isFromCoopContext = computed(() => !!props.preselectedCoopId);

const selectedCooperative = computed(() => {
    if (isFromCoopContext.value) {
        return props.preselectedCoop || null;
    }

    if (!props.showCooperativePicker) {
        return null;
    }

    return props.cooperatives.find((cooperative) => String(cooperative.id) === String(form.coop_id)) || null;
});

const availableMembers = computed(() => {
    if (isFromCoopContext.value) {
        return props.preselectedCoop?.members || [];
    }

    if (props.showCooperativePicker) {
        return selectedCooperative.value?.members || [];
    }

    return props.members;
});

const selectedMember = computed(() => {
    return availableMembers.value.find((member) => String(member.id) === String(form.member_id)) || null;
});

const filteredLoanTypes = computed(() => {
    if (isFromCoopContext.value) {
        return props.preselectedCoop?.loanTypes || [];
    }

    const sourceLoanTypes = props.showCooperativePicker
        ? (selectedCooperative.value?.loan_types || [])
        : props.loanTypes;

    if (!selectedMember.value && !props.showCooperativePicker) {
        return props.loanTypes;
    }

    if (props.showCooperativePicker && !selectedCooperative.value) {
        return [];
    }

    const cooperativeClassification = props.showCooperativePicker
        ? selectedCooperative.value?.classification || null
        : selectedMember.value?.cooperative?.classification || null;

    return sourceLoanTypes.filter((loanType) => {
        if (selectedMember.value && loanType.cooperative_id !== selectedMember.value.coop_id) {
            return false;
        }

        if (!cooperativeClassification) {
            return true;
        }

        return !loanType.classification || loanType.classification === cooperativeClassification;
    });
});

watch(() => form.coop_id, () => {
    if (isFromCoopContext.value || !props.showCooperativePicker) {
        return;
    }

    form.member_id = '';
    form.loan_type_id = '';
});

watch(filteredLoanTypes, (loanTypes) => {
    if (!form.loan_type_id) {
        return;
    }

    const exists = loanTypes.some((loanType) => String(loanType.id) === String(form.loan_type_id));
    if (!exists) {
        form.loan_type_id = '';
    }
});

const submit = () => {
    if (props.showCooperativePicker && !form.coop_id) {
        form.setError('coop_id', 'Please select a cooperative first.');
        return;
    }

    form.post('/finance/loans', {
        forceFormData: true,
    });
};

const handleBackClick = () => {
    if (isFromCoopContext.value && props.preselectedCoopId) {
        window.location.href = `/cooperatives/${props.preselectedCoopId}?tab=members`;
    } else {
        goBack();
    }
};
</script>

<template>
    <Head title="Finance - Create Loan" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isFromCoopContext">
        <div class="max-w-3xl space-y-6">
            <div v-if="isFromCoopContext" class="flex items-center justify-between gap-4">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${preselectedCoopId}`" class="text-primary hover:underline">{{ preselectedCoop?.name }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">New Loan</span>
                </nav>
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm" @click="handleBackClick">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
            </div>
            <div v-else class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">New Member Loan</h1>
                    <p class="text-sm text-muted-foreground">Fill out this short form to submit a loan application.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm" @click="handleBackClick">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
            </div>

            <form class="space-y-5 rounded-lg border bg-card p-5" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium">Cooperative</label>
                    <div v-if="isFromCoopContext" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        {{ preselectedCoop?.name || 'Selected cooperative' }}
                    </div>
                    <template v-else>
                        <select v-model="form.coop_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="">Select cooperative</option>
                            <option v-for="cooperative in cooperatives" :key="cooperative.id" :value="cooperative.id">
                                {{ cooperative.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">Select a cooperative first to load members and loan types.</p>
                        <div v-if="form.errors.coop_id" class="mt-1 text-xs text-red-600">{{ form.errors.coop_id }}</div>
                    </template>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Member</label>
                    <div v-if="isFromCoopContext" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        {{ selectedMember ? `${selectedMember.first_name} ${selectedMember.last_name}` : 'Selected member' }}
                    </div>
                    <template v-else>
                        <select v-model="form.member_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" :disabled="showCooperativePicker && !form.coop_id">
                            <option value="">Select member</option>
                            <option v-for="member in availableMembers" :key="member.id" :value="member.id">
                                {{ member.first_name }} {{ member.last_name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">Choose the member requesting the loan.</p>
                        <div v-if="form.errors.member_id" class="mt-1 text-xs text-red-600">{{ form.errors.member_id }}</div>
                    </template>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Loan Type</label>
                    <select v-model="form.loan_type_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" :disabled="showCooperativePicker && !form.coop_id && !isFromCoopContext">
                        <option value="">Select loan type</option>
                        <option v-for="loanType in filteredLoanTypes" :key="loanType.id" :value="loanType.id">
                            {{ loanType.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-muted-foreground">Loan types are filtered by the selected member's cooperative and classification tier.</p>
                    <div v-if="form.errors.loan_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.loan_type_id }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Loan Amount</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">₱</span>
                        <input
                            v-model="form.principal"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-md border py-2 pl-8 pr-3 text-sm"
                        />
                    </div>
                    <div v-if="form.errors.principal" class="mt-1 text-xs text-red-600">{{ form.errors.principal }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Purpose</label>
                    <textarea
                        v-model="form.purpose"
                        rows="4"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                        placeholder="Briefly describe the reason for this loan application"
                    ></textarea>
                    <div v-if="form.errors.purpose" class="mt-1 text-xs text-red-600">{{ form.errors.purpose }}</div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium">File Attachments</label>
                        <span class="text-xs text-muted-foreground">Maximum file size: 5MB per file</span>
                    </div>

                    <input ref="attachmentInputRef" type="file" multiple class="hidden" @change="onAttachmentsChange" />

                    <div class="space-y-3 rounded-lg border border-dashed border-border bg-muted/20 p-4">
                        <div v-if="attachmentItems.length === 0" class="rounded-md border border-border bg-background px-4 py-6 text-center text-sm text-muted-foreground">
                            No files selected yet.
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="item in attachmentItems" :key="item.file.name + item.file.size + item.index" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-xs font-semibold text-foreground">
                                        {{ item.label }}
                                    </div>
                                    <component :is="item.icon" class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground sm:hidden" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ item.file.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ item.sizeLabel }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground hover:bg-muted" @click="openAttachmentPreview(item.file)">
                                        <Eye class="h-3.5 w-3.5" />
                                        Preview
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-md border border-destructive/30 px-3 py-2 text-xs font-medium text-destructive hover:bg-destructive/5" @click="removeAttachment(item.index)">
                                        <Trash2 class="h-3.5 w-3.5" />
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="inline-flex items-center gap-2 rounded-md bg-foreground px-3 py-2 text-sm font-medium text-background hover:bg-foreground/90" @click="triggerAttachmentPicker">
                                <Plus class="h-4 w-4" />
                                Add File
                            </button>
                            <p class="text-xs text-muted-foreground">You may upload one or more supporting files.</p>
                        </div>
                    </div>

                    <div v-if="form.errors.attachments" class="mt-1 text-xs text-red-600">{{ form.errors.attachments }}</div>
                    <div v-if="form.errors['attachments.0']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.0'] }}</div>
                </div>

                <div class="flex items-center gap-3 border-t pt-4">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        {{ form.processing ? 'Submitting...' : 'Submit Loan Application' }}
                    </button>
                    <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="handleBackClick">Cancel</button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
