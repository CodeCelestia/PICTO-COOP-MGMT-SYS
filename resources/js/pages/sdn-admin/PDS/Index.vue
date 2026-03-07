<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { Paginator } from '@/types/ui';

interface PdsRecord {
    id: number;
    first_name: string;
    last_name: string;
    middle_name: string | null;
    email: string | null;
    phone_number: string | null;
    created_at: string;
    office: { id: number; name: string } | null;
    user: { id: number; name: string; email: string; status: string } | null;
}

const props = defineProps<{
    pdsRecords: Paginator<PdsRecord>;
    offices: { id: number; name: string }[];
    filters: { search?: string; office_id?: string };
}>();

const search = ref(props.filters.search ?? '');
const officeId = ref(props.filters.office_id ?? '');

function applyFilters() {
    router.get('/sdn-admin/pds', {
        search: search.value || undefined,
        office_id: officeId.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    officeId.value = '';
    router.get('/sdn-admin/pds', {}, { preserveState: true, replace: true });
}
</script>

<template>
    <AppLayout>
        <Head title="PDS Records" />

        <div class="space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">PDS Records</h1>
                <p class="text-muted-foreground text-sm">Personal Data Sheets across all offices in your SDN.</p>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <Input
                    v-model="search"
                    placeholder="Search by name or email…"
                    class="w-60"
                    @keyup.enter="applyFilters"
                />
                <select
                    v-model="officeId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-sm"
                    @change="applyFilters"
                >
                    <option value="">All Offices</option>
                    <option v-for="o in offices" :key="o.id" :value="String(o.id)">{{ o.name }}</option>
                </select>
                <Button @click="applyFilters">Search</Button>
                <Button v-if="filters.search || filters.office_id" variant="outline" @click="clearFilters">Clear</Button>
            </div>

            <!-- Table -->
            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Name</th>
                                <th class="px-4 py-3 text-left font-medium">Email</th>
                                <th class="px-4 py-3 text-left font-medium">Office</th>
                                <th class="px-4 py-3 text-left font-medium">Account Status</th>
                                <th class="px-4 py-3 text-left font-medium">Created</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="pdsRecords.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    No PDS records found.
                                </td>
                            </tr>
                            <tr
                                v-for="pds in pdsRecords.data"
                                :key="pds.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ pds.last_name }}, {{ pds.first_name }}
                                    <span v-if="pds.middle_name"> {{ pds.middle_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ pds.email ?? '—' }}</td>
                                <td class="px-4 py-3">{{ pds.office?.name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <template v-if="pds.user">
                                        <Badge :variant="pds.user.status === 'active' ? 'default' : 'secondary'">
                                            {{ pds.user.status }}
                                        </Badge>
                                    </template>
                                    <span v-else class="text-muted-foreground text-xs">No account</span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground text-xs">
                                    {{ new Date(pds.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="router.visit(`/sdn-admin/pds/${pds.id}`)"
                                    >
                                        View
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="pdsRecords.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in pdsRecords.links" :key="link.label">
                    <Button
                        v-if="link.url"
                        size="sm"
                        :variant="link.active ? 'default' : 'outline'"
                        @click="router.visit(link.url)"
                        v-html="link.label"
                    />
                    <Button v-else size="sm" variant="outline" disabled v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
