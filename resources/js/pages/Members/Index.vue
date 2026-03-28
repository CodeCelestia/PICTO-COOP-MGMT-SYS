<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Search, Eye } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    coop_id: number;
    first_name: string;
    last_name: string;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    region: string | null;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    phone: string | null;
    email: string | null;
    date_joined: string | null;
    membership_type: string | null;
    membership_status: 'Active' | 'Suspended' | 'Resigned' | 'Deceased' | null;
    share_capital: string | null;
    educational_attainment: string | null;
    primary_livelihood: string | null;
    sector: string | null;
    full_name: string;
    age: number | null;
    cooperative: Cooperative;
}

interface Props {
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        membership_status?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const page = usePage();
const roles = computed<string[]>(() => (page.props.auth?.roles as string[]) || []);
const accountType = computed(() => page.props.auth?.user?.account_type as string | undefined);
const isCoopAdmin = computed(() => Boolean(page.props.auth?.isCoopAdmin));
const isProvincialAdmin = computed(() => roles.value.includes('Provincial Admin') || accountType.value === 'Provincial Admin');
const isOfficer = computed(() => roles.value.includes('Officer') || accountType.value === 'Officer');
const canCreateMember = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const canEditMember = computed(() => isProvincialAdmin.value || isCoopAdmin.value || isOfficer.value);
const canDeleteMember = computed(() => isProvincialAdmin.value || isCoopAdmin.value);
const showActions = computed(() => canEditMember.value || canDeleteMember.value);

const search = ref(props.filters.search || '');
const membershipStatus = ref(props.filters.membership_status || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.members.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const applyFilters = () => {
    router.get('/members', {
        search: search.value,
        membership_status: membershipStatus.value === 'all' ? '' : membershipStatus.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    membershipStatus.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/members');
};

const deleteMember = async (member: Member) => {
    const confirmed = await confirmAction({
        title: 'Delete member?',
        text: `Are you sure you want to delete ${member.full_name}? This action cannot be undone.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/members/${member.id}`, {
        preserveScroll: true,
    });
};

const getStatusBadgeColor = (status: string | null) => {
    if (!status) return 'bg-gray-100 text-gray-800 border-gray-200';
    
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
        'Resigned': 'bg-red-100 text-red-800 border-red-200',
        'Deceased': 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatLocation = (member: Member) => {
    const parts = [
        member.city_municipality,
        member.province,
    ].filter(Boolean);
    
    return parts.join(', ') || 'N/A';
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Member Management</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage cooperative member profiles
                    </p>
                </div>
                <Link v-if="canCreateMember" href="/members/create">
                    <Button class="gap-2">
                        <Plus class="h-4 w-4" />
                        Register Member
                    </Button>
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Name or Email..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Membership Status</label>
                        <Select v-model="membershipStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="All Statuses" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Statuses</SelectItem>
                                <SelectItem value="Active">Active</SelectItem>
                                <SelectItem value="Suspended">Suspended</SelectItem>
                                <SelectItem value="Resigned">Resigned</SelectItem>
                                <SelectItem value="Deceased">Deceased</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Rows Per Page</label>
                        <div class="flex gap-2">
                            <Select v-model="perPage">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select size" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">5</SelectItem>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
                                    <SelectItem value="custom">Custom</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="perPage === 'custom'"
                                v-model="customPerPage"
                                type="number"
                                min="1"
                                max="500"
                                placeholder="Enter"
                                class="w-28"
                            />
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">
                        Clear Filters
                    </Button>
                </div>
            </div>

            <!-- Members Table -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Member Name</TableHead>
                            <TableHead>Contact</TableHead>
                            <TableHead>Membership</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Date Joined</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="members.data.length === 0">
                            <TableCell :colspan="showActions ? 6 : 5" class="text-center text-gray-500">
                                No members found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="member in members.data" :key="member.id">
                            <TableCell>
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900">{{ member.full_name }}</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div class="flex flex-col text-sm">
                                    <span v-if="member.email" class="text-gray-900">{{ member.email }}</span>
                                    <span v-if="!member.email" class="text-gray-400">N/A</span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-gray-600">{{ member.membership_type || 'N/A' }}</span>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusBadgeColor(member.membership_status)" class="border">
                                    {{ member.membership_status || 'N/A' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-gray-600">{{ formatDate(member.date_joined) }}</span>
                            </TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link :href="`/members/${member.id}`">
                                        <Button variant="ghost" size="sm" class="gap-1.5 hover:bg-slate-100" title="View member">
                                            <Eye class="h-4 w-4" />
                                            View
                                        </Button>
                                    </Link>
                                    <Link v-if="canEditMember" :href="`/members/${member.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-1.5" title="Edit member">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDeleteMember"
                                        @click="deleteMember(member)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-1.5 text-red-600 hover:text-red-700"
                                        title="Delete member"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="members.last_page > 1" class="border-t border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing {{ (members.current_page - 1) * members.per_page + 1 }} to 
                            {{ Math.min(members.current_page * members.per_page, members.total) }} of 
                            {{ members.total }} members
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="page in members.last_page"
                                :key="page"
                                @click="router.get('/members', {
                                    page,
                                    search: search || '',
                                    membership_status: membershipStatus === 'all' ? '' : membershipStatus,
                                    per_page: resolvedPerPage(),
                                })"
                                :variant="page === members.current_page ? 'default' : 'outline'"
                                size="sm"
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
