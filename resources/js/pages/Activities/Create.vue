<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, Plus, Save, Trash2, X } from 'lucide-vue-next';
import { computed } from 'vue';
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

interface OfficerOption {
    id: number;
    name: string | null;
    coop_id: number;
}

interface Props {
    cooperatives: Cooperative[];
    officers: OfficerOption[];
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
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateActivity = computed(() => permissions.value.includes('create activities-&-projects'));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    title: '',
    description: '',
    category: 'Project',
    date_started: '',
    date_ended: '',
    status: 'Planned',
    responsible_officer_id: 'none',
    funding_source: '',
    budget: '',
    actual_expense: '',
    target_member_beneficiaries: '',
    target_community_beneficiaries: '',
    actual_member_beneficiaries: '',
    actual_community_beneficiaries: '',
    implementing_partner: '',
    outcomes: '',
    remarks: '',
    funding_sources: [] as FundingSourceFormRow[],
});

const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const statusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];
const funderTypeOptions = ['Government', 'NGO', 'Private', 'Coop Fund', 'Donor'];
const fundingStatusOptions = ['Released', 'Pending', 'Partially Released'];

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
    });
};

const removeFundingSource = (index: number) => {
    form.funding_sources.splice(index, 1);
};

const submit = () => {
    if (!canCreateActivity.value) return;
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
        })),
    })).post('/activities', {
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
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Activity</h1>
                <p class="text-sm text-muted-foreground">Record a cooperative activity or project.</p>
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
                                    <table class="w-full min-w-[920px] text-sm">
                                        <thead class="bg-muted/50 text-left">
                                            <tr>
                                                <th class="px-3 py-2 font-medium">Funding Source Name</th>
                                                <th class="px-3 py-2 font-medium">Type</th>
                                                <th class="px-3 py-2 font-medium">Amount Allocated</th>
                                                <th class="px-3 py-2 font-medium">Amount Released</th>
                                                <th class="px-3 py-2 font-medium">Status</th>
                                                <th class="px-3 py-2 font-medium">Notes</th>
                                                <th class="px-3 py-2 font-medium">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="form.funding_sources.length === 0">
                                                <td colspan="7" class="px-3 py-4 text-center text-muted-foreground">
                                                    No funding sources yet.
                                                </td>
                                            </tr>
                                            <tr v-for="(source, index) in form.funding_sources" :key="index" class="border-t border-border">
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

                            <div>
                                <Label for="budget">Budget</Label>
                                <Input id="budget" v-model="form.budget" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.budget" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.budget }}
                                </p>
                            </div>

                            <div>
                                <Label for="actual_expense">Actual Expense</Label>
                                <Input id="actual_expense" v-model="form.actual_expense" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.actual_expense" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.actual_expense }}
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
                        <Button v-if="canCreateActivity" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Activity
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
