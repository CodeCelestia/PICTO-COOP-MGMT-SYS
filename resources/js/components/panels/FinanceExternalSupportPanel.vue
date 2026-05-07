<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import Swal from 'sweetalert2';

interface Cooperative {
    id: number;
    name: string;
}

interface ExternalSupport {
    id: number;
    coop_id: number;
    support_type: string;
    provider_name: string;
    amount: string | null;
    date_granted: string | null;
    date_completed: string | null;
    status: string;
    remarks: string | null;
    financial_record_id?: number | null;
}

const props = withDefaults(defineProps<{
    cooperative: Cooperative;
    externalSupports: ExternalSupport[];
}>(), {
    externalSupports: () => [],
});

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('create financial-&-support'));
const canEdit = computed(() => permissions.value.includes('update financial-&-support'));
const canDelete = computed(() => permissions.value.includes('delete financial-&-support'));

const createHref = computed(() => `/cooperatives/${props.cooperative.id}/finance/external-supports/create`);
const editHref = (supportId: number) => `/cooperatives/${props.cooperative.id}/finance/external-supports/${supportId}/edit`;

const formatDate = (value: string | null) => {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const deleteSupport = (supportId: number) => {
    if (!canDelete.value) return;

    void Swal.fire({
        title: 'Delete External Support?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        router.delete(`/cooperatives/${props.cooperative.id}/finance/external-supports/${supportId}`, {
            preserveScroll: true,
        });
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">External Support</h2>
                <p class="text-sm text-muted-foreground">External support for {{ cooperative.name }} only.</p>
            </div>
            <Link v-if="canCreate" :href="createHref">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Add External Support
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Provider Name</th>
                        <th class="px-4 py-3 text-left">Support Type</th>
                        <th class="px-4 py-3 text-left">Amount</th>
                        <th class="px-4 py-3 text-left">Date Granted</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="externalSupports.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No external support records found.</td>
                    </tr>
                    <tr v-for="support in externalSupports" :key="support.id" class="border-t">
                        <td class="px-4 py-3">{{ support.provider_name }}</td>
                        <td class="px-4 py-3">{{ support.support_type }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(support.amount) }}</td>
                        <td class="px-4 py-3">{{ formatDate(support.date_granted) }}</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(support.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ support.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link v-if="canEdit" :href="editHref(support.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button v-if="canDelete" type="button" variant="ghost" size="sm" class="gap-2 text-destructive hover:text-destructive" @click="deleteSupport(support.id)">
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
