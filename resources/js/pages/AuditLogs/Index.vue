<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { 
    Search, 
    Filter,
    FileText,
    User,
    Shield,
    Clock,
    Eye
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

interface Causer {
    id: number;
    name: string;
    email: string;
}

interface Activity {
    id: number;
    description: string;
    event: string;
    subject_type: string;
    subject_id: number;
    causer: Causer | null;
    properties: {
        attributes?: Record<string, any>;
        old?: Record<string, any>;
    };
    created_at: string;
    created_at_full: string;
}

interface PaginatedActivities {
    data: Activity[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    activities: PaginatedActivities;
    eventTypes: string[];
    subjectTypes: string[];
    filters: {
        search?: string;
        event?: string;
        subject_type?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Audit Logs', href: '/audit-logs' },
];

const search = ref(props.filters.search || '');
const selectedEvent = ref(props.filters.event || 'all');
const selectedSubjectType = ref(props.filters.subject_type || 'all');
const expandedRow = ref<number | null>(null);

const applyFilters = () => {
    router.get('/audit-logs', {
        search: search.value || undefined,
        event: selectedEvent.value === 'all' ? undefined : selectedEvent.value,
        subject_type: selectedSubjectType.value === 'all' ? undefined : selectedSubjectType.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedEvent.value = 'all';
    selectedSubjectType.value = 'all';
    router.get('/audit-logs');
};

const toggleExpand = (id: number) => {
    expandedRow.value = expandedRow.value === id ? null : id;
};

const getEventBadgeColor = (event: string) => {
    const colors: Record<string, string> = {
        'created': 'bg-green-100 text-green-800',
        'updated': 'bg-blue-100 text-blue-800',
        'deleted': 'bg-red-100 text-red-800',
    };
    return colors[event] || 'bg-gray-100 text-gray-800';
};

const getSubjectIcon = (subjectType: string) => {
    const icons: Record<string, any> = {
        'User': User,
        'Role': Shield,
    };
    return icons[subjectType] || FileText;
};
</script>

<template>
    <Head title="Audit Logs - Coop System" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Audit Logs</h1>
                    <p class="text-muted-foreground">System activity and change history</p>
                </div>
                <div class="flex items-center gap-2 rounded-md bg-primary/10 px-3 py-1.5">
                    <Clock class="h-4 w-4 text-primary" />
                    <span class="text-sm font-medium text-primary">{{ activities.total }} Records</span>
                </div>
            </div>

            <!-- Filters -->
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
                        <!-- Search -->
                        <div class="relative md:col-span-2">
                            <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="Search by description or user..."
                                class="pl-8"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Event Filter -->
                        <Select v-model="selectedEvent">
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

                        <!-- Subject Type Filter -->
                        <Select v-model="selectedSubjectType">
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

                    <!-- Filter Actions -->
                    <div class="mt-4 flex gap-2">
                        <Button @click="applyFilters" size="sm">
                            <Filter class="mr-2 h-4 w-4" />
                            Apply Filters
                        </Button>
                        <Button @click="clearFilters" variant="outline" size="sm">
                            Clear
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Activity Table -->
            <Card>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-12.5"></TableHead>
                                <TableHead>Event</TableHead>
                                <TableHead>Description</TableHead>
                                <TableHead>Subject</TableHead>
                                <TableHead>User</TableHead>
                                <TableHead>Timestamp</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="activities.data.length > 0">
                                <template v-for="activity in activities.data" :key="activity.id">
                                    <!-- Main Row -->
                                    <TableRow class="cursor-pointer hover:bg-muted/50" @click="toggleExpand(activity.id)">
                                        <TableCell>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="h-8 w-8 p-0"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Button>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :class="getEventBadgeColor(activity.event)">
                                                {{ activity.event }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell class="font-medium">
                                            {{ activity.description }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="flex items-center gap-2">
                                                <component :is="getSubjectIcon(activity.subject_type)" class="h-4 w-4 text-muted-foreground" />
                                                <span>{{ activity.subject_type }} #{{ activity.subject_id }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div v-if="activity.causer">
                                                <div class="font-medium">{{ activity.causer.name }}</div>
                                                <div class="text-xs text-muted-foreground">{{ activity.causer.email }}</div>
                                            </div>
                                            <span v-else class="text-muted-foreground">System</span>
                                        </TableCell>
                                        <TableCell>
                                            <div>
                                                <div class="font-medium">{{ activity.created_at }}</div>
                                                <div class="text-xs text-muted-foreground">{{ activity.created_at_full }}</div>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <!-- Expanded Row -->
                                    <TableRow v-if="expandedRow === activity.id" class="bg-muted/30">
                                        <TableCell colspan="6" class="p-6">
                                            <div class="space-y-4">
                                                <!-- Old Values -->
                                                <div v-if="activity.properties.old && Object.keys(activity.properties.old).length > 0">
                                                    <h4 class="mb-2 font-semibold text-sm">Previous Values:</h4>
                                                    <div class="rounded-md bg-red-50 p-3 text-sm">
                                                        <pre class="text-xs">{{ JSON.stringify(activity.properties.old, null, 2) }}</pre>
                                                    </div>
                                                </div>

                                                <!-- New Values -->
                                                <div v-if="activity.properties.attributes && Object.keys(activity.properties.attributes).length > 0">
                                                    <h4 class="mb-2 font-semibold text-sm">New Values:</h4>
                                                    <div class="rounded-md bg-green-50 p-3 text-sm">
                                                        <pre class="text-xs">{{ JSON.stringify(activity.properties.attributes, null, 2) }}</pre>
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

                    <!-- Pagination -->
                    <div v-if="activities.last_page > 1" class="flex items-center justify-between border-t p-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ ((activities.current_page - 1) * activities.per_page) + 1 }} to 
                            {{ Math.min(activities.current_page * activities.per_page, activities.total) }} of 
                            {{ activities.total }} results
                        </div>
                        <div class="flex gap-1">
                            <Button
                                v-for="link in activities.links"
                                :key="link.label"
                                :variant="link.active ? 'default' : 'outline'"
                                :disabled="!link.url"
                                size="sm"
                                @click="link.url && router.get(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
