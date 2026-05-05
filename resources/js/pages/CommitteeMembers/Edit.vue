<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Lock, Save, Users } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
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
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface CommitteeMemberPerson {
    id: number;
    first_name?: string | null;
    last_name?: string | null;
    member_code?: string | null;
}

interface CommitteeMember {
    id: number;
    member_id: number;
    coop_id: number;
    committee_name: string;
    role: string | null;
    date_assigned: string | null;
    date_removed: string | null;
    status: string;
    member?: CommitteeMemberPerson | null;
    cooperative?: Cooperative | null;
}

interface Props {
    committeeMember: CommitteeMember;
    cooperative?: Cooperative | null;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateMember = computed(() => permissions.value.includes('update officers-&-committees'));

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

const cooperative = computed(() => props.cooperative ?? props.committeeMember.cooperative ?? null);

const form = useForm({
    committee_name: '',
    role: '',
    date_assigned: '',
    date_removed: '',
    status: 'Active',
});

const {
    isPreFilling,
    markClean,
    inputErrorClass,
    clearError,
    handleCancel,
    triggerErrorShake,
    showErrorShake,
} = useFormUx(form);

const getInitials = (name?: string | null) => {
    if (!name) return '--';

    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '--';
    if (parts.length === 1) return (parts[0][0] || '-').toUpperCase();
    return ((parts[0][0] || '-') + (parts[1][0] || '-')).toUpperCase();
};

const formatDate = (dateString?: string | null) => {
    if (!dateString) return '—';
    return String(dateString).slice(0, 10);
};

const getMemberName = () => {
    const member = props.committeeMember.member;
    if (!member) return 'Assigned member';

    const parts = [member.last_name, member.first_name].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : 'Assigned member';
};

const submit = () => {
    if (!canUpdateMember.value) return;

    form.transform((data) => ({
        ...data,
        return_to: validatedReturnTo.value,
    })).put(`/committee-members/${props.committeeMember.id}`, {
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
    handleCancel({ confirmTitle: 'Discard committee member changes?', confirmText: 'You have unsaved changes. Are you sure you want to discard them?' });
};

onMounted(async () => {
    isPreFilling.value = true;

    const committeeMember = props.committeeMember;
    form.committee_name = committeeMember?.committee_name ?? '';
    form.role = committeeMember?.role ?? '';
    form.date_assigned = formatDate(committeeMember?.date_assigned);
    form.date_removed = formatDate(committeeMember?.date_removed);
    form.status = committeeMember?.status ?? 'Active';

    await nextTick();
    isPreFilling.value = false;
    markClean();
});

watch(() => form.status, () => {
    clearError('status');
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Edit Committee Member</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Update committee assignment information for this cooperative.</p>
                    </div>
                    <Button variant="outline" @click="handleCancel()">
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
                        <CardDescription>Review the assigned member and update committee details.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="space-y-4">
                            <div v-if="cooperative" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Committee member under</p>
                                    <p class="truncate text-sm font-semibold">{{ cooperative.name }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ cooperative.region }}{{ cooperative.classification ? ' · ' + cooperative.classification : '' }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    {{ cooperative.status ?? 'Active' }}
                                </span>
                            </div>

                            <div>
                                <label class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Assigned Member</label>
                                <div class="mt-2 flex items-center gap-3 rounded-lg border bg-muted/30 p-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary">
                                        {{ getInitials(getMemberName()) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold">{{ getMemberName() }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ props.committeeMember.member?.member_code || 'No member code available' }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground">
                                        <Lock class="h-3.5 w-3.5" />
                                        <span>Fixed</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Member cannot be changed after assignment.</p>
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
                    <Button type="submit" :disabled="form.processing || !canUpdateMember" class="gap-2">
                        <Save class="h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>