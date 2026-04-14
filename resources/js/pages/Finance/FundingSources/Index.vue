<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface FundingSource {
    id: number;
    activity_id: number | null;
    category: 'activity' | 'project' | 'member_concern';
    funder_name: string;
    funder_type: string;
    status: string;
    amount_allocated: string | null;
    amount_released: string | null;
    activity?: { title?: string };
    cooperative?: { name?: string };
}

defineProps<{
    fundingSources: {
        data: FundingSource[];
    };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_approve: boolean;
    };
}>();

const formatAmount = (value: string | null) => {
    if (!value) return '0.00';
    const num = Number(value);
    if (Number.isNaN(num)) return value;
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const categoryLabel = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'Member Concern';
    if (category === 'project') return 'Project';
    return 'Activity';
};

const categoryBadgeClass = (category: FundingSource['category']) => {
    if (category === 'member_concern') return 'border border-orange-200 bg-orange-100 text-orange-800';
    if (category === 'project') return 'border border-green-200 bg-green-100 text-green-800';
    return 'border border-blue-200 bg-blue-100 text-blue-800';
};
</script>

<template>
    <Head title="Finance - Funding Sources" />

    <FinanceShellLayout active-tab="funding-sources">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Funding Sources</h1>
                <p class="text-sm text-muted-foreground">View all funding sources for the cooperative, including activity-linked funding sources, project support, and member concern entries.</p>
            </div>
            <Link v-if="permissions.can_create" href="/finance/funding-sources/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
                New Funding Source
            </Link>
        </div>

        <div class="overflow-hidden rounded-lg border bg-card">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Funder</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Activity</th>
                        <th class="px-4 py-3 text-left">Cooperative</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Allocated</th>
                        <th class="px-4 py-3 text-left">Released</th>
                        <th class="px-4 py-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="fundingSources.data.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="8">No funding sources found.</td>
                    </tr>
                    <tr v-for="item in fundingSources.data" :key="item.id" class="border-t">
                        <td class="px-4 py-3">{{ item.funder_name }} <span class="text-xs text-muted-foreground">({{ item.funder_type }})</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium" :class="categoryBadgeClass(item.category)">
                                {{ categoryLabel(item.category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex w-fit rounded-md border border-border bg-muted px-2 py-0.5 text-xs font-medium text-foreground">
                                    {{ item.activity_id && item.activity?.title ? `From Activity: ${item.activity.title}` : (item.activity_id === null ? 'General Fund' : 'Manual Entry') }}
                                </span>
                                <span class="text-xs text-muted-foreground">{{ item.activity_id && item.activity?.title ? item.activity.title : (item.activity_id === null ? 'Not tied to a specific activity' : 'No activity linked') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ item.cooperative?.name || 'N/A' }}</td>
                        <td class="px-4 py-3">{{ item.status }}</td>
                        <td class="px-4 py-3">{{ formatAmount(item.amount_allocated) }}</td>
                        <td class="px-4 py-3">{{ formatAmount(item.amount_released) }}</td>
                        <td class="px-4 py-3">
                            <Link :href="`/finance/funding-sources/${item.id}`" class="text-primary hover:underline">Open</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </FinanceShellLayout>
</template>
