<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Users, Save, X, Building2, AlertCircle } from 'lucide-vue-next';
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
import { useCreateBack } from '@/composables/useCreateBack';
import MemberSelectDialog from '@/components/Officers/MemberSelectDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useFormUx } from '@/composables/useFormUx';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
    role_names: string[];
    member_code?: string | null;
    gender?: string | null;
    date_joined?: string | null;
    first_name?: string | null;
    last_name?: string | null;
}

interface Props {
    cooperatives: Cooperative[];
    members: MemberOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateOfficer = computed(() => permissions.value.includes('create officers-&-committees'));

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const initialCoopId = computed(() => queryParams.value.get('coop_id') || props.cooperatives[0]?.id?.toString() || '');
const initialPosition = computed(() => queryParams.value.get('position') || '');

const form = useForm({
    coop_id: initialCoopId.value,
    member_id: '',
    position: initialPosition.value,
    committee: '',
    term_start: '',
    term_end: '',
    status: 'Active',
    reason_for_change: '',
    election_year: '',
});

// useFormUx for UX helpers
const { isDirty, isPreFilling, markClean, inputErrorClass, clearError, handleCancel, triggerErrorShake } = useFormUx(form);

// cooperative context + banner data
const cooperative = computed(() => {
    const id = form.coop_id?.toString() || initialCoopId.value;
    return props.cooperatives.find(c => String(c.id) === String(id)) || null;
});

// Member modal state
const memberModalOpen = ref(false);
const selectedMembers = ref<any[]>([]);
const selectedMember = computed(() => selectedMembers.value[0] || null);
const useMultiSelect = ref(false);

function openMemberModal() {
    memberModalOpen.value = true;
}

function selectMember(member: any) {
    selectedMembers.value = [member];
    form.member_id = String(member.id);
    memberModalOpen.value = false;
}

function confirmMultipleMembers(members: any[]) {
    selectedMembers.value = members;
    useMultiSelect.value = true;
    memberModalOpen.value = false;
}

function clearMember() {
    selectedMembers.value = [];
    form.member_id = '';
    useMultiSelect.value = false;
}

function clearSelection(index: number) {
    selectedMembers.value = selectedMembers.value.filter((_, i) => i !== index);
    if (selectedMembers.value.length === 0) {
        form.member_id = '';
        useMultiSelect.value = false;
    } else if (selectedMembers.value.length === 1) {
        form.member_id = String(selectedMembers.value[0].id);
    }
}

const electionYearDate = computed<string>({
    get: () => (form.election_year ? `${form.election_year}-01-01` : ''),
    set: (value) => {
        if (!value) {
            form.election_year = '';
            return;
        }

        const year = new Date(value).getFullYear();
        form.election_year = Number.isNaN(year) ? '' : String(year);
    },
});

const submit = () => {
    if (!canCreateOfficer.value) return;
    if (selectedMembers.value.length === 0) {
        form.setError('member_id', 'Please select at least one member.');
        triggerErrorShake();
        return;
    }

    // simple client-side reason_for_change enforcement
    if (form.status && form.status.toLowerCase() !== 'active' && !form.reason_for_change) {
        form.setError('reason_for_change', 'Reason for change is required when status is not Active.');
        triggerErrorShake();
        return;
    }

    // For multi-select: create multiple officers in bulk
    if (useMultiSelect.value && selectedMembers.value.length > 1) {
        const officerData = selectedMembers.value.map((member) => ({
            coop_id: form.coop_id,
            member_id: String(member.id),
            position: form.position,
            committee: form.committee,
            term_start: form.term_start,
            term_end: form.term_end,
            status: form.status,
            reason_for_change: form.reason_for_change,
            election_year: form.election_year,
            return_to: returnToHref.value,
        }));

        router.post('/officers-bulk', { officers: officerData, return_to: returnToHref.value }, {
            preserveScroll: true,
            onSuccess: () => {
                markClean();
            },
            onError: () => {
                triggerErrorShake();
            },
        });
        return;
    }

    // For single select: create single officer
    form.transform((data) => ({
        ...data,
        coop_id: form.coop_id,
        member_id: form.member_id,
        return_to: returnToHref.value,
    })).post('/officers', {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
        },
        onError: () => {
            triggerErrorShake();
        },
    });
};

const cancel = () => {
    handleCancel({ confirmTitle: 'Discard officer?', confirmText: 'Discard new officer entry?' });
};

const { goBack, returnToHref } = useCreateBack({
    fallbackHref: '/officers',
    cooperativeId: computed(() => form.coop_id),
    cooperativeTab: 'officers',
});

const getInitials = (name?: string | null) => {
    if (!name) return '--';
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '--';
    if (parts.length === 1) return (parts[0][0] || '-').toUpperCase();
    return ((parts[0][0] || '-') + (parts[1][0] || '-')).toUpperCase();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <!-- Header card -->
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Create Officer</h1>
                        <p class="text-sm text-muted-foreground mt-1">Register a new officer for this cooperative.</p>
                    </div>
                    <Button variant="outline" @click="handleCancel()">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Officer Information Card -->
                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Users class="h-5 w-5" />
                            Officer Information
                        </CardTitle>
                        <CardDescription>Assign a member and set term information.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="space-y-4">
                            <!-- Cooperative context banner -->
                            <div v-if="cooperative" class="flex items-center gap-3 p-4 rounded-lg border border-blue-200 bg-blue-50/60 dark:border-blue-800 dark:bg-blue-900/10">
                                <div class="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center shrink-0">
                                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide mb-0.5">Creating officer under</p>
                                    <p class="text-sm font-semibold truncate">{{ cooperative.name }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ cooperative.region }}{{ cooperative.classification ? ' · ' + cooperative.classification : '' }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800">
                                    {{ cooperative.status ?? 'Active' }}
                                </span>
                            </div>

                            <!-- Member selector (modal trigger) -->
                            <div>
                                <label class="text-sm font-medium">Member(s) <span class="text-red-500 ml-0.5">*</span></label>
                                <div v-if="selectedMembers.length > 0" class="space-y-2 mt-2">
                                    <div v-for="(member, idx) in selectedMembers" :key="member.id" class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30 hover:bg-muted/50 transition-colors">
                                        <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-semibold shrink-0">{{ getInitials(member.name) }}</div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium">{{ member.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.member_code ?? (member.role_names ? member.role_names.join(', ') : '') }}</p>
                                        </div>
                                        <Button variant="ghost" size="sm" @click="clearSelection(idx)" type="button" class="text-muted-foreground hover:text-destructive"><X class="h-4 w-4" /></Button>
                                    </div>
                                </div>
                                <Button v-else variant="outline" class="w-full mt-2 justify-start text-muted-foreground" @click="openMemberModal" type="button">
                                    <Users class="mr-2 h-4 w-4" />
                                    Select Member(s)...
                                </Button>
                                <p v-if="form.errors?.member_id" class="text-sm text-red-500 mt-1 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.member_id }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label for="position">Position <span class="text-red-500 ml-0.5">*</span></Label>
                                    <Input id="position" v-model="form.position" :class="inputErrorClass('position')" @input="clearError('position')" placeholder="Chairperson, Treasurer, etc." />
                                    <p v-if="form.errors.position" class="mt-1 text-sm text-red-500">{{ form.errors.position }}</p>
                                </div>

                                <div>
                                    <Label for="committee">Committee <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                    <Input id="committee" v-model="form.committee" :class="inputErrorClass('committee')" @input="clearError('committee')" placeholder="Audit, Education, Credit" />
                                    <p v-if="form.errors.committee" class="mt-1 text-sm text-red-500">{{ form.errors.committee }}</p>
                                </div>

                                <div>
                                    <Label for="term_start">Term Start <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                    <Input id="term_start" v-model="form.term_start" type="date" :class="inputErrorClass('term_start')" @input="clearError('term_start')" />
                                    <p v-if="form.errors.term_start" class="mt-1 text-sm text-red-500">{{ form.errors.term_start }}</p>
                                </div>

                                <div>
                                    <Label for="term_end">Term End <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                    <Input id="term_end" v-model="form.term_end" type="date" :class="inputErrorClass('term_end')" @input="clearError('term_end')" />
                                    <p v-if="form.errors.term_end" class="mt-1 text-sm text-red-500">{{ form.errors.term_end }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Status Card -->
                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Status Information</CardTitle>
                        <CardDescription>Set the officer status and provide reason when applicable.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="status">Status <span class="text-red-500 ml-0.5">*</span></Label>
                                <Select v-model="form.status" @update:modelValue="(v) => { if(String(v).toLowerCase() === 'active') form.reason_for_change = ''; clearError('status'); }">
                                    <SelectTrigger :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Retired">Retired</SelectItem>
                                        <SelectItem value="Removed">Removed</SelectItem>
                                        <SelectItem value="Resigned">Resigned</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                            </div>

                            <div>
                                <Label for="election_year">Election Year <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></Label>
                                <Input
                                    id="election_year"
                                    v-model="electionYearDate"
                                    type="date"
                                    :class="inputErrorClass('election_year')"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">Choose any date in the election year. Only the year will be saved.</p>
                                <p v-if="form.errors.election_year" class="mt-1 text-sm text-red-500">{{ form.errors.election_year }}</p>
                            </div>

                            <div class="md:col-span-2" v-if="form.status && form.status.toLowerCase() !== 'active'">
                                <Label for="reason_for_change">Reason for Change <span class="text-red-500 ml-0.5">*</span></Label>
                                <Textarea id="reason_for_change" v-model="form.reason_for_change" :class="inputErrorClass('reason_for_change')" @input="clearError('reason_for_change')" placeholder="Enter reason for status change..." rows="3" />
                                <p v-if="form.errors.reason_for_change" class="mt-1 text-sm text-red-500">{{ form.errors.reason_for_change }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3 mt-6">
                    <Button variant="outline" @click="cancel">Cancel</Button>
                    <Button type="submit" :disabled="form.processing || !canCreateOfficer" class="gap-2">
                        <Save class="h-4 w-4" />
                        Create Officer
                    </Button>
                </div>
            </form>

            <MemberSelectDialog
                v-model:open="memberModalOpen"
                :members="props.members"
                :cooperative-id="form.coop_id"
                :multi-select="true"
                :selected-member-ids="selectedMembers.map(m => m.id)"
                :cooperative-name="cooperative?.name ?? null"
                :loading="false"
                @select="selectMember"
                @confirm="confirmMultipleMembers"
            />
        </div>
    </AppLayout>
</template>
