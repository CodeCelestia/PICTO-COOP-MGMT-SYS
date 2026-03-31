<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Activity,
    Calendar,
    Clock,
    FileText,
    Filter,
    History,
    MapPin,
    Monitor,
    Search,
    Users,
    Eye,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Causer {
    id: number;
    name: string;
    email: string;
}

interface ActivityItem {
    id: number;
    table_name: string | null;
    record_id: number | null;
    action: string;
    changed_by: string;
    changed_at: string | null;
    old_value: Record<string, unknown> | null;
    new_value: Record<string, unknown> | null;
    description: string;
    event: string;
    subject_type: string;
    subject_id: number;
    causer: Causer | null;
    properties: {
        attributes?: Record<string, unknown>;
        old?: Record<string, unknown>;
    };
    created_at: string;
    created_at_full: string;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links?: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface SessionItem {
    id: number;
    user_id: number;
    login_at: string;
    logout_at: string | null;
    ip_address: string;
    device_info: string;
    login_status: string;
    fail_reason: string | null;
    duration_minutes: number | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
}

interface AccountStatusItem {
    id: number;
    previous_status: string;
    new_status: string;
    change_reason: string;
    changed_by: string;
    changed_at: string;
    remarks: string | null;
    user: {
        id: number;
        name: string;
        email: string;
        account_status: string;
    };
}

const props = defineProps<{
    tab: 'audit' | 'sessions' | 'accounts';
    audit: Paginated<ActivityItem> | null;
    sessions: Paginated<SessionItem> | null;
    accounts: Paginated<AccountStatusItem> | null;
    eventTypes: string[];
    subjectTypes: string[];
    filters: {
        audit: {
            search?: string;
            event?: string;
            subject_type?: string;
        };
        sessions: {
            search?: string;
            status?: string;
            user_id?: number;
            date_from?: string;
            date_to?: string;
        };
        accounts: {
            search?: string;
            new_status?: string;
            user_id?: number;
            date_from?: string;
            date_to?: string;
        };
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Activity Logs', href: '/activity-logs' },
];

const activeTab = ref(props.tab || 'audit');

const setTab = (tab: 'audit' | 'sessions' | 'accounts') => {
    activeTab.value = tab;
    router.get('/activity-logs', { tab }, { preserveState: true, preserveScroll: true });
};

const auditSearch = ref(props.filters.audit.search || '');
const auditEvent = ref(props.filters.audit.event || 'all');
const auditSubject = ref(props.filters.audit.subject_type || 'all');
const expandedRow = ref<number | null>(null);

const applyAuditFilters = () => {
    router.get('/activity-logs', {
        tab: 'audit',
        search: auditSearch.value || undefined,
        event: auditEvent.value === 'all' ? undefined : auditEvent.value,
        subject_type: auditSubject.value === 'all' ? undefined : auditSubject.value,
    }, { preserveState: true, preserveScroll: true });
};

const clearAuditFilters = () => {
    auditSearch.value = '';
    auditEvent.value = 'all';
    auditSubject.value = 'all';
    applyAuditFilters();
};

const sessionSearch = ref(props.filters.sessions.search || '');
const sessionStatus = ref(props.filters.sessions.status || 'all');
const sessionDateFrom = ref(props.filters.sessions.date_from || '');
const sessionDateTo = ref(props.filters.sessions.date_to || '');
const expandedSessions = ref<Set<number>>(new Set());

const applySessionFilters = () => {
    router.get('/activity-logs', {
        tab: 'sessions',
        search: sessionSearch.value || undefined,
        status: sessionStatus.value === 'all' ? undefined : sessionStatus.value,
        date_from: sessionDateFrom.value || undefined,
        date_to: sessionDateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const clearSessionFilters = () => {
    sessionSearch.value = '';
    sessionStatus.value = 'all';
    sessionDateFrom.value = '';
    sessionDateTo.value = '';
    applySessionFilters();
};

const accountSearch = ref(props.filters.accounts.search || '');
const accountStatus = ref(props.filters.accounts.new_status || 'all');
const accountDateFrom = ref(props.filters.accounts.date_from || '');
const accountDateTo = ref(props.filters.accounts.date_to || '');

const applyAccountFilters = () => {
    router.get('/activity-logs', {
        tab: 'accounts',
        search: accountSearch.value || undefined,
        new_status: accountStatus.value === 'all' ? undefined : accountStatus.value,
        date_from: accountDateFrom.value || undefined,
        date_to: accountDateTo.value || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const clearAccountFilters = () => {
    accountSearch.value = '';
    accountStatus.value = 'all';
    accountDateFrom.value = '';
    accountDateTo.value = '';
    applyAccountFilters();
};

const toggleExpand = (id: number) => {
    expandedRow.value = expandedRow.value === id ? null : id;
};

const toggleSessionRow = (id: number) => {
    if (expandedSessions.value.has(id)) {
        expandedSessions.value.delete(id);
    } else {
        expandedSessions.value.add(id);
    }
};

const getEventBadgeColor = (event: string) => {
    const colors: Record<string, string> = {
        created: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-500/20 dark:text-green-200 dark:border-green-500/50',
        updated: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-500/20 dark:text-blue-200 dark:border-blue-500/50',
        deleted: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-500/50',
    };
    return colors[event] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-slate-500/20 dark:text-slate-200 dark:border-slate-500/50';
};

const getStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
        Success: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-500/20 dark:text-green-200 dark:border-green-500/50',
        Failed: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-500/50',
        'Locked Out': 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-500/20 dark:text-orange-200 dark:border-orange-500/50',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-slate-500/20 dark:text-slate-200 dark:border-slate-500/50';
};

const getAccountStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
        Active: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-500/20 dark:text-green-200 dark:border-green-500/50',
        Inactive: 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-slate-500/20 dark:text-slate-200 dark:border-slate-500/50',
        Suspended: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-500/20 dark:text-orange-200 dark:border-orange-500/50',
        Locked: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-500/20 dark:text-red-200 dark:border-red-500/50',
        'Pending Approval': 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-500/20 dark:text-yellow-200 dark:border-yellow-500/50',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-slate-500/20 dark:text-slate-200 dark:border-slate-500/50';
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

const tabClasses = (tab: string) => {
    return tab === activeTab.value
        ? 'border-primary text-foreground'
        : 'border-transparent text-muted-foreground hover:text-foreground';
};

const tabBaseClass =
    'rounded-sm border-b-2 pb-3 text-sm font-semibold transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';

const showAudit = computed(() => activeTab.value === 'audit');
const showSessions = computed(() => activeTab.value === 'sessions');
const showAccounts = computed(() => activeTab.value === 'accounts');
</script>

<template>
    <Head title="Activity Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
            <div class="flex flex-col gap-4 rounded-xl border border-border/80 bg-card/95 p-6 shadow-sm md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">Activity Logs</h1>
                    <p class="text-sm text-muted-foreground">Audit trails, session activity, and account status tracking.</p>
                </div>
                <div class="flex items-center gap-2 rounded-md bg-primary px-3 py-1.5 text-xs font-semibold uppercase tracking-widest text-primary-foreground">
                    <Activity class="h-4 w-4" />
                    Provincial Admin
                </div>
            </div>

            <div class="flex flex-wrap gap-4 border-b border-border" role="tablist" aria-label="Activity logs views">
                <button
                    role="tab"
                    :aria-selected="showAudit"
                    :class="[tabBaseClass, tabClasses('audit')]"
                    @click="setTab('audit')"
                >
                    Audit Logs
                </button>
                <button
                    role="tab"
                    :aria-selected="showSessions"
                    :class="[tabBaseClass, tabClasses('sessions')]"
                    @click="setTab('sessions')"
                >
                    Session History
                </button>
                <button
                    role="tab"
                    :aria-selected="showAccounts"
                    :class="[tabBaseClass, tabClasses('accounts')]"
                    @click="setTab('accounts')"
                >
                    Account History
                </button>
            </div>

            <div v-if="showAudit" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Filters
                        </CardTitle>
                        <CardDescription>Search and filter audit logs</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="relative md:col-span-2">
                                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="auditSearch"
                                    placeholder="Search by description or user..."
                                    class="pl-8"
                                    @keyup.enter="applyAuditFilters"
                                />
                            </div>

                            <Select v-model="auditEvent">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Events" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">All Events</SelectItem>
                                        <SelectItem v-for="event in eventTypes" :key="event" :value="event">
                                            {{ event.charAt(0).toUpperCase() + event.slice(1) }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                            <Select v-model="auditSubject">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem v-for="type in subjectTypes" :key="type" :value="type">
                                            {{ type }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button @click="applyAuditFilters" size="sm">
                                <Filter class="mr-2 h-4 w-4" />
                                Apply Filters
                            </Button>
                            <Button @click="clearAuditFilters" variant="outline" size="sm">
                                Clear
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12.5" />
                                    <TableHead>Table</TableHead>
                                    <TableHead>Record ID</TableHead>
                                    <TableHead>Action</TableHead>
                                    <TableHead>Changed By</TableHead>
                                    <TableHead>Changed At</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="audit && audit.data.length > 0">
                                    <template v-for="activity in audit.data" :key="activity.id">
                                        <TableRow class="cursor-pointer hover:bg-muted/50" @click="toggleExpand(activity.id)">
                                            <TableCell>
                                                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                                    <Eye class="h-4 w-4" />
                                                </Button>
                                            </TableCell>
                                            <TableCell>
                                                {{ activity.table_name || 'N/A' }}
                                            </TableCell>
                                            <TableCell>
                                                {{ activity.record_id ?? 'N/A' }}
                                            </TableCell>
                                            <TableCell>
                                                <Badge :class="getEventBadgeColor(activity.event)" class="border">
                                                    {{ activity.action }}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div v-if="activity.causer">
                                                    <div class="font-medium">{{ activity.changed_by }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ activity.causer.email }}</div>
                                                </div>
                                                <span v-else class="text-muted-foreground">{{ activity.changed_by }}</span>
                                            </TableCell>
                                            <TableCell>
                                                <div>
                                                    <div class="font-medium">{{ activity.changed_at || 'N/A' }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ activity.created_at }}</div>
                                                </div>
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="expandedRow === activity.id" class="bg-muted/30">
                                            <TableCell colspan="6" class="p-6">
                                                <div class="space-y-4">
                                                    <div>
                                                        <h4 class="mb-2 text-sm font-semibold">Description:</h4>
                                                        <p class="text-sm text-muted-foreground">{{ activity.description }}</p>
                                                    </div>

                                                    <div class="grid gap-4 md:grid-cols-2">
                                                        <div>
                                                            <h4 class="mb-2 text-sm font-semibold">Old Value:</h4>
                                                            <div class="rounded-md border border-red-200 bg-red-100/40 p-3 text-sm dark:border-red-500/40 dark:bg-red-500/10">
                                                                <pre class="text-xs">{{ JSON.stringify(activity.old_value || {}, null, 2) }}</pre>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <h4 class="mb-2 text-sm font-semibold">New Value:</h4>
                                                            <div class="rounded-md border border-green-200 bg-green-100/40 p-3 text-sm dark:border-green-500/40 dark:bg-green-500/10">
                                                                <pre class="text-xs">{{ JSON.stringify(activity.new_value || {}, null, 2) }}</pre>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </template>
                                <TableRow v-else>
                                    <TableCell colspan="6" class="h-32 text-center">
                                        <div class="flex flex-col items-center justify-center text-muted-foreground">
                                            <FileText class="mb-2 h-12 w-12 opacity-50" />
                                            <p>No audit logs found</p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="audit && audit.last_page > 1" class="flex items-center justify-between border-t p-4">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ ((audit.current_page - 1) * audit.per_page) + 1 }} to
                                {{ Math.min(audit.current_page * audit.per_page, audit.total) }} of
                                {{ audit.total }} results
                            </div>
                            <div class="flex gap-1">
                                <Button
                                    v-for="link in audit.links"
                                    :key="link.label"
                                    :variant="link.active ? 'default' : 'outline'"
                                    :disabled="!link.url"
                                    size="sm"
                                    @click="link.url && router.get(link.url)"
                                >
                                    <span v-html="link.label"></span>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="showSessions" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Session Filters
                        </CardTitle>
                        <CardDescription>Search and filter session history records.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="sessionSearch"
                                    placeholder="Search users, IP..."
                                    class="pl-10"
                                    @keyup.enter="applySessionFilters"
                                />
                            </div>

                            <Select v-model="sessionStatus">
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="Success">Success</SelectItem>
                                    <SelectItem value="Failed">Failed</SelectItem>
                                    <SelectItem value="Locked Out">Locked Out</SelectItem>
                                </SelectContent>
                            </Select>

                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="sessionDateFrom" type="date" class="pl-10" />
                            </div>

                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="sessionDateTo" type="date" class="pl-10" />
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button @click="applySessionFilters" size="sm">
                                <Filter class="mr-2 h-4 w-4" />
                                Apply Filters
                            </Button>
                            <Button v-if="sessionSearch || sessionStatus !== 'all' || sessionDateFrom || sessionDateTo" @click="clearSessionFilters" variant="outline" size="sm">
                                Clear Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12" />
                                    <TableHead>User</TableHead>
                                    <TableHead>Login Time</TableHead>
                                    <TableHead>Logout Time</TableHead>
                                    <TableHead>Duration</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>IP Address</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="sessions && sessions.data.length">
                                    <template v-for="session in sessions.data" :key="session.id">
                                        <TableRow>
                                            <TableCell>
                                                <Button variant="ghost" size="sm" @click="toggleSessionRow(session.id)">
                                                    <Activity class="h-4 w-4 transition-transform" :class="expandedSessions.has(session.id) ? 'rotate-90' : ''" />
                                                </Button>
                                            </TableCell>
                                            <TableCell class="font-medium">
                                                <div>{{ session.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ session.user.email }}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                                    {{ formatDateTime(session.login_at) }}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div v-if="session.logout_at" class="flex items-center gap-2">
                                                    <Clock class="h-4 w-4 text-muted-foreground" />
                                                    {{ formatDateTime(session.logout_at) }}
                                                </div>
                                                <Badge v-else class="border border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/50 dark:bg-blue-500/20 dark:text-blue-200">
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
                                                    <MapPin class="h-4 w-4 text-muted-foreground" />
                                                    <span class="font-mono text-sm">{{ session.ip_address }}</span>
                                                </div>
                                            </TableCell>
                                        </TableRow>

                                        <TableRow v-if="expandedSessions.has(session.id)" class="bg-muted/30">
                                            <TableCell colspan="7" class="p-6">
                                                <div class="grid gap-4 md:grid-cols-2">
                                                    <div class="rounded-lg border border-border bg-card p-4">
                                                        <div class="mb-2 flex items-center gap-2 font-semibold text-foreground">
                                                            <Monitor class="h-4 w-4" />
                                                            Device Information
                                                        </div>
                                                        <p class="text-sm text-muted-foreground">{{ session.device_info || 'N/A' }}</p>
                                                    </div>

                                                    <div class="rounded-lg border border-border bg-card p-4">
                                                        <div class="mb-2 font-semibold text-foreground">Session Details</div>
                                                        <div class="space-y-2 text-sm text-foreground">
                                                            <div><strong>Session ID:</strong> {{ session.id }}</div>
                                                            <div v-if="session.fail_reason">
                                                                <strong>Fail Reason:</strong>
                                                                <span class="text-red-600 dark:text-red-300">{{ session.fail_reason }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </template>

                                <TableRow v-else>
                                    <TableCell colspan="7" class="h-32 text-center text-muted-foreground">
                                        <Activity class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        No session history found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="sessions && sessions.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-6 py-4">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ ((sessions.current_page - 1) * sessions.per_page) + 1 }} to
                                {{ Math.min(sessions.current_page * sessions.per_page, sessions.total) }} of
                                {{ sessions.total }} records
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="sessions.current_page === 1"
                                    @click="router.get(`/activity-logs?page=${sessions.current_page - 1}&tab=sessions`, {}, { preserveScroll: true, preserveState: true })"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="sessions.current_page === sessions.last_page"
                                    @click="router.get(`/activity-logs?page=${sessions.current_page + 1}&tab=sessions`, {}, { preserveScroll: true, preserveState: true })"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="showAccounts" class="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Filter class="h-5 w-5" />
                            Account History Filters
                        </CardTitle>
                        <CardDescription>Search and filter account status history records.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    v-model="accountSearch"
                                    placeholder="Search users, changed by..."
                                    class="pl-10"
                                    @keyup.enter="applyAccountFilters"
                                />
                            </div>

                            <Select v-model="accountStatus">
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

                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="accountDateFrom" type="date" class="pl-10" />
                            </div>

                            <div class="relative">
                                <Calendar class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="accountDateTo" type="date" class="pl-10" />
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <Button @click="applyAccountFilters" size="sm">
                                <Filter class="mr-2 h-4 w-4" />
                                Apply Filters
                            </Button>
                            <Button v-if="accountSearch || accountStatus !== 'all' || accountDateFrom || accountDateTo" @click="clearAccountFilters" variant="outline" size="sm">
                                Clear Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="p-0">
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
                                <TableRow v-for="history in accounts?.data || []" :key="history.id">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                <Users class="h-5 w-5" />
                                            </div>
                                            <div>
                                                <div>{{ history.user.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ history.user.email }}</div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getAccountStatusBadgeColor(history.previous_status)" class="border">
                                            {{ history.previous_status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <History class="h-4 w-4 text-green-600 dark:text-green-300" />
                                            <Badge :class="getAccountStatusBadgeColor(history.new_status)" class="border">
                                                {{ history.new_status }}
                                            </Badge>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="text-foreground">{{ history.change_reason }}</div>
                                        <div v-if="history.remarks" class="mt-1 text-xs text-muted-foreground">
                                            {{ history.remarks }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="font-medium text-foreground">{{ history.changed_by }}</span>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex items-center gap-2 text-foreground">
                                            <Calendar class="h-4 w-4 text-muted-foreground" />
                                            {{ formatDateTime(history.changed_at) }}
                                        </div>
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="accounts && accounts.data.length === 0">
                                    <TableCell colspan="6" class="h-32 text-center text-muted-foreground">
                                        <History class="mx-auto mb-2 h-8 w-8 opacity-50" />
                                        No status change history found
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="accounts && accounts.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-border px-6 py-4">
                            <div class="text-sm text-muted-foreground">
                                Showing {{ ((accounts.current_page - 1) * accounts.per_page) + 1 }} to
                                {{ Math.min(accounts.current_page * accounts.per_page, accounts.total) }} of
                                {{ accounts.total }} records
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="accounts.current_page === 1"
                                    @click="router.get(`/activity-logs?page=${accounts.current_page - 1}&tab=accounts`, {}, { preserveScroll: true, preserveState: true })"
                                >
                                    Previous
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="accounts.current_page === accounts.last_page"
                                    @click="router.get(`/activity-logs?page=${accounts.current_page + 1}&tab=accounts`, {}, { preserveScroll: true, preserveState: true })"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
