<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, File, FileText, HandCoins, Image, Plus, Save, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
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

interface Cooperative {
    id: number;
    name: string;
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
    })).post(fundingSourceBasePath.value, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get(fundingSourceBasePath.value);
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Funding Source</h1>
                    <p class="text-sm text-muted-foreground">Record funding source details for a cooperative or activity.</p>
                </div>
                <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <HandCoins class="h-5 w-5" />
                            Funding Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="category">Category</Label>
                                <Select v-model="form.category">
                                    <SelectTrigger id="category" :class="{ 'border-red-500': form.errors.category }">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="activity">Activity</SelectItem>
                                        <SelectItem value="project">Project</SelectItem>
                                        <SelectItem value="member_concern">Member Concern</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.category }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Activity: tied to a specific activity record. Project: for broader cooperative projects not tied to one activity. Member Concern: support tied to an individual member need.
                                </p>
                            </div>

                            <div v-if="form.category === 'activity'">
                                <Label for="activity_id">Activity</Label>
                                <Select v-model="form.activity_id" :disabled="isCoopScopedUser && filteredActivities.length === 1">
                                    <SelectTrigger id="activity_id" :class="{ 'border-red-500': form.errors.activity_id }">
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
                                <p v-if="form.errors.activity_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.activity_id }}
                                </p>
                                <p v-else-if="filteredActivities.length === 0" class="mt-1 text-sm text-muted-foreground">
                                    No activities found for the selected cooperative.
                                </p>
                            </div>

                            <div v-if="form.category === 'project'">
                                <Label for="project_name">Project Name</Label>
                                <Input id="project_name" v-model="form.project_name" placeholder="Enter project name" />
                                <p v-if="form.errors.project_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.project_name }}
                                </p>
                            </div>

                            <div v-if="form.category === 'member_concern'">
                                <Label for="member_id">Member</Label>
                                <Select v-model="form.member_id">
                                    <SelectTrigger id="member_id" :class="{ 'border-red-500': form.errors.member_id }">
                                        <SelectValue placeholder="Select member" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="member in filteredMembers" :key="member.id" :value="member.id.toString()">
                                            {{ member.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.member_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.member_id }}
                                </p>
                                <p v-else-if="filteredMembers.length === 0" class="mt-1 text-sm text-muted-foreground">
                                    No members found for the selected cooperative.
                                </p>
                            </div>

                            <div>
                                <Label for="cooperative_name">Cooperative</Label>
                                <Input
                                    v-if="isCoopScopedUser"
                                    id="cooperative_name"
                                    :value="selectedCooperative?.name || 'No cooperative assigned'"
                                    disabled
                                />
                                <Select v-else v-model="form.coop_id">
                                    <SelectTrigger id="coop_id" :class="{ 'border-red-500': form.errors.coop_id }">
                                        <SelectValue placeholder="Select cooperative" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="funder_name">Funder Name</Label>
                                <Input id="funder_name" v-model="form.funder_name" placeholder="Funding agency or source" />
                                <p v-if="form.errors.funder_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.funder_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="funder_type">Funder Type</Label>
                                <Select v-model="form.funder_type">
                                    <SelectTrigger id="funder_type" :class="{ 'border-red-500': form.errors.funder_type }">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in funderTypes" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.funder_type" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.funder_type }}
                                </p>
                            </div>

                            <div>
                                <Label for="amount_allocated">Amount Allocated</Label>
                                <Input id="amount_allocated" v-model="form.amount_allocated" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount_allocated" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.amount_allocated }}
                                </p>
                            </div>

                            <div>
                                <Label for="amount_released">Amount Released</Label>
                                <Input id="amount_released" v-model="form.amount_released" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount_released" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.amount_released }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_released">Date Released</Label>
                                <Input id="date_released" v-model="form.date_released" type="date" />
                                <p v-if="form.errors.date_released" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_released }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="remarks">Remarks</Label>
                                <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">
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
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
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
