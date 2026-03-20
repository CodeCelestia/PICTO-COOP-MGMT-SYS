<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Activity, Filter, Search, Calendar, Monitor, MapPin, Clock } from 'lucide-vue-next';
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
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Session {
    id: number;
    user_id: number;
    login_at: string;
    logout_at: string | null;
    ip_address: string;
    device_info: string;
    login_status: string;
    fail_reason: string | null;
    session_token: string | null;
    duration_minutes: number | null;
    user: User;
}

interface Props {
    sessions: {
        data: Session[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
        user_id?: number;
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const expandedRows = ref<Set<number>>(new Set());

const applyFilters = () => {
    router.get('/session-history', {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const toggleRow = (sessionId: number) => {
    if (expandedRows.value.has(sessionId)) {
        expandedRows.value.delete(sessionId);
    } else {
        expandedRows.value.add(sessionId);
    }
};

const getStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
        'Success': 'bg-green-100 text-green-800 border-green-200',
        'Failed': 'bg-red-100 text-red-800 border-red-200',
        'Locked Out': 'bg-orange-100 text-orange-800 border-orange-200',
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

const formatDuration = (minutes: number | null) => {
    if (!minutes) return 'Active';
    
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value || dateFrom.value || dateTo.value;
});
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Session History</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Monitor login/logout activity and security events
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
                            placeholder="Search users, IP..."
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
                            <SelectItem value="">All Statuses</SelectItem>
                            <SelectItem value="Success">Success</SelectItem>
                            <SelectItem value="Failed">Failed</SelectItem>
                            <SelectItem value="Locked Out">Locked Out</SelectItem>
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

            <!-- Sessions Table -->
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12"></TableHead>
                            <TableHead>User</TableHead>
                            <TableHead>Login Time</TableHead>
                            <TableHead>Logout Time</TableHead>
                            <TableHead>Duration</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>IP Address</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <template v-for="session in sessions.data" :key="session.id">
                            <TableRow>
                                <TableCell>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="toggleRow(session.id)"
                                    >
                                        <Activity class="h-4 w-4" :class="expandedRows.has(session.id) ? 'rotate-90' : ''" />
                                    </Button>
                                </TableCell>
                                <TableCell class="font-medium">
                                    <div>{{ session.user.name }}</div>
                                    <div class="text-xs text-gray-500">{{ session.user.email }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <Clock class="h-4 w-4 text-gray-400" />
                                        {{ formatDateTime(session.login_at) }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div v-if="session.logout_at" class="flex items-center gap-2">
                                        <Clock class="h-4 w-4 text-gray-400" />
                                        {{ formatDateTime(session.logout_at) }}
                                    </div>
                                    <Badge v-else class="bg-blue-100 text-blue-800 border-blue-200">
                                        Active
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    {{ formatDuration(session.duration_minutes) }}
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStatusBadgeColor(session.login_status)" class="border">
                                        {{ session.login_status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <MapPin class="h-4 w-4 text-gray-400" />
                                        <span class="font-mono text-sm">{{ session.ip_address }}</span>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Expanded Row Details -->
                            <TableRow v-if="expandedRows.has(session.id)" class="bg-gray-50">
                                <TableCell colspan="7" class="p-6">
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <!-- Device Information -->
                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="mb-2 flex items-center gap-2 font-semibold">
                                                <Monitor class="h-4 w-4" />
                                                Device Information
                                            </div>
                                            <p class="text-sm text-gray-600">{{ session.device_info || 'N/A' }}</p>
                                        </div>

                                        <!-- Session Details -->
                                        <div class="rounded-lg border border-gray-200 bg-white p-4">
                                            <div class="mb-2 font-semibold">Session Details</div>
                                            <div class="space-y-2 text-sm">
                                                <div><strong>Session ID:</strong> {{ session.id }}</div>
                                                <div v-if="session.fail_reason">
                                                    <strong>Fail Reason:</strong> 
                                                    <span class="text-red-600">{{ session.fail_reason }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </template>

                        <!-- Empty State -->
                        <TableRow v-if="sessions.data.length === 0">
                            <TableCell colspan="7" class="h-32 text-center text-gray-500">
                                <Activity class="mx-auto mb-2 h-8 w-8 text-gray-300" />
                                No session history found
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="sessions.last_page > 1" class="flex items-center justify-between border-t px-6 py-4">
                    <div class="text-sm text-gray-500">
                        Showing {{ ((sessions.current_page - 1) * sessions.per_page) + 1 }} to 
                        {{ Math.min(sessions.current_page * sessions.per_page, sessions.total) }} of 
                        {{ sessions.total }} sessions
                    </div>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="sessions.current_page === 1"
                            @click="router.get(`/session-history?page=${sessions.current_page - 1}`)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="sessions.current_page === sessions.last_page"
                            @click="router.get(`/session-history?page=${sessions.current_page + 1}`)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
