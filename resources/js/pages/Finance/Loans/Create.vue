<script setup lang="ts">
import MemberSelectDialog from '@/components/Officers/MemberSelectDialog.vue';
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
import { ArrowLeft, Eye, File, FileText, Image, Paperclip, Plus, ShieldCheck, Trash2, Users } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';
import Swal from 'sweetalert2';

type LoanTypeOption = {
    id: number;
    name: string;
    cooperative_id: number;
    classification: 'micro' | 'small' | 'medium' | 'large' | null;
};

type CooperativeMemberOption = {
    id: number;
    first_name: string;
    last_name: string;
    coop_id: number;
    cooperative?: {
        classification: 'micro' | 'small' | 'medium' | 'large' | null;
    } | null;
};

type CooperativeOption = {
    id: number;
    name: string;
    classification: 'micro' | 'small' | 'medium' | 'large' | null;
    members: CooperativeMemberOption[];
    loan_types: LoanTypeOption[];
};

type PreselectedCooperativeOption = {
    id: number;
    name: string;
    classification: 'micro' | 'small' | 'medium' | 'large' | null;
    members: CooperativeMemberOption[];
    loanTypes: LoanTypeOption[];
};

type MemberDialogOption = {
    id: number;
    name: string;
    coop_id: number;
    role_names: string[];
    member_code?: string | null;
    gender?: string | null;
    date_joined?: string | null;
    status?: string | null;
    first_name?: string | null;
    last_name?: string | null;
};

const props = defineProps<{
    members: CooperativeMemberOption[];
    loanTypes: LoanTypeOption[];
    cooperatives: CooperativeOption[];
    showCooperativePicker: boolean;
    preselectedCoopId?: number | null;
    preselectedMemberId?: number | null;
    preselectedCoop?: PreselectedCooperativeOption | null;
}>();

const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');
const coopContextId = computed(() => props.preselectedCoopId || props.preselectedCoop?.id || null);
const isFromCoopContext = computed(() => Boolean(props.preselectedCoopId));
const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const returnToParam = computed(() => {
    const candidate = queryParams.value.get('return_to');
    if (!candidate || !candidate.startsWith('/') || candidate.startsWith('//')) {
        return '';
    }

    return candidate;
});

const form = useForm({
    coop_id: props.preselectedCoopId ?? '',
    return_to: returnToParam.value,
    member_id: props.preselectedMemberId ?? '',
    loan_type_id: '',
    principal: '',
    purpose: '',
    attachments: [] as File[],
});

const { isDirty, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, markClean, showErrorShake } = useFormUx(form);

const attachmentInputRef = ref<HTMLInputElement | null>(null);
const previewUrls = new Map<File, string>();
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

const formatMemberName = (member: CooperativeMemberOption) => `${member.first_name} ${member.last_name}`;

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

const memberDialogMembers = computed<MemberDialogOption[]>(() => availableMembers.value.map((member) => ({
    id: member.id,
    name: formatMemberName(member),
    coop_id: member.coop_id,
    role_names: [],
    member_code: null,
    gender: null,
    date_joined: null,
    status: 'Active',
    first_name: member.first_name,
    last_name: member.last_name,
})));

const memberModalOpen = ref(false);
const memberModalCooperativeId = computed(() => (isFromCoopContext.value ? props.preselectedCoopId ?? null : form.coop_id || null));
const canOpenMemberModal = computed(() => isFromCoopContext.value || Boolean(form.coop_id));
const selectedMember = computed(() => availableMembers.value.find((member) => String(member.id) === String(form.member_id)) || null);
const selectedMemberName = computed(() => selectedMember.value ? formatMemberName(selectedMember.value) : 'Selected member');
const selectedCooperativeName = computed(() => selectedCooperative.value?.name || props.preselectedCoop?.name || 'Selected cooperative');

const selectedCooperativeLoanTypes = computed(() => {
    if (isFromCoopContext.value) {
        return props.preselectedCoop?.loanTypes || [];
    }

    const coop = selectedCooperative.value as any;
    return coop?.loan_types || [];
});

const selectedCooperativeClassification = computed(() => {
    if (isFromCoopContext.value) {
        return props.preselectedCoop?.classification || null;
    }

    return selectedCooperative.value?.classification || null;
});

const filteredLoanTypes = computed(() => {
    const sourceLoanTypes = props.showCooperativePicker ? selectedCooperativeLoanTypes.value : props.loanTypes;

    if (props.showCooperativePicker && !selectedCooperative.value) {
        return [];
    }

    const cooperativeClassification = props.showCooperativePicker
        ? selectedCooperativeClassification.value
        : selectedMember.value?.cooperative?.classification || null;

    return (sourceLoanTypes as LoanTypeOption[]).filter((loanType) => {
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

    const exists = (loanTypes as LoanTypeOption[]).some((loanType) => String(loanType.id) === String(form.loan_type_id));
    if (!exists) {
        form.loan_type_id = '';
    }
});

const handleAttachmentsChange = (event: Event) => {
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

const backHref = computed(() => {
    if (isFromCoopContext.value && props.preselectedCoopId) {
        return `/cooperatives/${coopContextId.value}?tab=finance&subtab=loans`;
    }

    return returnToParam.value || '/finance/loans';
});

const goBack = async () => {
    if (isDirty.value) {
        const result = await Swal.fire({
            title: 'Discard this loan application?',
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

const openMemberModal = () => {
    memberModalOpen.value = true;
};

const selectMember = (member: MemberDialogOption) => {
    form.member_id = member.id;
    form.loan_type_id = '';
    form.clearErrors('member_id', 'loan_type_id');
    memberModalOpen.value = false;
};

const submit = () => {
    if (props.showCooperativePicker && !form.coop_id) {
        form.setError('coop_id', 'Please select a cooperative first.');
        triggerErrorShake();
        scrollToFirstError();
        return;
    }

    if (!form.member_id) {
        form.setError('member_id', 'Please select a member.');
        triggerErrorShake();
        scrollToFirstError();
        return;
    }

    if (!form.loan_type_id) {
        form.setError('loan_type_id', 'Please select a loan type.');
        triggerErrorShake();
        scrollToFirstError();
        return;
    }

    const postUrl = isFromCoopContext.value && props.preselectedCoopId
        ? `/cooperatives/${props.preselectedCoopId}/finance/loans`
        : '/finance/loans';

    form.transform((data) => ({
        ...data,
        return_to: backHref.value,
    })).post(postUrl, {
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
    <Head title="Finance - Create Loan" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isFromCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex flex-col gap-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <div v-if="isFromCoopContext" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                            <span>/</span>
                            <a :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ selectedCooperativeName }}</a>
                            <span>/</span>
                            <span class="text-foreground">Create Loan</span>
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-semibold tracking-tight">Create Loan Application</h1>
                            <p class="max-w-2xl text-sm text-muted-foreground">
                                Capture the member, loan type, and requested amount in one place.
                            </p>
                        </div>
                        <div v-if="isFromCoopContext" class="flex flex-wrap items-center gap-2">
                            <Badge variant="secondary" class="gap-1 rounded-full px-3 py-1 text-xs font-medium">
                                <ShieldCheck class="h-3.5 w-3.5" />
                                Cooperative-scoped create
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
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Users class="h-5 w-5" />
                            Loan Details
                        </CardTitle>
                        <CardDescription>Choose the member and loan type before setting the requested amount.</CardDescription>
                    </CardHeader>
                    <CardContent class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <Label>Cooperative</Label>
                            <template v-if="isFromCoopContext">
                                <div class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-foreground">
                                    {{ selectedCooperativeName }}
                                </div>
                            </template>
                            <template v-else-if="props.showCooperativePicker">
                                <Select v-model="form.coop_id">
                                    <SelectTrigger :class="inputErrorClass('coop_id')">
                                        <SelectValue placeholder="Select cooperative" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="cooperative in cooperatives" :key="cooperative.id" :value="String(cooperative.id)">
                                            {{ cooperative.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="text-xs text-muted-foreground">Select a cooperative first so the member and loan type options stay aligned.</p>
                                <p v-if="form.errors.coop_id" class="text-xs text-destructive">{{ form.errors.coop_id }}</p>
                            </template>
                            <template v-else>
                                <div class="rounded-lg border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
                                    This loan will use the current finance context.
                                </div>
                            </template>
                        </div>

                        <div class="space-y-2">
                            <Label>Member <span class="text-muted-foreground">*</span></Label>
                            <div v-if="selectedMember" class="rounded-xl border border-border bg-background p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ selectedMemberName }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ selectedMember.cooperative?.classification ? `${selectedMember.cooperative.classification} cooperative` : 'Selected member' }}</p>
                                    </div>
                                    <Button type="button" variant="outline" size="sm" :disabled="!canOpenMemberModal" @click="openMemberModal">
                                        Change
                                    </Button>
                                </div>
                            </div>
                            <Button v-else type="button" variant="outline" class="w-full justify-start gap-2" :disabled="!canOpenMemberModal" @click="openMemberModal">
                                <Users class="h-4 w-4" />
                                Select member
                            </Button>
                            <p class="text-xs text-muted-foreground">Search and choose the member who will receive the loan.</p>
                            <p v-if="form.errors.member_id" class="text-xs text-destructive">{{ form.errors.member_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label>Loan Type <span class="text-muted-foreground">*</span></Label>
                            <Select v-model="form.loan_type_id" :disabled="props.showCooperativePicker && !selectedCooperative && !isFromCoopContext">
                                <SelectTrigger :class="inputErrorClass('loan_type_id')">
                                    <SelectValue placeholder="Select loan type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="loanType in filteredLoanTypes" :key="loanType.id" :value="String(loanType.id)">
                                        {{ loanType.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p class="text-xs text-muted-foreground">Loan types are filtered by cooperative and classification tier.</p>
                            <p v-if="form.errors.loan_type_id" class="text-xs text-destructive">{{ form.errors.loan_type_id }}</p>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <Label for="principal">Loan Amount <span class="text-muted-foreground">*</span></Label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground">₱</span>
                                <Input
                                    id="principal"
                                    v-model="form.principal"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    class="pl-8"
                                    :class="inputErrorClass('principal')"
                                    @input="clearError('principal')"
                                />
                            </div>
                            <p class="text-xs text-muted-foreground">Enter the principal amount requested by the member.</p>
                            <p v-if="form.errors.principal" class="text-xs text-destructive">{{ form.errors.principal }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-xl">Application Notes</CardTitle>
                        <CardDescription>Add a short purpose statement and attach supporting documents if needed.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="space-y-2">
                            <Label for="purpose">Purpose</Label>
                            <Textarea
                                id="purpose"
                                v-model="form.purpose"
                                rows="4"
                                placeholder="Briefly describe why this loan is being requested"
                                :class="inputErrorClass('purpose')"
                                @input="clearError('purpose')"
                            />
                            <p class="text-xs text-muted-foreground">Optional, but useful for the approval and audit trail.</p>
                            <p v-if="form.errors.purpose" class="text-xs text-destructive">{{ form.errors.purpose }}</p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <Label>Attachments</Label>
                                <span class="text-xs text-muted-foreground">Maximum file size: 5MB per file</span>
                            </div>

                            <input ref="attachmentInputRef" type="file" multiple class="hidden" @change="handleAttachmentsChange" />

                            <div class="rounded-2xl border border-dashed border-border bg-muted/20 p-4">
                                <div v-if="attachmentItems.length === 0" class="rounded-xl border border-border bg-background px-4 py-8 text-center text-sm text-muted-foreground">
                                    No attachments selected yet.
                                </div>

                                <div v-else class="space-y-3">
                                    <div v-for="item in attachmentItems" :key="`${item.file.name}-${item.file.size}-${item.index}`" class="flex flex-col gap-3 rounded-xl border border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between">
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
                                            <Button type="button" variant="outline" size="sm" class="gap-2" @click="openAttachmentPreview(item.file)">
                                                <Eye class="h-3.5 w-3.5" />
                                                Preview
                                            </Button>
                                            <Button type="button" variant="destructive" size="sm" class="gap-2" @click="removeAttachment(item.index)">
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <Button type="button" class="gap-2 bg-foreground text-background hover:bg-foreground/90" @click="triggerAttachmentPicker">
                                        <Paperclip class="h-4 w-4" />
                                        Add File
                                    </Button>
                                    <p class="text-xs text-muted-foreground">You can upload multiple supporting files.</p>
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
                            Review the summary above before submitting the loan request.
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <Button type="button" variant="outline" class="gap-2" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />
                                Cancel
                            </Button>
                            <Button type="submit" class="gap-2" :disabled="form.processing">
                                <Plus class="h-4 w-4" />
                                {{ form.processing ? 'Submitting...' : 'Submit Loan Application' }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <MemberSelectDialog
                v-model:open="memberModalOpen"
                :members="memberDialogMembers"
                :cooperative-id="memberModalCooperativeId"
                :selected-member-id="form.member_id"
                :cooperative-name="selectedCooperativeName"
                title="Select Member"
                description="Search and choose a member for this loan application."
                :loading="false"
                @select="selectMember"
            />

            <div v-if="showErrorShake" class="sr-only">Validation error</div>
        </div>
    </FinanceShellLayout>
</template>
