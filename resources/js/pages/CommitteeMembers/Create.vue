<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { ArrowLeft, Building2, Save, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useCreateBack } from '@/composables/useCreateBack';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFormUx } from '@/composables/useFormUx';
import MemberSelectDialog from '@/components/Officers/MemberSelectDialog.vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
    status?: string | null;
    first_name?: string | null;
    last_name?: string | null;
}

interface Props {
    cooperative?: Cooperative | null;
    members: MemberOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateMember = computed(() => permissions.value.includes('create officers-&-committees'));

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const validatedReturnTo = computed(() => {
    const returnTo = queryParams.value.get('return_to') || '';

    try {
        const url = new URL(returnTo, window.location.origin);
        if (url.origin === window.location.origin && url.pathname.startsWith('/')) {
            return `${url.pathname}${url.search}${url.hash}`;
        }
    } catch {
        // ignore invalid return_to values
    }

    return '';
});

const cooperative = computed(() => props.cooperative ?? null);

const form = useForm({
    coop_id: cooperative.value?.id?.toString() || '',
    member_id: '',
    committee_name: '',
    role: '',
    date_assigned: '',
    date_removed: '',
    status: 'Active',
});

const memberModalOpen = ref(false);
const selectedMembers = ref<any[]>([]);
const selectedMember = computed(() => selectedMembers.value[0] || null);
const useMultiSelect = ref(false);

const {
    isDirty,
    markClean,
    inputErrorClass,
    clearError,
    triggerErrorShake,
    showErrorShake,
} = useFormUx(form);

const { goBack } = useCreateBack({
    fallbackHref: '/committee-members',
    cooperativeId: computed(() => cooperative.value?.id || null),
    cooperativeTab: 'committees',
});

const getInitials = (name?: string | null) => {
    if (!name) return '--';

    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '--';
    if (parts.length === 1) return (parts[0][0] || '-').toUpperCase();
    return ((parts[0][0] || '-') + (parts[1][0] || '-')).toUpperCase();
};

const selectMember = (member: MemberOption) => {
    // single select
    selectedMembers.value = [member];
    form.member_id = String(member.id);
    useMultiSelect.value = false;
    clearError('member_id');
    memberModalOpen.value = false;
};

const confirmMultipleMembers = (members: MemberOption[]) => {
    selectedMembers.value = members;
    form.member_id = members[0] ? String(members[0].id) : '';
    useMultiSelect.value = true;
    clearError('member_id');
    memberModalOpen.value = false;
};

const clearMember = () => {
    selectedMembers.value = [];
    form.member_id = '';
    useMultiSelect.value = false;
    clearError('member_id');
};

const clearSelection = (index: number) => {
    selectedMembers.value = selectedMembers.value.filter((_, i) => i !== index);
    if (selectedMembers.value.length === 0) {
        form.member_id = '';
        useMultiSelect.value = false;
    } else if (selectedMembers.value.length === 1) {
        form.member_id = String(selectedMembers.value[0].id);
        useMultiSelect.value = false;
    }
};

const submit = () => {
    if (!canCreateMember.value) return;

    if (selectedMembers.value.length === 0) {
        form.setError('member_id', 'Please select at least one member.');
        triggerErrorShake();
        return;
    }

    // For multi-select: create multiple committee members in bulk
    if (useMultiSelect.value && selectedMembers.value.length > 1) {
        const items = selectedMembers.value.map((member) => ({
            coop_id: form.coop_id,
            member_id: String(member.id),
            committee_name: form.committee_name,
            role: form.role,
            date_assigned: form.date_assigned,
            date_removed: form.date_removed,
            status: form.status,
            return_to: validatedReturnTo.value,
        }));

        router.post('/committee-members-bulk', { committee_members: items, return_to: validatedReturnTo.value }, {
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

    // Single select
    form.transform((data) => ({
        ...data,
        return_to: validatedReturnTo.value,
    })).post('/committee-members', {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
        },
        onError: () => {
            triggerErrorShake();
        },
    });
};

const confirmDiscard = async () => {
    if (!isDirty.value) {
        return true;
    }

    const result = await Swal.fire({
        title: 'Discard committee member?',
        text: 'Discard new committee member entry?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Discard',
        cancelButtonText: 'Keep editing',
    });

    return result.isConfirmed;
};

const cancel = async () => {
    if (await confirmDiscard()) {
        goBack();
    }
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Add Committee Member</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Assign a member to a committee in this cooperative.</p>
                    </div>
                    <Button variant="outline" @click="cancel">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Users class="h-5 w-5" />
                            Committee Information
                        </CardTitle>
                        <CardDescription>Choose a member and set the committee assignment details.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="space-y-4">
                            <div v-if="cooperative" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Adding committee member under</p>
                                    <p class="truncate text-sm font-semibold">{{ cooperative.name }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ cooperative.region }}{{ cooperative.classification ? ' · ' + cooperative.classification : '' }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    {{ cooperative.status ?? 'Active' }}
                                </span>
                            </div>

                            <div>
                                <label class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Selected Member</label>
                                <div v-if="selectedMembers.length > 0" class="space-y-2 mt-2">
                                    <div v-for="(member, idx) in selectedMembers" :key="member.id" class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30 hover:bg-muted/50 transition-colors">
                                        <div class="h-9 w-9 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-semibold shrink-0">{{ getInitials(member.name) }}</div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium">{{ member.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ member.member_code || (member.role_names ? member.role_names.join(', ') : '') }}</p>
                                        </div>
                                        <Button variant="ghost" size="sm" @click="clearSelection(idx)" type="button" class="text-muted-foreground hover:text-destructive"><X class="h-4 w-4" /></Button>
                                    </div>
                                </div>
                                <Button v-else type="button" variant="outline" class="mt-2 w-full justify-start text-muted-foreground" @click="memberModalOpen = true">
                                    <Users class="mr-2 h-4 w-4" />
                                    Select Member(s)...
                                </Button>
                                <p class="mt-1 text-xs text-muted-foreground">Search and choose a member using the shared officer member modal.</p>
                                <p v-if="form.errors.member_id" class="mt-1 text-sm text-red-500">{{ form.errors.member_id }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <Label for="committee_name">Committee Name <span class="ml-0.5 text-red-500">*</span></Label>
                                    <Input id="committee_name" v-model="form.committee_name" placeholder="Audit, Education, Credit" :class="inputErrorClass('committee_name')" @input="clearError('committee_name')" />
                                    <p v-if="form.errors.committee_name" class="mt-1 text-sm text-red-500">{{ form.errors.committee_name }}</p>
                                </div>

                                <div>
                                    <Label for="role">Role <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input id="role" v-model="form.role" placeholder="Chairperson, Member" :class="inputErrorClass('role')" @input="clearError('role')" />
                                    <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">{{ form.errors.role }}</p>
                                </div>

                                <div>
                                    <Label for="date_assigned">Date Assigned <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input id="date_assigned" v-model="form.date_assigned" type="date" :class="inputErrorClass('date_assigned')" @input="clearError('date_assigned')" />
                                    <p v-if="form.errors.date_assigned" class="mt-1 text-sm text-red-500">{{ form.errors.date_assigned }}</p>
                                </div>

                                <div>
                                    <Label for="date_removed">Date Removed <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input id="date_removed" v-model="form.date_removed" type="date" :class="inputErrorClass('date_removed')" @input="clearError('date_removed')" />
                                    <p v-if="form.errors.date_removed" class="mt-1 text-sm text-red-500">{{ form.errors.date_removed }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Status Information</CardTitle>
                        <CardDescription>Set the assignment status for this committee member.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="status">Status <span class="ml-0.5 text-red-500">*</span></Label>
                                <Select v-model="form.status" @update:modelValue="() => clearError('status')">
                                    <SelectTrigger :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3 mt-6">
                    <Button type="button" variant="outline" @click="cancel">Cancel</Button>
                    <Button type="submit" :disabled="form.processing || !canCreateMember" class="gap-2">
                        <Save class="h-4 w-4" />
                        Save Committee Member
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