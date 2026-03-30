<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { Users, Save, X } from 'lucide-vue-next';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
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
    cooperatives: Cooperative[];
    members: MemberOption[];
    termHistory: OfficerTermHistory[];
}

const props = defineProps<Props>();

const termHistory = computed(() => props.termHistory);

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    coop_id: props.officer.coop_id.toString(),
    member_id: props.officer.member_id.toString(),
    position: props.officer.position,
    committee: props.officer.committee || '',
    term_start: props.officer.term_start || '',
    term_end: props.officer.term_end || '',
    status: props.officer.status || 'Active',
    reason_for_change: '',
    election_year: '',
});

const filteredMembers = computed(() => {
    if (!form.coop_id) return props.members;
    return props.members.filter(member => member.coop_id.toString() === form.coop_id);
});

const submit = () => {
    form.put(`/officers/${props.officer.id}`, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/officers');
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
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Officer</h1>
                <p class="mt-1 text-sm text-gray-500">Update officer assignment.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Officer Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative</Label>
                                <Select v-model="form.coop_id" :disabled="isCoopAdmin">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.coop_id }">
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
                                <Label for="member_id">Member</Label>
                                <Select v-model="form.member_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.member_id }">
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
                            </div>

                            <div>
                                <Label for="position">Position</Label>
                                <Input id="position" v-model="form.position" placeholder="Chairperson, Treasurer, etc." />
                                <p v-if="form.errors.position" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.position }}
                                </p>
                            </div>

                            <div>
                                <Label for="committee">Committee</Label>
                                <Input id="committee" v-model="form.committee" placeholder="Audit, Education, Credit" />
                                <p v-if="form.errors.committee" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.committee }}
                                </p>
                            </div>

                            <div>
                                <Label for="term_start">Term Start</Label>
                                <Input id="term_start" v-model="form.term_start" type="date" />
                                <p v-if="form.errors.term_start" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.term_start }}
                                </p>
                            </div>

                            <div>
                                <Label for="term_end">Term End</Label>
                                <Input id="term_end" v-model="form.term_end" type="date" />
                                <p v-if="form.errors.term_end" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.term_end }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Retired">Retired</SelectItem>
                                        <SelectItem value="Removed">Removed</SelectItem>
                                        <SelectItem value="Resigned">Resigned</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="reason_for_change">Reason for Change</Label>
                                <Textarea
                                    id="reason_for_change"
                                    v-model="form.reason_for_change"
                                    placeholder="Election, appointment, resignation, removal, etc."
                                />
                                <p v-if="form.errors.reason_for_change" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.reason_for_change }}
                                </p>
                            </div>

                            <div>
                                <Label for="election_year">Election Year</Label>
                                <Input
                                    id="election_year"
                                    v-model="form.election_year"
                                    type="number"
                                    min="1900"
                                    max="2100"
                                    placeholder="2026"
                                />
                                <p v-if="form.errors.election_year" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.election_year }}
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
                            Update Officer
                        </Button>
                    </div>
                </form>
            </div>

            <div class="mt-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Officer Term History</h2>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Position</TableHead>
                            <TableHead>Committee</TableHead>
                            <TableHead>Term</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Reason</TableHead>
                            <TableHead>Recorded By</TableHead>
                            <TableHead>Recorded At</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="termHistory.length === 0">
                            <TableCell colspan="7" class="text-center text-gray-500">
                                No term history recorded yet.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="history in termHistory" :key="history.id">
                            <TableCell class="text-sm text-gray-700">{{ history.position }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ history.committee || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">
                                {{ formatTerm(history.term_start, history.term_end) }}
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ history.status }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ history.reason_for_change || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ history.recorded_by || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDateTime(history.recorded_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
