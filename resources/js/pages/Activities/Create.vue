<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, Save, X } from 'lucide-vue-next';
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

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

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
});

const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];
const statusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];

const filteredOfficers = computed(() => {
    if (!form.coop_id) return props.officers;
    return props.officers.filter(officer => officer.coop_id.toString() === form.coop_id);
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        responsible_officer_id: data.responsible_officer_id === 'none' ? '' : data.responsible_officer_id,
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
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add Activity</h1>
                <p class="mt-1 text-sm text-gray-500">Record a cooperative activity or project.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
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

                            <div>
                                <Label for="funding_source">Funding Source</Label>
                                <Input id="funding_source" v-model="form.funding_source" placeholder="LGU, DA, Coop Fund" />
                                <p v-if="form.errors.funding_source" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.funding_source }}
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

                    <div class="flex justify-end gap-3 border-t pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Activity
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
