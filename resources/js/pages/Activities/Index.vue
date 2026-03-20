<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ClipboardList, Plus, Pencil, Trash2, Search, HandCoins } from 'lucide-vue-next';
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

interface Officer {
    id: number;
    member: {
        full_name: string;
    };
}

interface Activity {
    id: number;
    coop_id: number;
    title: string;
    description: string | null;
    category: string;
    date_started: string | null;
    date_ended: string | null;
    status: string;
    responsible_officer_id: number | null;
    funding_source: string | null;
    cooperative: Cooperative;
    responsible_officer?: Officer | null;
}

interface Props {
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        category?: string;
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
const coopId = ref(props.filters.coop_id || 'all');
const status = ref(props.filters.status || 'all');
const category = ref(props.filters.category || 'all');

const statusOptions = ['Planned', 'In Progress', 'Completed', 'Cancelled'];
const categoryOptions = ['Project', 'Outreach', 'Event', 'Livelihood', 'Training', 'Infrastructure', 'Other'];

const applyFilters = () => {
    router.get('/activities', {
        search: search.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        status: status.value === 'all' ? '' : status.value,
        category: category.value === 'all' ? '' : category.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    coopId.value = 'all';
    status.value = 'all';
    category.value = 'all';
    router.get('/activities');
};

const deleteActivity = async (activity: Activity) => {
    const confirmed = await confirmAction({
        title: 'Delete activity?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/activities/${activity.id}`, {
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

const formatOfficerName = (activity: Activity) => {
    return activity.responsible_officer?.member?.full_name || 'N/A';
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Activities & Projects</h1>
                    <p class="mt-1 text-sm text-gray-500">Track cooperative activities and projects</p>
                </div>
                <Link v-if="canCreate" href="/activities/create">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        Add Activity
                    </Button>
                </Link>
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
                                placeholder="Title, funding, partner..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger>
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
                        <label class="mb-2 block text-sm font-medium text-gray-700">Category</label>
                        <Select v-model="category">
                            <SelectTrigger>
                                <SelectValue placeholder="All Categories" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Categories</SelectItem>
                                <SelectItem v-for="option in categoryOptions" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                        <Select v-model="status">
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                    {{ option }}
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
                            <TableHead>Activity</TableHead>
                            <TableHead>Cooperative</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Dates</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Responsible</TableHead>
                            <TableHead v-if="showActions" class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="activities.data.length === 0">
                            <TableCell :colspan="showActions ? 7 : 6" class="text-center text-gray-500">
                                No activities found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="activity in activities.data" :key="activity.id">
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <ClipboardList class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ activity.title }}</div>
                                        <div class="text-xs text-gray-500">{{ activity.funding_source || 'No funding source' }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ activity.cooperative.name }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ activity.category }}</TableCell>
                            <TableCell class="text-sm text-gray-600">
                                {{ formatDate(activity.date_started) }} - {{ formatDate(activity.date_ended) }}
                            </TableCell>
                            <TableCell class="text-sm text-gray-600">{{ activity.status }}</TableCell>
                            <TableCell class="text-sm text-gray-600">{{ formatOfficerName(activity) }}</TableCell>
                            <TableCell v-if="showActions" class="text-right">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/activity-funding-sources?activity_id=${activity.id}`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <HandCoins class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Link v-if="canEdit" :href="`/activities/${activity.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteActivity(activity)"
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

                <div v-if="activities.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (activities.current_page - 1) * activities.per_page + 1 }} to
                            {{ Math.min(activities.current_page * activities.per_page, activities.total) }} of
                            {{ activities.total }} activities
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in activities.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === activities.current_page"
                                @click="router.get('/activities', { ...filters, page })"
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
