<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Users, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { confirmAction } from '@/lib/alerts';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Cooperative {
    id: number;
    name: string;
}

interface Activity {
    id: number;
    title: string;
    coop_id: number;
    cooperative?: Cooperative;
}

interface Member {
    id: number;
    full_name: string;
}

interface Participant {
    id: number;
    activity_id: number;
    member_id: number;
    role: string | null;
    date_joined: string | null;
    is_beneficiary: boolean;
    remarks: string | null;
    activity: Activity;
    member: Member;
}

interface Props {
    participants: {
        data: Participant[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    activities: Activity[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        activity_id?: string;
        coop_id?: string;
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);

const page = usePage();
const auth = computed(() => page.props.auth as { roles?: string[]; isCoopAdmin?: boolean; user?: { account_type?: string } } | undefined);
const roles = computed<string[]>(() => auth.value?.roles || []);
const accountType = computed(() => auth.value?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const canCreate = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEdit = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDelete = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const activityId = ref(props.filters.activity_id || 'all');
const coopId = ref(props.filters.coop_id || 'all');

const applyFilters = () => {
    router.get('/activity-participants', {
        search: search.value,
        activity_id: activityId.value === 'all' ? '' : activityId.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    activityId.value = 'all';
    coopId.value = 'all';
    router.get('/activity-participants');
};

const deleteParticipant = async (participant: Participant) => {
    const confirmed = await confirmAction({
        title: 'Remove participant?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Remove',
    });

    if (!confirmed) return;

    router.delete(`/activity-participants/${participant.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const beneficiaryLabel = (value: boolean) => (value ? 'Yes' : 'No');
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Activity Participants</h1>
                    <p class="mt-1 text-sm text-gray-500">Track activity participation details</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/activities" class="text-sm text-blue-600 hover:underline">
                        View Activities
                    </Link>
                    <Link v-if="canCreate" href="/activity-participants/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Participant
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Member or activity..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger id="coop_filter">
                                <SelectValue placeholder="All Cooperatives" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Cooperatives</SelectItem>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                    {{ coop.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Activity</label>
                        <Select v-model="activityId">
                            <SelectTrigger id="activity_filter">
                                <SelectValue placeholder="All Activities" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Activities</SelectItem>
                                <SelectItem v-for="activity in activities" :key="activity.id" :value="activity.id.toString()">
                                    {{ activity.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">Clear Filters</Button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Member</TableHead>
                            <TableHead>Activity</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Date Joined</TableHead>
                            <TableHead>Beneficiary</TableHead>
                            <TableHead v-if="showActions" class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="participants.data.length === 0">
                            <TableCell :colspan="showActions ? 7 : 6" class="text-center text-gray-500">
                                No participants found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="participant in participants.data" :key="participant.id">
                            <TableCell class="text-sm text-gray-900">{{ participant.member.full_name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ participant.activity.title }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ participant.activity.cooperative?.name || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ participant.role || 'N/A' }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatDate(participant.date_joined) }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ beneficiaryLabel(participant.is_beneficiary) }}</TableCell>
                            <TableCell v-if="showActions" class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link v-if="canEdit" :href="`/activity-participants/${participant.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteParticipant(participant)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <div v-if="participants.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (participants.current_page - 1) * participants.per_page + 1 }} to
                            {{ Math.min(participants.current_page * participants.per_page, participants.total) }} of
                            {{ participants.total }} participants
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in participants.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === participants.current_page"
                                @click="router.get('/activity-participants', { ...filters, page })"
                            >
                                {{ page }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
