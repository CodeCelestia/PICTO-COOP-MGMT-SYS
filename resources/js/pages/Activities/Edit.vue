<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, Plus, Save, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
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
    implementing_partner: string | null;
    outcomes: string | null;
    outcomes_attachment_path?: string | null;
    remarks: string | null;
    funding_sources?: FundingSourceRecord[];
}

interface FundingSourceRecord {
    id: number;
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
    funder_name: string;
    funder_type: string;
    amount_allocated: string;
    amount_released: string;
    date_released: string;
    status: string;
    remarks: string;
    attachments: Array<File | null>;
    attachment_paths?: string[] | null;
    attachment_names?: string[] | null;
    attachments_removed?: string[];
}

interface Props {
    activity: Activity;
    cooperatives: Cooperative[];
    officers: OfficerOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateActivity = computed(() => permissions.value.includes('update activities-&-projects'));

const form = useForm({
    coop_id: props.activity.coop_id.toString(),
    title: props.activity.title,
    description: props.activity.description || '',
    category: props.activity.category,
    date_started: props.activity.date_started || '',
    date_ended: props.activity.date_ended || '',
    status: props.activity.status || 'Planned',
    responsible_officer_id: props.activity.responsible_officer_id?.toString() || 'none',
    funding_source: props.activity.funding_source || '',
    budget: props.activity.budget || '',
    actual_expense: props.activity.actual_expense || '',
    target_member_beneficiaries: props.activity.target_member_beneficiaries?.toString() || '',
    target_community_beneficiaries: props.activity.target_community_beneficiaries?.toString() || '',
    actual_member_beneficiaries: props.activity.actual_member_beneficiaries?.toString() || '',
    actual_community_beneficiaries: props.activity.actual_community_beneficiaries?.toString() || '',
    implementing_partner: props.activity.implementing_partner || '',
    outcomes: props.activity.outcomes || '',
    outcomes_attachment: null as File | null,
    remarks: props.activity.remarks || '',
    funding_sources: (props.activity.funding_sources || []).map((source) => ({
        id: source.id,
        funder_name: source.funder_name,
        funder_type: source.funder_type,
        amount_allocated: source.amount_allocated || '',
        amount_released: source.amount_released || '',
        date_released: source.date_released || '',
        status: source.status,
        remarks: source.remarks || '',
        attachments: [],
        attachment_paths: source.attachment_paths || [],
        attachment_names: source.attachment_names || [],
        attachments_removed: [],
    })) as FundingSourceFormRow[],
});

const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const statusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];
const funderTypeOptions = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const fundingStatusOptions = ['Released', 'Pending', 'Partially Released'];
const maxFundingSourceFiles = 3;
const filesDialogIndex = ref<number | null>(null);

const filteredOfficers = computed(() => {
    if (!form.coop_id) return props.officers;
    return props.officers.filter(officer => officer.coop_id.toString() === form.coop_id);
});

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
        attachment_paths: [],
        attachment_names: [],
        attachments_removed: [],
    });
};

const addFundingSourceAttachment = (index: number) => {
    const existingCount = form.funding_sources[index].attachment_names?.length || 0;
    if (existingCount + form.funding_sources[index].attachments.length >= maxFundingSourceFiles) return;
    form.funding_sources[index].attachments.push(null);
};

const updateFundingSourceAttachment = (event: Event, index: number, fileIndex: number) => {
    const input = event.target as HTMLInputElement | null;
    form.funding_sources[index].attachments[fileIndex] = input?.files?.[0] || null;
};

const removeFundingSourceAttachment = (index: number, fileIndex: number) => {
    form.funding_sources[index].attachments.splice(fileIndex, 1);
};

const openFilesDialog = (index: number) => {
    filesDialogIndex.value = index;
};

const closeFilesDialog = () => {
    filesDialogIndex.value = null;
};

const activeFundingSourceFiles = computed<Array<{ name: string; url?: string; pendingIndex?: number }>>(() => {
    if (filesDialogIndex.value === null) return [];
    const source = form.funding_sources[filesDialogIndex.value];
    const existing = (source.attachment_names || []).map((name, idx) => ({
        name,
        url: source.attachment_paths?.[idx] ? `/storage/${source.attachment_paths[idx]}` : undefined,
    }));
    const pending = source.attachments
        .map((file, pendingIndex) => ({ file, pendingIndex }))
        .filter((entry): entry is { file: File; pendingIndex: number } => Boolean(entry.file))
        .map(({ file, pendingIndex }) => ({ name: file.name, pendingIndex }));
    return [...existing, ...pending];
});

const removeActiveFundingSourceFile = (pendingIndex: number) => {
    if (filesDialogIndex.value === null) return;
    removeFundingSourceAttachment(filesDialogIndex.value, pendingIndex);
};

const removeExistingFundingSourceFile = (index: number, path: string) => {
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

const updateOutcomesAttachment = (event: Event) => {
    const input = event.target as HTMLInputElement | null;
    form.outcomes_attachment = input?.files?.[0] || null;
};

const removeFundingSource = (index: number) => {
    form.funding_sources.splice(index, 1);
};

const submit = () => {
    if (!canUpdateActivity.value) return;
    form.transform((data) => ({
        ...data,
        responsible_officer_id: data.responsible_officer_id === 'none' ? '' : data.responsible_officer_id,
        funding_source: data.funding_source || data.funding_sources[0]?.funder_name || '',
        funding_sources: data.funding_sources.map((source) => ({
            ...source,
            amount_allocated: source.amount_allocated || null,
            amount_released: source.amount_released || null,
            date_released: source.date_released || null,
            remarks: source.remarks || null,
            attachments: source.attachments.filter((file): file is File => Boolean(file)),
            attachments_removed: source.attachments_removed || [],
        })),
        outcomes_attachment: data.outcomes_attachment || null,
    })).put(`/activities/${props.activity.id}`, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/activities');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Edit Activity</h1>
                <p class="text-sm text-muted-foreground">Update activity or project details.</p>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <ClipboardList class="h-5 w-5" />
                            Activity Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative</Label>
                                <Select v-model="form.coop_id" :disabled="isCoopAdmin">
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
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" placeholder="Activity title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <Label for="category">Category</Label>
                                <Select v-model="form.category">
                                    <SelectTrigger id="category" :class="{ 'border-red-500': form.errors.category }">
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in categoryOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.category" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.category }}
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

                            <div>
                                <Label for="date_started">Date Started</Label>
                                <Input id="date_started" v-model="form.date_started" type="date" />
                                <p v-if="form.errors.date_started" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_started }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_ended">Date Ended</Label>
                                <Input id="date_ended" v-model="form.date_ended" type="date" />
                                <p v-if="form.errors.date_ended" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_ended }}
                                </p>
                            </div>

                            <div>
                                <Label for="responsible_officer_id">Responsible Officer</Label>
                                <Select v-model="form.responsible_officer_id">
                                    <SelectTrigger id="responsible_officer_id" :class="{ 'border-red-500': form.errors.responsible_officer_id }">
                                        <SelectValue placeholder="Select officer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No officer</SelectItem>
                                        <SelectItem
                                            v-for="officer in filteredOfficers"
                                            :key="officer.id"
                                            :value="officer.id.toString()"
                                        >
                                            {{ officer.name || 'Unknown' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.responsible_officer_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.responsible_officer_id }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <div class="mb-2 flex items-center justify-between">
                                    <Label>Funding Sources</Label>
                                    <Button type="button" variant="outline" class="gap-2" @click="addFundingSource">
                                        <Plus class="h-4 w-4" />
                                        Add Funding Source
                                    </Button>
                                </div>
                                <div class="overflow-x-auto rounded-md border border-border">
                                    <table class="w-full min-w-230 text-sm">
                                        <thead class="bg-muted/50 text-left">
                                            <tr>
                                                <th class="px-3 py-2 font-medium">Funding Source Name</th>
                                                <th class="px-3 py-2 font-medium">Type</th>
                                                <th class="px-3 py-2 font-medium">Amount Allocated</th>
                                                <th class="px-3 py-2 font-medium">Amount Released</th>
                                                <th class="px-3 py-2 font-medium">Status</th>
                                                <th class="px-3 py-2 font-medium">Notes</th>
                                                <th class="px-3 py-2 font-medium">Files</th>
                                                <th class="px-3 py-2 font-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="form.funding_sources.length === 0">
                                                <td colspan="8" class="px-3 py-4 text-center text-muted-foreground">
                                                    No funding sources yet.
                                                </td>
                                            </tr>
                                            <tr v-for="(source, index) in form.funding_sources" :key="source.id ?? index" class="border-t border-border">
                                                <td class="px-2 py-2 align-top">
                                                    <Input v-model="source.funder_name" placeholder="e.g., DA Region V" />
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Select v-model="source.funder_type">
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Type" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="option in funderTypeOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Input v-model="source.amount_allocated" type="number" min="0" step="0.01" />
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Input v-model="source.amount_released" type="number" min="0" step="0.01" />
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Select v-model="source.status">
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="Status" />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem v-for="option in fundingStatusOptions" :key="option" :value="option">
                                                                {{ option }}
                                                            </SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Input v-model="source.remarks" placeholder="Optional notes" />
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <div class="space-y-2">
                                                        <div v-for="(file, fileIndex) in source.attachments" :key="fileIndex" class="flex items-center gap-2">
                                                            <Input
                                                                type="file"
                                                                accept=".pdf,.jpg,.jpeg,.png"
                                                                @change="updateFundingSourceAttachment($event, index, fileIndex)"
                                                            />
                                                            <Button type="button" variant="outline" size="sm" @click="removeFundingSourceAttachment(index, fileIndex)">
                                                                Remove
                                                            </Button>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                class="gap-1"
                                                                :disabled="(source.attachment_names?.length || 0) + source.attachments.length >= maxFundingSourceFiles"
                                                                @click="addFundingSourceAttachment(index)"
                                                            >
                                                                <Plus class="h-3.5 w-3.5" />
                                                                Add File
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="secondary"
                                                                size="sm"
                                                                @click="openFilesDialog(index)"
                                                            >
                                                                Files
                                                            </Button>
                                                            <span class="text-xs text-muted-foreground">
                                                                {{ (source.attachment_names?.length || 0) + source.attachments.length }}/{{ maxFundingSourceFiles }} files
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-2 py-2 align-top">
                                                    <Button type="button" variant="outline" @click="removeFundingSource(index)">
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <p v-if="form.errors.funding_sources" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.funding_sources }}
                                </p>
                            </div>

                            <div class="md:col-span-2 grid gap-4 md:grid-cols-2">
                                <div class="space-y-4">
                                    <div>
                                        <Label for="budget">Budget</Label>
                                        <Input id="budget" v-model="form.budget" type="number" min="0" step="0.01" />
                                        <p v-if="form.errors.budget" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.budget }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="target_member_beneficiaries">Target Member Beneficiaries</Label>
                                        <Input id="target_member_beneficiaries" v-model="form.target_member_beneficiaries" type="number" min="0" />
                                        <p v-if="form.errors.target_member_beneficiaries" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.target_member_beneficiaries }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="target_community_beneficiaries">Target Community Beneficiaries</Label>
                                        <Input id="target_community_beneficiaries" v-model="form.target_community_beneficiaries" type="number" min="0" />
                                        <p v-if="form.errors.target_community_beneficiaries" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.target_community_beneficiaries }}
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <Label for="actual_expense">Actual Expense</Label>
                                        <Input id="actual_expense" v-model="form.actual_expense" type="number" min="0" step="0.01" />
                                        <p v-if="form.errors.actual_expense" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.actual_expense }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="actual_member_beneficiaries">Actual Member Beneficiaries</Label>
                                        <Input id="actual_member_beneficiaries" v-model="form.actual_member_beneficiaries" type="number" min="0" />
                                        <p v-if="form.errors.actual_member_beneficiaries" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.actual_member_beneficiaries }}
                                        </p>
                                    </div>

                                    <div>
                                        <Label for="actual_community_beneficiaries">Actual Community Beneficiaries</Label>
                                        <Input id="actual_community_beneficiaries" v-model="form.actual_community_beneficiaries" type="number" min="0" />
                                        <p v-if="form.errors.actual_community_beneficiaries" class="mt-1 text-sm text-red-500">
                                            {{ form.errors.actual_community_beneficiaries }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="implementing_partner">Implementing Partner</Label>
                                <Input id="implementing_partner" v-model="form.implementing_partner" placeholder="DA, DOLE, NGO" />
                                <p v-if="form.errors.implementing_partner" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.implementing_partner }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="description">Description</Label>
                                <Textarea id="description" v-model="form.description" placeholder="Brief description of the activity" />
                                <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="outcomes">Outcomes</Label>
                                <Textarea id="outcomes" v-model="form.outcomes" placeholder="Key outputs or outcomes" />
                                <p v-if="form.errors.outcomes" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.outcomes }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="outcomes_attachment">Outcomes Attachment</Label>
                                <div class="space-y-2">
                                    <Input
                                        id="outcomes_attachment"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        @change="updateOutcomesAttachment"
                                    />
                                    <a
                                        v-if="props.activity.outcomes_attachment_path"
                                        :href="`/storage/${props.activity.outcomes_attachment_path}`"
                                        class="inline-flex items-center gap-1 rounded-md border border-primary/30 bg-primary/5 px-3 py-1.5 text-sm font-medium text-primary transition hover:bg-primary/10"
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        View current file
                                    </a>
                                </div>
                                <p v-if="form.errors.outcomes_attachment" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.outcomes_attachment }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="remarks">Remarks</Label>
                                <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.remarks }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:justify-end">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canUpdateActivity" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Update Activity
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="filesDialogIndex !== null" @update:open="(value) => { if (!value) closeFilesDialog(); }">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Funding Source Files</DialogTitle>
                <DialogDescription>Existing and newly selected files for this funding source.</DialogDescription>
            </DialogHeader>
            <div class="space-y-2">
                <div v-if="activeFundingSourceFiles.length === 0" class="text-sm text-muted-foreground">
                    No files added yet.
                </div>
                <ul v-else class="space-y-2">
                    <li v-for="file in activeFundingSourceFiles" :key="`${file.name}-${file.pendingIndex ?? file.url ?? 'existing'}`" class="flex items-center justify-between gap-3 rounded-md border border-border px-3 py-2 text-sm">
                        <div class="truncate">
                            <a v-if="file.url" :href="file.url" class="text-primary underline" target="_blank" rel="noreferrer">
                                {{ file.name }}
                            </a>
                            <span v-else>{{ file.name }}</span>
                        </div>
                        <Button
                            v-if="file.pendingIndex !== undefined"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removeActiveFundingSourceFile(file.pendingIndex)"
                        >
                            Remove
                        </Button>
                        <Button
                            v-else-if="file.url"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="removeExistingFundingSourceFile(filesDialogIndex ?? 0, file.url.replace('/storage/', ''))"
                        >
                            Remove
                        </Button>
                    </li>
                </ul>
            </div>
        </DialogContent>
    </Dialog>
</template>
