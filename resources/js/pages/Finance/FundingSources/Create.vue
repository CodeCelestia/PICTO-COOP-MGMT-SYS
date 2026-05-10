<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Building2, File, FileText, HandCoins, Image, Plus, Save, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction, notifySuccess } from '@/lib/alerts';
import Swal from 'sweetalert2';
import { useFormUx } from '@/composables/useFormUx';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface ActivityOption {
    id: number;
    title: string;
    coop_id: number;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface Props {
    activities: ActivityOption[];
    members: MemberOption[];
    cooperatives: Cooperative[];
    isCoopContext?: boolean;
    coopContext?: Cooperative | null;
}

const props = defineProps<Props>();
const NO_ACTIVITY_VALUE = '__none__';

const page = usePage();
const auth = computed(() => page.props.auth as {
    isCoopAdmin?: boolean;
    permissions?: string[];
    user?: { coop_id?: number | null };
} | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreate = computed(() =>
    permissions.value.includes('create finance-funding-sources')
    || permissions.value.includes('create activities-&-projects')
);
const canViewAllCooperatives = computed(() => permissions.value.includes('view-all-cooperatives'));
const userCoopId = computed(() => auth.value?.user?.coop_id ? Number(auth.value.user.coop_id) : null);
const isCoopScopedUser = computed(() => Boolean(userCoopId.value && !canViewAllCooperatives.value));
const isFinanceContext = computed(() => page.url.startsWith('/finance/funding-sources'));
const isCoopContext = computed(() => Boolean(props.isCoopContext && props.coopContext));

const urlParams = new URLSearchParams(
    typeof window !== 'undefined' ? window.location.search : '',
);
const prefilledActivityId = urlParams.get('activity_id');

const defaultCoopId = isCoopScopedUser.value
    ? (userCoopId.value?.toString() || '')
    : (props.cooperatives[0]?.id?.toString() || '');

const initialActivityId = (() => {
    if (prefilledActivityId) {
        return prefilledActivityId;
    }

    if (isFinanceContext.value) {
        return NO_ACTIVITY_VALUE;
    }

    if (!props.activities.length) {
        return NO_ACTIVITY_VALUE;
    }

    if (defaultCoopId) {
        const coopMatch = props.activities.find((activity) => activity.coop_id === Number(defaultCoopId));
        if (coopMatch) {
            return coopMatch.id.toString();
        }
    }

    return props.activities[0].id.toString();
})();

const form = useForm<{
    activity_id: string;
    category: string;
    project_name: string;
    member_id: string;
    coop_id: string;
    funder_name: string;
    funder_type: string;
    amount_allocated: string;
    amount_released: string;
    date_released: string;
    status: string;
    remarks: string;
    attachments: Array<File | null>;
}>({
    activity_id: initialActivityId,
    category: 'activity',
    project_name: '',
    member_id: '',
    coop_id: defaultCoopId,
    funder_name: '',
    funder_type: 'Government',
    amount_allocated: '',
    amount_released: '',
    date_released: '',
    status: 'Pending',
    remarks: '',
    attachments: [],
});

const { isDirty, markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, showErrorShake } = useFormUx(form);

const funderTypes = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const statusOptions = ['Released', 'Pending', 'Partially Released'];
const maxFundingSourceFiles = 3;

const addAttachmentSlot = () => {
    if (form.attachments.length >= maxFundingSourceFiles) return;
    form.attachments.push(null);
};

const updateAttachmentSlot = (event: Event, index: number) => {
    const input = event.target as HTMLInputElement | null;
    const nextFile = input?.files?.[0] || null;
    form.attachments[index] = nextFile;
    if (nextFile) notifySuccess('File added to funding source.');
};

const removeAttachmentSlot = async (index: number) => {
    const ok = await confirmAction({
        title: 'Remove file?',
        text: 'This will remove the selected file from this funding source.',
        confirmButtonText: 'Remove file',
    });
    if (!ok) return;
    form.attachments.splice(index, 1);
};

const fileKindFromName = (name: string) => {
    const extension = name.split('.').pop()?.toLowerCase() || '';
    if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension)) return 'image';
    if (extension === 'pdf') return 'pdf';
    return 'file';
};

const fundingSourceFiles = computed(() =>
    form.attachments
        .map((file, pendingIndex) => (file ? {
            name: file.name,
            kind: fileKindFromName(file.name),
            pendingIndex,
        } : null))
        .filter((entry): entry is { name: string; kind: string; pendingIndex: number } => Boolean(entry))
);

const filteredActivities = computed(() => {
    if (!form.coop_id) {
        return props.activities;
    }

    const coopId = Number(form.coop_id);
    return props.activities.filter((activity) => activity.coop_id === coopId);
});

const selectedActivity = computed(() => {
    return filteredActivities.value.find((activity) => activity.id.toString() === form.activity_id) || null;
});

const filteredMembers = computed(() => {
    if (!form.coop_id) {
        return props.members;
    }

    const coopId = Number(form.coop_id);
    return props.members.filter((member) => member.coop_id === coopId);
});

const selectedCooperative = computed(() => {
    if (isCoopContext.value && props.coopContext) {
        return props.coopContext;
    }

    if (form.coop_id) {
        return props.cooperatives.find((coop) => coop.id.toString() === form.coop_id) || null;
    }

    const selectedCoopId = selectedActivity.value?.coop_id;
    if (selectedCoopId) {
        return props.cooperatives.find((coop) => coop.id === selectedCoopId) || null;
    }

    return null;
});

const fundingSourceBasePath = computed(() =>
    page.url.startsWith('/finance/funding-sources')
        ? '/finance/funding-sources'
        : '/activity-funding-sources'
);

const navigateBack = async () => {
    if (isDirty.value) {
        const result = await Swal.fire({
            title: 'Discard changes?',
            text: 'You have unsaved changes. Are you sure you want to discard them?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing',
        });

        if (!result.isConfirmed) {
            return;
        }
    }

    if (page.url.startsWith('/cooperatives/') && fundingCoopId.value) {
        router.get(`/cooperatives/${fundingCoopId.value}?tab=finance&subtab=funding-sources`);
        return;
    }

    const globalCoopId = fundingCoopId.value
        ?? new URLSearchParams(window.location.search).get('coop_id');

    router.get(globalCoopId
        ? `${fundingSourceBasePath.value}?coop_id=${globalCoopId}`
        : fundingSourceBasePath.value);
};

watch(filteredActivities, (activities) => {
    if (form.category !== 'activity') {
        form.activity_id = NO_ACTIVITY_VALUE;
        return;
    }

    if (isFinanceContext.value && !prefilledActivityId) {
        if (
            form.activity_id
            && form.activity_id !== NO_ACTIVITY_VALUE
            && !activities.some((activity) => activity.id.toString() === form.activity_id)
        ) {
            form.activity_id = NO_ACTIVITY_VALUE;
        }
        return;
    }

    if (!activities.length) {
        form.activity_id = NO_ACTIVITY_VALUE;
        return;
    }

    const hasSelection = activities.some((activity) => activity.id.toString() === form.activity_id);
    if (!hasSelection) {
        form.activity_id = activities[0].id.toString();
    }
}, { immediate: true });

watch(() => form.category, (category) => {
    if (category !== 'activity') {
        form.activity_id = NO_ACTIVITY_VALUE;
    }

    if (category !== 'project') {
        form.project_name = '';
    }

    if (category !== 'member_concern') {
        form.member_id = '';
    }
});

watch(filteredMembers, (members) => {
    if (form.category !== 'member_concern') return;
    if (!members.length) {
        form.member_id = '';
        return;
    }
    if (!members.some((member) => member.id.toString() === form.member_id)) {
        form.member_id = members[0].id.toString();
    }
}, { immediate: true });

const submit = () => {
    if (!canCreate.value) return;
    form.transform((data) => ({
        ...data,
        activity_id: data.category === 'activity' && data.activity_id !== NO_ACTIVITY_VALUE ? data.activity_id : '',
        project_name: data.category === 'project' ? data.project_name : '',
        member_id: data.category === 'member_concern' ? data.member_id : '',
        attachments: data.attachments.filter((file): file is File => Boolean(file)),
    })).post(page.url.startsWith('/cooperatives/') && fundingCoopId.value
        ? `/cooperatives/${fundingCoopId.value}/finance/funding-sources`
        : fundingSourceBasePath.value, {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
        },
        onError: () => {
            triggerErrorShake();
            scrollToFirstError();
        },
    });
};

const fundingCoopId = computed(() =>
    selectedActivity.value?.coop_id || form.coop_id || (props.coopContext?.id ? String(props.coopContext.id) : null)
);
const handleBack = navigateBack;
const handleCancel = navigateBack;
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Add Funding Source</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Record funding source details for a cooperative or activity.</p>
                    </div>
                    <Button variant="outline" type="button" @click="handleBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <div v-if="isCoopContext && selectedCooperative" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Creating record under</p>
                    <p class="truncate text-sm font-semibold text-foreground">{{ selectedCooperative.name }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">{{ selectedCooperative.region || '' }}{{ selectedCooperative.classification ? ' · ' + selectedCooperative.classification : '' }}</p>
                </div>
                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">{{ selectedCooperative.status ?? 'Active' }}</span>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <HandCoins class="h-5 w-5" />
                            Funding Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium leading-none">Category <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.category" @update:model-value="clearError('category')">
                                    <SelectTrigger id="category" :class="inputErrorClass('category')">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="activity">Activity</SelectItem>
                                        <SelectItem value="project">Project</SelectItem>
                                        <SelectItem value="member_concern">Member Concern</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.category }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Activity: tied to a specific activity record. Project: for broader cooperative projects not tied to one activity. Member Concern: support tied to an individual member need.
                                </p>
                            </div>

                            <div v-if="form.category === 'activity'">
                                <label class="text-sm font-medium leading-none">Activity <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.activity_id" :disabled="isCoopScopedUser && filteredActivities.length === 1" @update:model-value="clearError('activity_id')">
                                    <SelectTrigger id="activity_id" :class="inputErrorClass('activity_id')">
                                        <SelectValue placeholder="Select activity (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NO_ACTIVITY_VALUE">No specific activity</SelectItem>
                                        <SelectItem v-for="activity in filteredActivities" :key="activity.id" :value="activity.id.toString()">
                                            {{ activity.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Leave empty if this funding source is not tied to a specific activity.
                                </p>
                                <p v-if="form.errors.activity_id" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.activity_id }}
                                </p>
                                <p v-else-if="filteredActivities.length === 0" class="mt-1 text-sm text-muted-foreground">
                                    No activities found for the selected cooperative.
                                </p>
                            </div>

                            <div v-if="form.category === 'project'">
                                <label class="text-sm font-medium leading-none">Project Name <span class="text-red-500 ml-0.5">*</span></label>
                                <Input id="project_name" v-model="form.project_name" :class="inputErrorClass('project_name')" @input="clearError('project_name')" placeholder="Enter project name" />
                                <p v-if="form.errors.project_name" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.project_name }}
                                </p>
                            </div>

                            <div v-if="form.category === 'member_concern'">
                                <label class="text-sm font-medium leading-none">Member <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.member_id" @update:model-value="clearError('member_id')">
                                    <SelectTrigger id="member_id" :class="inputErrorClass('member_id')">
                                        <SelectValue placeholder="Select member" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="member in filteredMembers" :key="member.id" :value="member.id.toString()">
                                            {{ member.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.member_id" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.member_id }}
                                </p>
                                <p v-else-if="filteredMembers.length === 0" class="mt-1 text-sm text-muted-foreground">
                                    No members found for the selected cooperative.
                                </p>
                            </div>

                            <div v-if="!isCoopContext">
                                <label class="text-sm font-medium leading-none">Cooperative <span class="text-red-500 ml-0.5">*</span></label>
                                <Input
                                    v-if="isCoopScopedUser"
                                    id="cooperative_name"
                                    :value="selectedCooperative?.name || 'No cooperative assigned'"
                                    disabled
                                />
                                <Select v-else v-model="form.coop_id" @update:model-value="clearError('coop_id')">
                                    <SelectTrigger id="coop_id" :class="inputErrorClass('coop_id')">
                                        <SelectValue placeholder="Select cooperative" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.coop_id" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.coop_id }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Funder Name <span class="text-red-500 ml-0.5">*</span></label>
                                <Input id="funder_name" v-model="form.funder_name" :class="inputErrorClass('funder_name')" @input="clearError('funder_name')" placeholder="Funding agency or source" />
                                <p v-if="form.errors.funder_name" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.funder_name }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Funder Type <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.funder_type" @update:model-value="clearError('funder_type')">
                                    <SelectTrigger id="funder_type" :class="inputErrorClass('funder_type')">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in funderTypes" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.funder_type" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.funder_type }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Amount Allocated <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="amount_allocated" v-model="form.amount_allocated" :class="inputErrorClass('amount_allocated')" @input="clearError('amount_allocated')" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount_allocated" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.amount_allocated }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Amount Released <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="amount_released" v-model="form.amount_released" :class="inputErrorClass('amount_released')" @input="clearError('amount_released')" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount_released" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.amount_released }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Date Released <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="date_released" v-model="form.date_released" :class="inputErrorClass('date_released')" @input="clearError('date_released')" type="date" />
                                <p v-if="form.errors.date_released" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.date_released }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Status <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.status" @update:model-value="clearError('status')">
                                    <SelectTrigger id="status" :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-sm font-medium leading-none">Remarks <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Textarea id="remarks" v-model="form.remarks" :class="inputErrorClass('remarks')" @input="clearError('remarks')" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.remarks }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label>Files</Label>
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="gap-1"
                                            :disabled="form.attachments.length >= maxFundingSourceFiles"
                                            @click="addAttachmentSlot"
                                        >
                                            <Plus class="h-3.5 w-3.5" />
                                            Add File
                                        </Button>
                                        <span class="text-xs text-muted-foreground">{{ form.attachments.length }}/{{ maxFundingSourceFiles }} files</span>
                                    </div>
                                    <div v-for="(file, index) in form.attachments" :key="index" class="flex items-center gap-2">
                                        <Input
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            @change="updateAttachmentSlot($event, index)"
                                        />
                                        <Button type="button" variant="outline" size="sm" @click="removeAttachmentSlot(index)">
                                            Remove
                                        </Button>
                                    </div>
                                    <div class="border-t border-border/60 pt-2">
                                        <div class="rounded-lg border border-border bg-muted/30 p-2">
                                            <div v-if="fundingSourceFiles.length === 0" class="text-xs text-muted-foreground">
                                                No files added yet.
                                            </div>
                                            <ul v-else class="space-y-2">
                                                <li
                                                    v-for="file in fundingSourceFiles"
                                                    :key="`${file.name}-${file.pendingIndex}`"
                                                    class="flex items-center justify-between gap-2 rounded-md bg-background px-2 py-1.5 text-xs shadow-sm"
                                                >
                                                    <div class="flex min-w-0 items-center gap-2">
                                                        <FileText v-if="file.kind === 'pdf'" class="h-4 w-4 text-rose-500" />
                                                        <Image v-else-if="file.kind === 'image'" class="h-4 w-4 text-emerald-500" />
                                                        <File v-else class="h-4 w-4 text-muted-foreground" />
                                                        <span class="truncate">{{ file.name }}</span>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        class="h-7 px-2"
                                                        @click="removeAttachmentSlot(file.pendingIndex)"
                                                    >
                                                        Remove
                                                    </Button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="form.errors.attachments" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.attachments }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="handleCancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreate" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Funding Source
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
