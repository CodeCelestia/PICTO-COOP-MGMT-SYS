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
import { Textarea } from '@/components/ui/textarea';
import { useFormUx } from '@/composables/useFormUx';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface OfficerMember {
    id: number;
    first_name?: string | null;
    last_name?: string | null;
    member_code?: string | null;
}

interface Officer {
    id: number;
    member_id: number;
    coop_id: number;
    position: string;
    committee: string | null;
    term_start: string | null;
    term_end: string | null;
    status: string;
    reason_for_change?: string | null;
    election_year?: number | null;
    member?: OfficerMember | null;
    cooperative?: Cooperative | null;
}

interface OfficerTermHistory {
    id: number;
    position: string;
    committee: string | null;
    term_start: string | null;
    term_end: string | null;
    status: string;
    reason_for_change: string | null;
    election_year: number | null;
    recorded_by: string | null;
    recorded_at: string | null;
}

interface Props {
    officer: Officer;
    cooperative?: Cooperative | null;
    termHistory: OfficerTermHistory[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateOfficer = computed(() => permissions.value.includes('update officers-&-committees'));

const validatedReturnTo = computed(() => {
    const returnTo = new URLSearchParams((page.url || '').split('?')[1] || '').get('return_to') || '';

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

const cooperative = computed(() => props.cooperative || props.officer.cooperative || null);
const termHistory = computed(() => props.termHistory);

const form = useForm({
    coop_id: props.officer.coop_id.toString(),
    position: '',
    committee: '',
    term_start: '',
    term_end: '',
    status: 'Active',
    reason_for_change: '',
    election_year: '',
});

const originalStatus = ref('');

const {
    isDirty,
    isPreFilling,
    markClean,
    inputErrorClass,
    clearError,
    handleCancel,
    triggerErrorShake,
    showErrorShake,
} = useFormUx(form);

const isSubmitting = computed(() => form.processing);

const parseDateLocal = (dateString?: string | null) => {
    if (!dateString) {
        return '';
    }

    return String(dateString).slice(0, 10);
};

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

const getInitials = (name?: string | null) => {
    if (!name) return '--';

    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '--';
    if (parts.length === 1) return (parts[0][0] || '-').toUpperCase();
    return ((parts[0][0] || '-') + (parts[1][0] || '-')).toUpperCase();
};

const getMemberName = () => {
    const member = props.officer.member;
    if (!member) return 'Assigned member';

    const parts = [member.last_name, member.first_name].filter(Boolean);
    return parts.length > 0 ? parts.join(', ') : 'Assigned member';
};

const submit = () => {
    if (!canUpdateOfficer.value) {
        return;
    }

    if (form.status && form.status.toLowerCase() !== 'active' && !form.reason_for_change) {
        form.setError('reason_for_change', 'Reason for change is required when status is not Active.');
        triggerErrorShake();
        return;
    }

    form.transform((data) => ({
        ...data,
        return_to: validatedReturnTo.value,
    })).put(`/officers/${props.officer.id}`, {
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
    handleCancel({ confirmTitle: 'Discard officer changes?', confirmText: 'You have unsaved changes. Are you sure you want to discard them?' });
};

const formatTerm = (start: string | null, end: string | null) => {
    if (!start && !end) return 'N/A';
    if (start && end) return `${start} - ${end}`;
    return start || end || 'N/A';
};

const formatDateTime = (date: string | null) => {
    if (!date) return 'N/A';

    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

watch(() => form.status, (newStatus) => {
    if (newStatus?.toLowerCase() === 'active') {
        form.reason_for_change = '';
        clearError('reason_for_change');
    }
});

onMounted(async () => {
    isPreFilling.value = true;

    const officer = props.officer;
    form.position = officer?.position ?? '';
    form.committee = officer?.committee ?? '';
    form.term_start = parseDateLocal(officer?.term_start);
    form.term_end = parseDateLocal(officer?.term_end);
    form.status = officer?.status ?? '';
    form.reason_for_change = officer?.reason_for_change ?? '';
    form.election_year = officer?.election_year ? String(officer.election_year) : '';
    originalStatus.value = officer?.status ?? '';

    await nextTick();
    isPreFilling.value = false;
    markClean();
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Edit Officer</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Update officer information for this cooperative.</p>
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
                            Officer Information
                        </CardTitle>
                        <CardDescription>Review the assigned member and update term details.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="space-y-4">
                            <div v-if="cooperative" class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Creating officer under</p>
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
                                        <p class="truncate text-xs text-muted-foreground">{{ props.officer.member?.member_code ?? 'No member code available' }}</p>
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
                                    <Label for="position">Position / Role <span class="ml-0.5 text-red-500">*</span></Label>
                                    <Input
                                        id="position"
                                        v-model="form.position"
                                        placeholder="Chairperson, Treasurer, etc."
                                        :class="inputErrorClass('position')"
                                        @input="clearError('position')"
                                    />
                                    <p v-if="form.errors.position" class="mt-1 text-sm text-red-500">{{ form.errors.position }}</p>
                                </div>

                                <div>
                                    <Label for="committee">Committee <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input
                                        id="committee"
                                        v-model="form.committee"
                                        placeholder="Audit, Education, Credit"
                                        :class="inputErrorClass('committee')"
                                        @input="clearError('committee')"
                                    />
                                    <p v-if="form.errors.committee" class="mt-1 text-sm text-red-500">{{ form.errors.committee }}</p>
                                </div>

                                <div>
                                    <Label for="term_start">Term Start Date <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input
                                        id="term_start"
                                        v-model="form.term_start"
                                        type="date"
                                        :class="inputErrorClass('term_start')"
                                        @input="clearError('term_start')"
                                    />
                                    <p v-if="form.errors.term_start" class="mt-1 text-sm text-red-500">{{ form.errors.term_start }}</p>
                                </div>

                                <div>
                                    <Label for="term_end">Term End Date <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                    <Input
                                        id="term_end"
                                        v-model="form.term_end"
                                        type="date"
                                        :class="inputErrorClass('term_end')"
                                        @input="clearError('term_end')"
                                    />
                                    <p v-if="form.errors.term_end" class="mt-1 text-sm text-red-500">{{ form.errors.term_end }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Status Information</CardTitle>
                        <CardDescription>Set the officer status and provide reason when applicable.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="status">Status <span class="ml-0.5 text-red-500">*</span></Label>
                                <Select
                                    v-model="form.status"
                                    @update:modelValue="(value) => {
                                        clearError('status');
                                        if (String(value).toLowerCase() === 'active') {
                                            form.reason_for_change = '';
                                            clearError('reason_for_change');
                                        }
                                    }"
                                >
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
                                <Label for="election_year">Election Year <span class="ml-1 font-normal text-xs text-muted-foreground">(Optional)</span></Label>
                                <Input
                                    id="election_year"
                                    v-model="electionYearDate"
                                    type="date"
                                    :class="inputErrorClass('election_year')"
                                    @input="clearError('election_year')"
                                />
                                <p class="mt-1 text-xs text-muted-foreground">Choose any date in the election year. Only the year will be saved.</p>
                                <p v-if="form.errors.election_year" class="mt-1 text-sm text-red-500">{{ form.errors.election_year }}</p>
                            </div>

                            <div v-if="form.status && form.status.toLowerCase() !== 'active'" class="md:col-span-2">
                                <Label for="reason_for_change">Reason for Change <span class="ml-0.5 text-red-500">*</span></Label>
                                <Textarea
                                    id="reason_for_change"
                                    v-model="form.reason_for_change"
                                    placeholder="Election, appointment, resignation, removal, etc."
                                    :class="inputErrorClass('reason_for_change')"
                                    @input="clearError('reason_for_change')"
                                    rows="3"
                                />
                                <p v-if="form.errors.reason_for_change" class="mt-1 text-sm text-red-500">{{ form.errors.reason_for_change }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3 mt-6">
                    <Button variant="outline" type="button" @click="cancel">Cancel</Button>
                    <Button type="submit" class="gap-2" :disabled="isSubmitting || !canUpdateOfficer">
                        <Save class="h-4 w-4" />
                        Save Changes
                    </Button>
                </div>
            </form>

            <Card>
                <CardHeader class="pb-4">
                    <CardTitle class="text-xl">Officer Term History</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left text-muted-foreground">
                                    <th class="py-3 pr-4 font-medium">Position</th>
                                    <th class="py-3 pr-4 font-medium">Committee</th>
                                    <th class="py-3 pr-4 font-medium">Term</th>
                                    <th class="py-3 pr-4 font-medium">Status</th>
                                    <th class="py-3 pr-4 font-medium">Reason</th>
                                    <th class="py-3 pr-4 font-medium">Recorded By</th>
                                    <th class="py-3 pr-4 font-medium">Recorded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="termHistory.length === 0">
                                    <td colspan="7" class="py-8 text-center text-muted-foreground">No term history recorded yet.</td>
                                </tr>
                                <tr v-for="history in termHistory" :key="history.id" class="border-b last:border-0">
                                    <td class="py-3 pr-4 text-foreground">{{ history.position }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ history.committee || 'N/A' }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ formatTerm(history.term_start, history.term_end) }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ history.status }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ history.reason_for_change || 'N/A' }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ history.recorded_by || 'N/A' }}</td>
                                    <td class="py-3 pr-4 text-muted-foreground">{{ formatDateTime(history.recorded_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>