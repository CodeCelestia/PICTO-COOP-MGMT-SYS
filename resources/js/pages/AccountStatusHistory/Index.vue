<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { History, Filter, Search, Calendar, TrendingUp, User2 } from 'lucide-vue-next';
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
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface User {
    id: number;
    name: string;
    email: string;
    account_status: string;
}

interface StatusHistory {
    id: number;
    user_id: number;
    previous_status: string;
    new_status: string;
    change_reason: string;
    changed_by: string;
    changed_at: string;
    remarks: string | null;
    user: User;
}

interface Props {
    histories: {
        data: StatusHistory[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        new_status?: string;
        user_id?: number;
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.new_status || 'all');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const applyFilters = () => {
    router.get('/account-status-history', {
        search: search.value || undefined,
        new_status: statusFilter.value === 'all' ? undefined : statusFilter.value,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const getStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
        'Active': 'bg-green-100 text-green-800 border-green-200',
        'Inactive': 'bg-gray-100 text-gray-800 border-gray-200',
        'Suspended': 'bg-orange-100 text-orange-800 border-orange-200',
        'Locked': 'bg-red-100 text-red-800 border-red-200',
        'Pending Approval': 'bg-yellow-100 text-yellow-800 border-yellow-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value !== 'all' || dateFrom.value || dateTo.value;
});
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Account Status History</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Track all account status changes and approvals
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <div class="grid gap-4 md:grid-cols-4">
                    <!-- Search -->
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="search"
                            placeholder="Search users, changed by..."
                            class="pl-10"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Status Filter -->
                    <Select v-model="statusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All Statuses</SelectItem>
                            <SelectItem value="Active">Active</SelectItem>
                            <SelectItem value="Inactive">Inactive</SelectItem>
                            <SelectItem value="Suspended">Suspended</SelectItem>
                            <SelectItem value="Locked">Locked</SelectItem>
                            <SelectItem value="Pending Approval">Pending Approval</SelectItem>
                        </SelectContent>
                    </Select>

                    <!-- Date From -->
                    <div class="relative">
                        <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="dateFrom"
                            type="date"
                            placeholder="From Date"
                            class="pl-10"
                        />
                    </div>

                    <!-- Date To -->
                    <div class="relative">
                        <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <Input
                            v-model="dateTo"
                            type="date"
                            placeholder="To Date"
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="mt-4 flex gap-2">
                    <Button @click="applyFilters" size="sm">
                        <Filter class="mr-2 h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        variant="outline"
                        size="sm"
                    >
                        Clear Filters
                    </Button>
                </div>
            </div>

            <!-- History Table -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>User</TableHead>
                            <TableHead>Previous Status</TableHead>
                            <TableHead>New Status</TableHead>
                            <TableHead>Change Reason</TableHead>
                            <TableHead>Changed By</TableHead>
                            <TableHead>Changed At</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="history in histories.data" :key="history.id">
                            <TableCell class="font-medium">
                                <div class="flex items-center gap-2">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                        <User2 class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <div>{{ history.user.name }}</div>
                                        <div class="text-xs text-gray-500">{{ history.user.email }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Badge :class="getStatusBadgeColor(history.previous_status)" class="border">
                                    {{ history.previous_status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <TrendingUp class="h-4 w-4 text-green-500" />
                                    <Badge :class="getStatusBadgeColor(history.new_status)" class="border">
                                        {{ history.new_status }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell>
                                <div>{{ history.change_reason }}</div>
                                <div v-if="history.remarks" class="mt-1 text-xs text-gray-500">
                                    {{ history.remarks }}
                                </div>
                            </TableCell>
                            <TableCell>
                                <span class="font-medium">{{ history.changed_by }}</span>
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-2">
                                    <Calendar class="h-4 w-4 text-gray-400" />
                                    {{ formatDateTime(history.changed_at) }}
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Empty State -->
                        <TableRow v-if="histories.data.length === 0">
                            <TableCell colspan="6" class="h-32 text-center text-gray-500">
                                <History class="mx-auto mb-2 h-8 w-8 text-gray-300" />
                                No status change history found
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="histories.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
                    <div class="text-sm text-gray-500">
                        Showing {{ ((histories.current_page - 1) * histories.per_page) + 1 }} to 
                        {{ Math.min(histories.current_page * histories.per_page, histories.total) }} of 
                        {{ histories.total }} records
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="histories.current_page === 1"
                            @click="router.get(`/account-status-history?page=${histories.current_page - 1}`)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="histories.current_page === histories.last_page"
                            @click="router.get(`/account-status-history?page=${histories.current_page + 1}`)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
