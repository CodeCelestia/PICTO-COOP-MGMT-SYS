<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, GraduationCap, Lock, Save, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
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
import { useCoopLabel } from '@/composables/useCoopLabel';
import AppLayout from '@/layouts/AppLayout.vue';
import { notifyError, notifySuccess } from '@/lib/alerts';
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
    name: string;
    coop_id: number;
    coop_name: string;
    role: string;
    status: string;
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
    status: string;
}

interface Props {
    training: Training;
    cooperatives: Cooperative[];
    members: Member[];
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
    status: props.training.status,
});

const targetGroups = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];
const statusOptions = ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'];
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

// ✅ FIX: Always navigate to the trainings index, never use document.referrer
const goBack = () => {
    router.get('/trainings');
};

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

const availableMembers = computed(() => {
    if (!selectedCoopIds.value.length) {
        return props.members;
    }

    const coopSet = new Set(selectedCoopIds.value.map((id) => Number(id)));
    return props.members.filter((member) => coopSet.has(member.coop_id));
});

const filteredMembers = computed(() => {
    const searchTerm = memberSearch.value.trim().toLowerCase();

    if (!searchTerm) {
        return availableMembers.value;
    }

    return availableMembers.value.filter((member) => member.name.toLowerCase().includes(searchTerm));
});

const selectedMembers = computed(() => props.members.filter((member) => form.member_ids.includes(member.id)));

const isMemberSelected = (memberId: number) => form.member_ids.includes(memberId);

const toggleMemberSelection = (memberId: number) => {
    const index = form.member_ids.findIndex((id) => id === memberId);

    if (index === -1) {
        form.member_ids.push(memberId);
    } else {
        form.member_ids.splice(index, 1);
    }
};

const removeSelectedMember = (memberId: number) => {
    form.member_ids = form.member_ids.filter((id) => id !== memberId);
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

    const coopSet = new Set(selectedCoopIds.value.map((id) => Number(id)));

    form.member_ids = form.member_ids.filter((memberId) => {
        const member = props.members.find((item) => item.id === memberId);
        return member ? coopSet.has(member.coop_id) : false;
    });

    form.clearErrors('coop_id', 'coop_ids');
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
    })).put(`/trainings/${props.training.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Training updated successfully.');
        },
        onError: (errors) => {
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
                        <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6">
                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <GraduationCap class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>Capture the training title, type, date, and delivery details.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="title">Title <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" placeholder="Enter training title" :class="{ 'border-red-500': form.errors.title }" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <Label for="target_group">Target Group <span class="text-red-500">*</span></Label>
                                <Select v-model="form.target_group">
                                    <SelectTrigger id="target_group" :class="{ 'border-red-500': form.errors.target_group }">
                                        <SelectValue placeholder="Select target group" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in targetGroups" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.target_group" class="mt-1 text-sm text-red-500">{{ form.errors.target_group }}</p>
                            </div>
                            <div>
                                <Label for="date_conducted">Date Conducted</Label>
                                <Input id="date_conducted" v-model="form.date_conducted" type="date" :class="{ 'border-red-500': form.errors.date_conducted }" />
                                <p v-if="form.errors.date_conducted" class="mt-1 text-sm text-red-500">{{ form.errors.date_conducted }}</p>
                            </div>
                            <div>
                                <Label for="venue">Venue</Label>
                                <Input id="venue" v-model="form.venue" placeholder="Training venue" :class="{ 'border-red-500': form.errors.venue }" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">{{ form.errors.venue }}</p>
                            </div>
                            <div>
                                <Label for="facilitator">Facilitator</Label>
                                <Input id="facilitator" v-model="form.facilitator" placeholder="Provider or facilitator" :class="{ 'border-red-500': form.errors.facilitator }" />
                                <p v-if="form.errors.facilitator" class="mt-1 text-sm text-red-500">{{ form.errors.facilitator }}</p>
                            </div>
                            <div>
                                <Label for="status">Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="skills_targeted">Skills Targeted</Label>
                                <Textarea id="skills_targeted" v-model="form.skills_targeted" placeholder="Skills targeted by the training" />
                                <p v-if="form.errors.skills_targeted" class="mt-1 text-sm text-red-500">{{ form.errors.skills_targeted }}</p>
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
                            <Label for="coop_picker">{{ cooperativeLabel }}</Label>
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

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Participants</CardTitle>
                        <CardDescription>Select the members who attended the training.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div class="grid gap-4 md:grid-cols-[minmax(280px,1fr)_240px]">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Search members</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input v-model="memberSearch" placeholder="Search by member name" class="pl-9" />
                                </div>
                            </div>
                            <div class="space-y-1 rounded-xl border border-border/60 bg-muted/20 p-4">
                                <p class="text-sm font-medium text-foreground/80">Selected cooperative</p>
                                <p class="text-sm text-muted-foreground">{{ selectedCooperativeSummary }}</p>
                                <p v-if="form.errors.member_ids" class="text-sm text-red-500">{{ form.errors.member_ids }}</p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-border bg-muted/30">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border text-left text-sm">
                                    <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground">
                                        <tr>
                                            <th class="w-12 px-3 py-3"><span class="sr-only">Select</span></th>
                                            <th class="px-3 py-3">Name</th>
                                            <th class="px-3 py-3">Cooperative</th>
                                            <th class="px-3 py-3">Role</th>
                                            <th class="px-3 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border bg-card">
                                        <tr v-if="filteredMembers.length === 0">
                                            <td :colspan="5" class="px-3 py-4 text-center text-sm text-muted-foreground">No members found for the selected cooperative.</td>
                                        </tr>
                                        <tr v-for="member in filteredMembers" :key="member.id">
                                            <td class="px-3 py-3"><Checkbox :model-value="isMemberSelected(member.id)" @update:model-value="() => toggleMemberSelection(member.id)" :aria-label="`Select ${member.name}`" /></td>
                                            <td class="px-3 py-3 font-medium text-foreground">{{ member.name }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.coop_name }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.role }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.status }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="border-t border-border/60 p-4">
                                <div class="mb-3 flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-foreground">Selected Participants</p>
                                    <p class="text-sm text-muted-foreground">{{ selectedMembers.length }} selected</p>
                                </div>
                                <div v-if="selectedMembers.length === 0" class="text-sm text-muted-foreground">No participants selected yet.</div>
                                <div v-else class="grid gap-2">
                                    <div v-for="member in selectedMembers" :key="member.id" class="flex items-center justify-between rounded-lg border border-border bg-background px-3 py-2">
                                        <div>
                                            <p class="font-medium text-foreground">{{ member.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.coop_name }} · {{ member.role }}</p>
                                        </div>
                                        <Button size="sm" variant="ghost" class="h-8 w-8 p-0" @click="removeSelectedMember(member.id)"><X class="h-4 w-4" /></Button>
                                    </div>
                                </div>
                            </div>
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
                                <Label for="no_of_participants">No. of Participants</Label>
                                <Input id="no_of_participants" v-model="form.no_of_participants" type="number" min="0" placeholder="0" :class="{ 'border-red-500': form.errors.no_of_participants }" />
                                <p v-if="form.errors.no_of_participants" class="mt-1 text-sm text-red-500">{{ form.errors.no_of_participants }}</p>
                            </div>
                            <div>
                                <Label for="follow_up_date">Follow-up Date</Label>
                                <Input id="follow_up_date" v-model="form.follow_up_date" type="date" :class="{ 'border-red-500': form.errors.follow_up_date }" />
                                <p v-if="form.errors.follow_up_date" class="mt-1 text-sm text-red-500">{{ form.errors.follow_up_date }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_remarks">Follow-up Remarks</Label>
                                <Input id="follow_up_remarks" v-model="form.follow_up_remarks" placeholder="Follow-up remarks" :class="{ 'border-red-500': form.errors.follow_up_remarks }" />
                                <p v-if="form.errors.follow_up_remarks" class="mt-1 text-sm text-red-500">{{ form.errors.follow_up_remarks }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_needed" class="flex items-center gap-2 text-sm text-foreground/80">
                                    <Checkbox id="follow_up_needed" v-model:checked="form.follow_up_needed" />
                                    <span>Follow-up needed</span>
                                </Label>
                                <p v-if="form.errors.follow_up_needed" class="mt-1 text-sm text-red-500">{{ form.errors.follow_up_needed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="goBack" type="button" variant="outline" class="gap-2">
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
    </AppLayout>
</template>