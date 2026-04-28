<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Eye, PiggyBank, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface Cooperative {
    id: number;
    name: string;
}

interface FinanceSavings {
    id: number;
    account_number: string;
    account_status: string;
    current_balance: string;
    interest_rate: string;
    member?: { first_name?: string; last_name?: string };
}

const props = defineProps<{
    cooperative: Cooperative;
    savings: FinanceSavings[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreate = computed(() => permissions.value.includes('open finance-savings-accounts'));
const canEdit = computed(() => permissions.value.includes('update finance-savings-accounts'));
const canDelete = computed(() => permissions.value.includes('close finance-savings-accounts'));

const createHref = computed(() => `/finance/savings/create?coop_id=${props.cooperative.id}`);
const viewHref = (savingsId: number) => `/finance/savings/${savingsId}?coop_id=${props.cooperative.id}`;
const editHref = (savingsId: number) => `/finance/savings/${savingsId}/edit?coop_id=${props.cooperative.id}`;

const deleteSavings = (savingsId: number) => {
    if (!canDelete.value) return;

    if (!window.confirm('Close this savings account?')) {
        return;
    }

    router.delete(`/finance/savings/${savingsId}`, {
        preserveScroll: true,
        data: { coop_id: props.cooperative.id },
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Savings</h2>
                <p class="text-sm text-muted-foreground">Savings accounts for {{ cooperative.name }} only.</p>
            </div>
            <Link v-if="canCreate" :href="createHref">
                <Button class="gap-2">
                    <Plus class="h-4 w-4" />
                    Open Savings Account
                </Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-border bg-background shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Member</th>
                        <th class="px-4 py-3 text-left">Balance</th>
                        <th class="px-4 py-3 text-left">Interest</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="savings.length === 0">
                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No savings accounts found for this cooperative.</td>
                    </tr>
                    <tr v-for="item in savings" :key="item.id" class="border-t">
                        <td class="px-4 py-3">{{ item.account_number }}</td>
                        <td class="px-4 py-3">{{ item.member?.first_name }} {{ item.member?.last_name }}</td>
                        <td class="px-4 py-3">{{ formatPhilippinePeso(item.current_balance) }}</td>
                        <td class="px-4 py-3">{{ item.interest_rate }}%</td>
                        <td class="px-4 py-3">
                            <Badge :class="[getFinanceStatusBadgeClass(item.account_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                {{ item.account_status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex flex-wrap items-center justify-center gap-2">
                                <Link :href="viewHref(item.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Eye class="h-4 w-4" />
                                        View
                                    </Button>
                                </Link>
                                <Link v-if="canEdit" :href="editHref(item.id)">
                                    <Button variant="ghost" size="sm" class="gap-2">
                                        <Pencil class="h-4 w-4" />
                                        Edit
                                    </Button>
                                </Link>
                                <Button v-if="canDelete" type="button" variant="ghost" size="sm" class="gap-2 text-destructive hover:text-destructive" @click="deleteSavings(item.id)">
                                    <Trash2 class="h-4 w-4" />
                                    Close
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>