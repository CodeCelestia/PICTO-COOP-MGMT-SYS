<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { HandCoins, Save, X } from 'lucide-vue-next';
import { computed, watch } from 'vue';
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

interface FundingSource {
    id: number;
    activity_id: number | null;
    category: 'activity' | 'project' | 'member_concern';
    coop_id: number;
    funder_name: string;
    funder_type: string;
    amount_allocated: string | null;
    amount_released: string | null;
    date_released: string | null;
    status: string;
    remarks: string | null;
}

interface Props {
    fundingSource: FundingSource;
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
const canUpdate = computed(() =>
    permissions.value.includes('update finance-funding-sources')
    || permissions.value.includes('update activities-&-projects')
);
const canViewAllCooperatives = computed(() => permissions.value.includes('view-all-cooperatives'));
const userCoopId = computed(() => auth.value?.user?.coop_id ? Number(auth.value.user.coop_id) : null);
const isCoopScopedUser = computed(() => Boolean(userCoopId.value && !canViewAllCooperatives.value));
const isFinanceContext = computed(() => page.url.startsWith('/finance/funding-sources'));

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
}>({
    activity_id: props.fundingSource.activity_id ? props.fundingSource.activity_id.toString() : NO_ACTIVITY_VALUE,
    category: props.fundingSource.category || 'activity',
    project_name: '',
    member_id: '',
    coop_id: props.fundingSource.coop_id.toString(),
    funder_name: props.fundingSource.funder_name,
    funder_type: props.fundingSource.funder_type,
    amount_allocated: props.fundingSource.amount_allocated || '',
    amount_released: props.fundingSource.amount_released || '',
    date_released: props.fundingSource.date_released || '',
    status: props.fundingSource.status,
    remarks: props.fundingSource.remarks || '',
});

const funderTypes = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const statusOptions = ['Released', 'Pending', 'Partially Released'];

if (isCoopScopedUser.value && userCoopId.value) {
    form.coop_id = userCoopId.value.toString();
}

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

    if (selectedActivity.value) {
        return props.cooperatives.find((coop) => coop.id === selectedActivity.value.coop_id) || null;
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

    if (isFinanceContext.value) {
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
    if (!canUpdate.value) return;
    form.transform((data) => ({
        ...data,
        activity_id: data.category === 'activity' && data.activity_id !== NO_ACTIVITY_VALUE ? data.activity_id : '',
        project_name: data.category === 'project' ? data.project_name : '',
        member_id: data.category === 'member_concern' ? data.member_id : '',
    })).put(`${fundingSourceBasePath.value}/${props.fundingSource.id}`, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get(fundingSourceBasePath.value);
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Edit Funding Source</h1>
                <p class="text-sm text-muted-foreground">Update funding source details.</p>
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
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canUpdate" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Update Funding Source
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
