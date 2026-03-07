<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Building2, Plus, Users } from 'lucide-vue-next';
import type { Paginator, PaginatorLink } from '@/types/ui';

interface Office {
    id: number;
    name: string;
    code: string;
    status: string;
    allow_self_registration: boolean;
    members_count: number;
}

defineProps<{
    offices: Paginator<Office>;
}>();
</script>

<template>
    <AppLayout>
        <Head title="Offices" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Offices</h1>
                    <p class="text-muted-foreground text-sm">Manage offices within your network.</p>
                </div>
                <Button @click="router.visit('/sdn-admin/offices/create')">
                    <Plus class="mr-2 h-4 w-4" />
                    New Office
                </Button>
            </div>

            <Card>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">Office</th>
                                <th class="px-4 py-3 text-left font-medium">Code</th>
                                <th class="px-4 py-3 text-left font-medium">Status</th>
                                <th class="px-4 py-3 text-left font-medium">Members</th>
                                <th class="px-4 py-3 text-left font-medium">Self-Register</th>
                                <th class="px-4 py-3 text-left font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="offices.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    No offices yet. Create the first one.
                                </td>
                            </tr>
                            <tr
                                v-for="office in offices.data"
                                :key="office.id"
                                class="border-b last:border-0 hover:bg-muted/30"
                            >
                                <td class="px-4 py-3 font-medium">
                                    <div class="flex items-center gap-2">
                                        <Building2 class="h-4 w-4 text-muted-foreground" />
                                        {{ office.name }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ office.code }}</td>
                                <td class="px-4 py-3">
                                    <Badge :variant="office.status === 'active' ? 'default' : 'secondary'">
                                        {{ office.status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1">
                                        <Users class="h-4 w-4 text-muted-foreground" />
                                        {{ office.members_count }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="office.allow_self_registration ? 'default' : 'outline'">
                                        {{ office.allow_self_registration ? 'Yes' : 'No' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="router.visit(`/sdn-admin/offices/${office.id}/edit`)"
                                    >
                                        Edit
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <!-- Pagination -->
            <div v-if="offices.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in offices.links" :key="link.label">
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
