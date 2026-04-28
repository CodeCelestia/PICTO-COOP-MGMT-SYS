<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Landmark, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Cooperative {
    id: number;
    name: string;
}

interface FundingSource {
    id: number;
    funder_name: string;
    funder_type: string;
    status: string;
    amount_allocated: string | null;
    amount_released: string | null;
    activity?: { title?: string } | null;
}

const props = defineProps<{
    cooperative: Cooperative;
    fundingSources: FundingSource[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('create finance-funding-sources'));
const canEdit = computed(() => permissions.value.includes('update finance-funding-sources'));
const canDelete = computed(() => permissions.value.includes('delete finance-funding-sources'));

const createHref = computed(() => `/finance/funding-sources/create?coop_id=${props.cooperative.id}`);
const viewHref = (sourceId: number) => `/finance/funding-sources/${sourceId}?coop_id=${props.cooperative.id}`;
const editHref = (sourceId: number) => `/finance/funding-sources/${sourceId}/edit?coop_id=${props.cooperative.id}`;

const deleteSource = (sourceId: number) => {
    if (!canDelete.value) return;

    if (!window.confirm('Delete this funding source?')) {
        return;
    }

    router.delete(`/finance/funding-sources/${sourceId}`, {
        preserveScroll: true,
        data: { coop_id: props.cooperative.id },
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Funding Sources</h2>
                <p class="text-sm text-muted-foreground">Funding sources for {{ cooperative.name }} only.</p>
            </div>
            <Link v-if="canCreate" :href="createHref">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Add Funding Source
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Funder</th>
                        <th class="px-4 py-3 text-left">Activity</th>
                        <th class="px-4 py-3 text-left">Amount Allocated</th>
                        <th class="px-4 py-3 text-left">Amount Released</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="fundingSources.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No funding sources found for this cooperative.</td>
                    </tr>
                    <tr v-for="source in fundingSources" :key="source.id" class="border-t">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <Landmark class="h-4 w-4 text-muted-foreground" />
                                <span>{{ source.funder_name }}</span>
                                <span class="text-xs text-muted-foreground">({{ source.funder_type }})</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ source.activity?.title || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(source.amount_allocated) }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(source.amount_released) }}</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(source.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ source.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="viewHref(source.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="canEdit" :href="editHref(source.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button v-if="canDelete" type="button" variant="ghost" size="sm" class="gap-2 text-destructive hover:text-destructive" @click="deleteSource(source.id)">
                                    <Trash2 class="h-4 w-4" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>