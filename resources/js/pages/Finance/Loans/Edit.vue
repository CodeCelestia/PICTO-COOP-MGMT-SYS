<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormUx } from '@/composables/useFormUx';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Eye, File, FileText, Image, Paperclip, Save, ShieldCheck, Trash2, Upload, Users } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';
import Swal from 'sweetalert2';

type LoanAttachment = {
    path: string;
    name: string;
    url: string;
    extension: string;
};

const props = defineProps<{
    loan: {
        id: number;
        coop_id?: number;
        principal: string;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        status: string;
        member?: { first_name?: string; last_name?: string };
        loanType?: { name?: string };
        cooperative?: { id: number; name: string } | null;
        attachments?: LoanAttachment[];
    };
    from?: string | null;
    cooperative_id?: number | null;
}>();

const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');
const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const coopIdFromUrl = computed(() => {
    const coopId = queryParams.value.get('coop_id');
    return coopId ? parseInt(coopId, 10) : null;
});

const coopContextId = computed(() => coopIdFromUrl.value || props.loan.coop_id || props.cooperative_id || props.loan.cooperative?.id || null);
const isCoopContext = computed(() => Boolean(window.location.pathname.startsWith('/cooperatives/') || coopContextId.value));
const cooperativeName = computed(() => props.loan.cooperative?.name || 'Cooperative');

const returnToParam = computed(() => {
    const candidate = queryParams.value.get('return_to');
    if (!candidate || !candidate.startsWith('/') || candidate.startsWith('//')) {
        return '';
    }

    return candidate;
});

const backHref = computed(() => {
    if (returnToParam.value) {
        return returnToParam.value;
    }

    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance&subtab=loans`;
    }

    return '/finance/loans';
});

const form = useForm({
    return_to: backHref.value,
    interest_rate: Number(props.loan.interest_rate),
    term_months: props.loan.term_months,
    purpose: props.loan.purpose || '',
    status: props.loan.status,
    attachments: [] as File[],
    attachments_removed: [] as string[],
});

const { isDirty, inputErrorClass, clearError, handleCancel, markClean, scrollToFirstError, triggerErrorShake, showErrorShake } = useFormUx(form);

const previewUrls = new Map<File, string>();
const existingAttachments = ref<LoanAttachment[]>([...(props.loan.attachments || [])]);
const MAX_FILE_SIZE_BYTES = 5 * 1024 * 1024;

const formatFileSize = (bytes: number) => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
};

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

const getAttachmentIcon = (attachment: LoanAttachment) => {
    const extension = attachment.extension.toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
        return Image;
    }

    if (extension === 'pdf') {
        return FileText;
    }

    return File;
};

const getNewAttachmentLabel = (name: string) => {
    const extension = name.split('.').pop()?.toUpperCase() || 'FILE';

    if (['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'BMP', 'SVG'].includes(extension)) {
        return 'IMG';
    }

    if (extension === 'PDF') {
        return 'PDF';
    }

    return extension.slice(0, 4) || 'FILE';
};

const getNewAttachmentIcon = (name: string) => {
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
    label: getNewAttachmentLabel(file.name),
    icon: getNewAttachmentIcon(file.name),
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

const triggerFileInput = () => {
    if (typeof document !== 'undefined') {
        document.getElementById('loan-edit-upload')?.click();
    }
};

const getSubmitUrl = () => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/loans/${props.loan.id}`;
    }

    return `/finance/loans/${props.loan.id}`;
};

const goBack = async () => {
    if (isDirty.value) {
        const result = await Swal.fire({
            title: 'Discard these loan changes?',
            text: 'Any unsaved changes will be lost.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing',
        });

        if (!result.isConfirmed) {
            return;
        }
    }

    router.get(backHref.value);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        return_to: backHref.value,
        attachments: data.attachments.filter((file): file is File => Boolean(file)),
        attachments_removed: data.attachments_removed,
    })).put(getSubmitUrl(), {
        forceFormData: true,
        onSuccess: () => {
            markClean();
        },
        onError: () => {
            triggerErrorShake();
            scrollToFirstError();
        },
    });
};

onUnmounted(() => {
    previewUrls.forEach((url) => URL.revokeObjectURL(url));
    previewUrls.clear();
});
</script>

<template>
    <Head :title="`Finance - Edit Loan #${loan.id}`" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex flex-col gap-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <div v-if="isCoopContext" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                            <span>/</span>
                            <a :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ cooperativeName }}</a>
                            <span>/</span>
                            <span class="text-foreground">Edit Loan</span>
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-semibold tracking-tight">Edit Loan #{{ loan.id }}</h1>
                            <p class="max-w-2xl text-sm text-muted-foreground">
                                Update the repayment terms, status, and supporting notes.
                            </p>
                        </div>
                        <div v-if="isCoopContext" class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="gap-1 rounded-full px-3 py-1 text-xs font-medium">
                                <ShieldCheck class="h-3.5 w-3.5" />
                                Cooperative-scoped edit
                            </Badge>
                        </div>
                    </div>

                    <Button variant="outline" class="gap-2 self-start lg:self-auto" @click="goBack">
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <form class="space-y-6" @submit.prevent="submit">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl">Loan Snapshot</CardTitle>
                        <CardDescription>These fields are informational and not edited on this screen.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-border bg-background p-4 text-sm">
                            <div class="text-muted-foreground">Member</div>
                            <div class="mt-1 font-semibold text-foreground">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-background p-4 text-sm">
                            <div class="text-muted-foreground">Loan Type</div>
                            <div class="mt-1 font-semibold text-foreground">{{ loan.loanType?.name || 'N/A' }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-background p-4 text-sm">
                            <div class="text-muted-foreground">Principal</div>
                            <div class="mt-1 font-semibold text-foreground">₱{{ Number(loan.principal).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</div>
                        </div>
                        <div class="rounded-xl border border-border bg-background p-4 text-sm">
                            <div class="text-muted-foreground">Current Status</div>
                            <div class="mt-1 font-semibold text-foreground">{{ loan.status }}</div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Users class="h-5 w-5" />
                            Repayment Terms
                        </CardTitle>
                        <CardDescription>Adjust the values that control repayment calculations and lifecycle state.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="interest_rate">Interest Rate (%)</Label>
                            <Input
                                id="interest_rate"
                                v-model="form.interest_rate"
                                type="number"
                                min="0"
                                max="50"
                                step="0.01"
                                :class="inputErrorClass('interest_rate')"
                                @input="clearError('interest_rate')"
                            />
                            <p class="text-xs text-muted-foreground">Annual rate used when the repayment schedule is generated.</p>
                            <p v-if="form.errors.interest_rate" class="text-xs text-destructive">{{ form.errors.interest_rate }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="term_months">Term (Months)</Label>
                            <Input
                                id="term_months"
                                v-model="form.term_months"
                                type="number"
                                min="1"
                                max="60"
                                step="1"
                                :class="inputErrorClass('term_months')"
                                @input="clearError('term_months')"
                            />
                            <p class="text-xs text-muted-foreground">The schedule runs for the selected number of months.</p>
                            <p v-if="form.errors.term_months" class="text-xs text-destructive">{{ form.errors.term_months }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger :class="inputErrorClass('status')">
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Pending">Pending</SelectItem>
                                    <SelectItem value="Approved">Approved</SelectItem>
                                    <SelectItem value="Active">Active</SelectItem>
                                    <SelectItem value="Completed">Completed</SelectItem>
                                    <SelectItem value="Defaulted">Defaulted</SelectItem>
                                    <SelectItem value="Rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">Use the status that matches the current loan lifecycle.</p>
                            <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl">Loan Notes</CardTitle>
                        <CardDescription>Update the purpose and manage supporting documents.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-2">
                            <Label for="purpose">Purpose</Label>
                            <Textarea
                                id="purpose"
                                v-model="form.purpose"
                                rows="4"
                                placeholder="Explain the loan purpose or any reviewer notes"
                                :class="inputErrorClass('purpose')"
                                @input="clearError('purpose')"
                            />
                            <p class="text-xs text-muted-foreground">Optional, but helpful for future review.</p>
                            <p v-if="form.errors.purpose" class="text-xs text-destructive">{{ form.errors.purpose }}</p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <Label>Attachments</Label>
                                <span class="text-xs text-muted-foreground">Maximum file size: 5MB per file</span>
                            </div>

                            <div v-if="existingAttachments.length > 0" class="space-y-3">
                                <div v-for="file in existingAttachments" :key="file.path" class="flex flex-col gap-3 rounded-xl border border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">{{ getAttachmentLabel(file) }}</Badge>
                                        <component :is="getAttachmentIcon(file)" class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground sm:hidden" />
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-foreground">{{ file.name }}</p>
                                            <p class="text-xs text-muted-foreground">Existing file</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                        <Button type="button" variant="outline" size="sm" class="gap-2" @click="openAttachmentPreview(file.url)">
                                            <Eye class="h-3.5 w-3.5" />
                                            Preview
                                        </Button>
                                        <Button type="button" variant="destructive" size="sm" class="gap-2" @click="removeExistingAttachment(file.path)">
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Remove
                                        </Button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-dashed border-border bg-muted/20 p-4">
                                <div class="flex flex-wrap items-center gap-3">
                                    <input type="file" multiple class="hidden" id="loan-edit-upload" @change="handleNewFiles" />
                                    <Button type="button" class="gap-2 bg-foreground text-background hover:bg-foreground/90" @click="triggerFileInput()">
                                        <Upload class="h-4 w-4" />
                                        Add Files
                                    </Button>
                                    <p class="text-xs text-muted-foreground">Upload new supporting documents if the loan file changed.</p>
                                </div>

                                <div v-if="newAttachmentItems.length > 0" class="mt-4 space-y-3">
                                    <div v-for="attachment in newAttachmentItems" :key="`${attachment.name}-${attachment.index}`" class="flex flex-col gap-3 rounded-xl border border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-start gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-border bg-muted text-xs font-semibold text-foreground">
                                                {{ attachment.label }}
                                            </div>
                                            <component :is="attachment.icon" class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground sm:hidden" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-medium text-foreground">{{ attachment.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ attachment.sizeLabel }}</p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                            <Button type="button" variant="outline" size="sm" class="gap-2" @click="openAttachmentPreview(getAttachmentPreviewUrl(attachment.file))">
                                                <Eye class="h-3.5 w-3.5" />
                                                Preview
                                            </Button>
                                            <Button type="button" variant="destructive" size="sm" class="gap-2" @click="removeNewAttachment(attachment.index)">
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.attachments" class="text-xs text-destructive">{{ form.errors.attachments }}</p>
                            <p v-if="form.errors['attachments.0']" class="text-xs text-destructive">{{ form.errors['attachments.0'] }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="flex flex-col gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Save updates to the loan record and keep the repayments in sync.
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <Button type="button" variant="outline" class="gap-2" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />
                                Cancel
                            </Button>
                            <Button type="submit" class="gap-2" :disabled="form.processing">
                                <Save class="h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <div v-if="showErrorShake" class="sr-only">Validation error</div>
        </div>
    </FinanceShellLayout>
</template>
