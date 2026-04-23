<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { GraduationCap, Save, X, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';

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

interface Props {
    cooperatives: Cooperative[];
    members: Member[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateTraining = computed(() => permissions.value.includes('create training-&-capacity'));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    coop_ids: props.cooperatives[0]?.id ? [props.cooperatives[0].id.toString()] : ([] as string[]),
    member_ids: [] as number[],
    title: '',
    date_conducted: '',
    facilitator: '',
    skills_targeted: '',
    venue: '',
    target_group: 'All Members',
    no_of_participants: '',
    follow_up_needed: false,
    follow_up_date: '',
    follow_up_remarks: '',
    status: 'Planned',
});

const targetGroups = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];
const statusOptions = ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'];
const isCooperativeDialogOpen = ref(false);
const selectedCoopIds = ref<string[]>(form.coop_ids);

const selectedCooperatives = computed(() => {
    const selectedSet = new Set(selectedCoopIds.value);
    return props.cooperatives.filter((coop) => selectedSet.has(String(coop.id)));
});

const selectedCooperativeSummary = computed(() => {
    const count = selectedCoopIds.value.length;

    if (count === 0) return 'Choose Cooperative(s)';
    if (count === 1) return selectedCooperatives.value[0]?.name || '1 cooperative selected';

    return `${count} cooperatives selected`;
});

const syncSelectedCooperatives = (ids: string[]) => {
    selectedCoopIds.value = [...new Set(ids)];
    form.coop_ids = [...selectedCoopIds.value];
    form.coop_id = selectedCoopIds.value[0] || '';
    form.clearErrors('coop_id', 'coop_ids');
};

const memberSearch = ref('');

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

    return availableMembers.value.filter((member) =>
        member.name.toLowerCase().includes(searchTerm)
    );
});

const selectedMembers = computed(() => {
    return props.members.filter((member) => form.member_ids.includes(member.id));
});

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

watch(selectedCoopIds, (newCoopIds) => {
    const coopSet = new Set(newCoopIds.map((id) => Number(id)));
    form.member_ids = form.member_ids.filter((memberId) => {
        const member = props.members.find((item) => item.id === memberId);
        return member ? coopSet.has(member.coop_id) : false;
    });
});

const openCooperativePicker = () => {
    if (!props.cooperatives.length) return;
    isCooperativeDialogOpen.value = true;
};

const submit = () => {
    if (!canCreateTraining.value) return;

    if (!selectedCoopIds.value.length) {
        form.setError('coop_ids', 'Please select at least one cooperative.');
        return;
    }

    form.transform((data) => ({
        ...data,
        coop_id: selectedCoopIds.value[0] || '',
        coop_ids: [...selectedCoopIds.value],
    })).post('/trainings', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/trainings');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Training</h1>
                <p class="text-sm text-muted-foreground">Record a training or capacity building session.</p>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <GraduationCap class="h-5 w-5" />
                            Training Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_picker">Cooperatives</Label>
                                <Button
                                    id="coop_picker"
                                    type="button"
                                    variant="outline"
                                    class="h-10 w-full items-center justify-between"
                                    :class="{ 'border-red-500 focus-visible:ring-red-500': form.errors.coop_ids || form.errors.coop_id }"
                                    @click="openCooperativePicker"
                                >
                                    <span class="truncate text-left">{{ selectedCooperativeSummary }}</span>
                                    <span class="ml-2 text-xs text-muted-foreground">
                                        {{ selectedCoopIds.length ? `${selectedCoopIds.length} selected` : 'Select' }}
                                    </span>
                                </Button>
                                <div v-if="selectedCooperatives.length" class="mt-2 flex flex-wrap gap-1.5">
                                    <Badge
                                        v-for="coop in selectedCooperatives.slice(0, 4)"
                                        :key="coop.id"
                                        variant="secondary"
                                        class="max-w-full truncate"
                                    >
                                        {{ coop.name }}
                                    </Badge>
                                    <Badge v-if="selectedCooperatives.length > 4" variant="outline">
                                        +{{ selectedCooperatives.length - 4 }} more
                                    </Badge>
                                </div>
                                <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_id }}
                                </p>
                                <p v-if="form.errors.coop_ids" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_ids }}
                                </p>
                            </div>

                            <div>
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" placeholder="Training title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_conducted">Date Conducted</Label>
                                <Input id="date_conducted" v-model="form.date_conducted" type="date" />
                                <p v-if="form.errors.date_conducted" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_conducted }}
                                </p>
                            </div>

                            <div>
                                <Label for="facilitator">Facilitator</Label>
                                <Input id="facilitator" v-model="form.facilitator" placeholder="Provider or facilitator" />
                                <p v-if="form.errors.facilitator" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.facilitator }}
                                </p>
                            </div>

                            <div>
                                <Label for="venue">Venue</Label>
                                <Input id="venue" v-model="form.venue" placeholder="Training venue" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.venue }}
                                </p>
                            </div>

                            <div>
                                <Label for="target_group">Target Group</Label>
                                <Select v-model="form.target_group">
                                    <SelectTrigger id="target_group" :class="{ 'border-red-500': form.errors.target_group }">
                                        <SelectValue placeholder="Select target group" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in targetGroups" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.target_group" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.target_group }}
                                </p>
                            </div>

                            <div>
                                <Label for="no_of_participants">No. of Participants</Label>
                                <Input id="no_of_participants" v-model="form.no_of_participants" type="number" min="0" />
                                <p v-if="form.errors.no_of_participants" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.no_of_participants }}
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
                                <Label for="skills_targeted">Skills Targeted</Label>
                                <Textarea id="skills_targeted" v-model="form.skills_targeted" placeholder="Skills targeted" />
                                <p v-if="form.errors.skills_targeted" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.skills_targeted }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="follow_up_needed" class="flex items-center gap-2 text-sm text-foreground/80">
                                    <Checkbox id="follow_up_needed" v-model:checked="form.follow_up_needed" />
                                    <span>Follow-up needed</span>
                                </Label>
                                <p v-if="form.errors.follow_up_needed" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_needed }}
                                </p>
                            </div>

                            <div>
                                <Label for="follow_up_date">Follow-up Date</Label>
                                <Input id="follow_up_date" v-model="form.follow_up_date" type="date" />
                                <p v-if="form.errors.follow_up_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_date }}
                                </p>
                            </div>

                            <div>
                                <Label for="follow_up_remarks">Follow-up Remarks</Label>
                                <Input id="follow_up_remarks" v-model="form.follow_up_remarks" placeholder="Follow-up remarks" />
                                <p v-if="form.errors.follow_up_remarks" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_remarks }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-border pt-6">
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <GraduationCap class="h-5 w-5" />
                            Participants
                        </h2>

                        <div class="mb-4 grid gap-4 md:grid-cols-[minmax(280px,1fr)_240px]">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-foreground/80">Search members</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="memberSearch"
                                        placeholder="Search by member name"
                                        class="pl-9"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <p class="text-sm font-medium text-foreground/80">Selected cooperatives</p>
                                <p class="text-sm text-muted-foreground">{{ selectedCooperativeSummary }}</p>
                                <p v-if="form.errors.member_ids" class="text-sm text-red-500">
                                    {{ form.errors.member_ids }}
                                </p>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-border bg-muted p-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-border text-left text-sm">
                                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-muted-foreground">
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
                                            <td :colspan="5" class="px-3 py-4 text-center text-sm text-muted-foreground">
                                                No members found for the selected cooperative(s).
                                            </td>
                                        </tr>
                                        <tr v-for="member in filteredMembers" :key="member.id">
                                            <td class="px-3 py-3">
                                                <Checkbox
                                                    :model-value="isMemberSelected(member.id)"
                                                    @update:model-value="() => toggleMemberSelection(member.id)"
                                                    :aria-label="`Select ${member.name}`"
                                                />
                                            </td>
                                            <td class="px-3 py-3 font-medium text-foreground">{{ member.name }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.coop_name }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.role }}</td>
                                            <td class="px-3 py-3 text-sm text-muted-foreground">{{ member.status }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 rounded-md border border-border bg-card p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <p class="text-sm font-semibold text-foreground">Selected Participants</p>
                                    <p class="text-sm text-muted-foreground">{{ selectedMembers.length }} selected</p>
                                </div>
                                <div v-if="selectedMembers.length === 0" class="text-sm text-muted-foreground">
                                    No participants selected yet.
                                </div>
                                <div v-else class="grid gap-2">
                                    <div
                                        v-for="member in selectedMembers"
                                        :key="member.id"
                                        class="flex items-center justify-between rounded-md border border-border px-3 py-2 bg-white"
                                    >
                                        <div>
                                            <p class="font-medium text-foreground">{{ member.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.coop_name }} · {{ member.role }}</p>
                                        </div>
                                        <Button size="sm" variant="ghost" class="h-8 w-8 p-0" @click="removeSelectedMember(member.id)">
                                            <X class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:justify-end">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateTraining" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Training
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <CooperativeMultiSelectDialog
            :open="isCooperativeDialogOpen"
            :cooperatives="cooperatives"
            :selected-ids="selectedCoopIds"
            title="Choose Cooperatives"
            description="Search and filter cooperatives, then choose one or more entries for this training record."
            confirm-label="Confirm"
            cancel-label="Cancel"
            @update:open="(value) => isCooperativeDialogOpen = value"
            @confirm="syncSelectedCooperatives"
        />
    </AppLayout>
</template>
