<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Filter, Pencil, Plus, XCircle, ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SavingsRow {
    id: number;
    account_number: string;
    account_status: string;
    current_balance: string;
    interest_rate: string;
    member?: { first_name?: string; last_name?: string };
}

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    coop_id: number;
}

const props = defineProps<{
    savings: { data: SavingsRow[] };
    cooperative?: { id: number; name: string } | null;
    cooperatives?: Array<{ id: number; name: string; status: string }>;
    accountStatuses: string[];
    filters?: { status?: string };
    permissions: {
        can_create: boolean;
        can_edit: boolean;
        can_close: boolean;
        can_record_transaction: boolean;
        can_calculate_interest: boolean;
    };
}>();

const currentUrl = window.location.pathname + window.location.search;
const pathName = window.location.pathname;

const selectedCoop = ref<Cooperative | null>(null);
const selectedMember = ref<Member | null>(null);
const coopMembers = ref<Member[]>([]);
const loadingMembers = ref(false);

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId, 10) : null;
});

const isFromCoopContext = computed(() => pathName.startsWith('/cooperatives/') || !!coopIdFromUrl.value);
const coopContextId = computed(() => props.cooperative?.id || coopIdFromUrl.value || null);
const indexBasePath = computed(() => {
    if (pathName.startsWith('/cooperatives/') && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings`;
    }

    return '/finance/savings';
});

const isGlobalMode = computed(() => !props.cooperative?.id && !coopIdFromUrl.value);
const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showMembersList = computed(() => isGlobalMode.value && selectedCoop.value && !selectedMember.value);
const showSavingsList = computed(() => isGlobalMode.value ? !!selectedMember.value : true);

const selectCoop = async (coop: Cooperative) => {
    selectedCoop.value = coop;
    selectedMember.value = null;
    loadingMembers.value = true;
    
    try {
        const response = await fetch(`/api/cooperatives/${coop.id}/members`);
        coopMembers.value = await response.json();
    } catch (error) {
        console.error('Failed to load members:', error);
        coopMembers.value = [];
    } finally {
        loadingMembers.value = false;
    }
};

const selectMember = (member: Member) => {
    selectedMember.value = member;
    router.get(indexBasePath.value, {
        member_id: member.id,
        status: status.value || undefined,
    }, {
        preserveState: false,
        preserveScroll: false,
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    selectedMember.value = null;
    coopMembers.value = [];
    router.get('/finance/savings');
};

const backToMembers = () => {
    selectedMember.value = null;
    window.scrollTo(0, 0);
};

const status = ref(props.filters?.status || '');

const applyFilter = () => {
    const params: Record<string, any> = {
        status: status.value || undefined,
    };
    
    if (selectedMember.value) {
        params.member_id = selectedMember.value.id;
    }
    
    router.get(indexBasePath.value, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    status.value = '';
    const params: Record<string, string | number | undefined> = {};
    if (selectedMember.value) {
        params.member_id = selectedMember.value.id;
    }

    router.get(indexBasePath.value, params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const createHref = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/create`;
    }

    return currentUrl
        ? `/finance/savings/create?return_to=${encodeURIComponent(currentUrl)}`
        : '/finance/savings/create';
});

const viewHref = (savingsId: number) => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}`;
    }

    return currentUrl
        ? `/finance/savings/${savingsId}?return_to=${encodeURIComponent(currentUrl)}`
        : `/finance/savings/${savingsId}`;
};

const editHref = (savingsId: number) => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}/edit`;
    }

    return currentUrl
        ? `/finance/savings/${savingsId}/edit?return_to=${encodeURIComponent(currentUrl)}`
        : `/finance/savings/${savingsId}/edit`;
};

const closeAccount = (savingsId: number) => {
    if (!props.permissions.can_close) {
        return;
    }

    if (!window.confirm('Are you sure you want to close this savings account?')) {
        return;
    }

    const url = isFromCoopContext.value && coopContextId.value
        ? `/cooperatives/${coopContextId.value}/finance/savings/${savingsId}`
        : `/finance/savings/${savingsId}`;

    router.delete(url);
};
</script>

<template>
    <Head title="Finance - Savings" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="w-full space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Savings Accounts</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Manage member balances, withdrawals, deposits, and interest.</p>
                    </div>
                    <Link v-if="permissions.can_create" :href="createHref">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Open Savings Account
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <div v-if="showCooperativesList" class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Select a Cooperative</CardTitle>
                        <CardDescription>Choose a cooperative before browsing savings accounts.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="coop in cooperatives" :key="coop.id" class="cursor-pointer rounded-lg border bg-card p-4 transition hover:border-primary hover:bg-primary/5" @click="selectCoop(coop)">
                                <h3 class="font-medium text-foreground">{{ coop.name }}</h3>
                                <p class="mt-1 text-xs text-muted-foreground">Click to view members and savings</p>
                            </div>
                        </div>
                        <div v-if="!cooperatives || cooperatives.length === 0" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                            No cooperatives available.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div v-if="showMembersList" class="space-y-4">
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToCooperatives" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Cooperatives
                    </Button>
                    <h2 class="text-lg font-semibold">Members in {{ selectedCoop?.name }}</h2>
                </div>
                <div v-if="loadingMembers" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                    Loading members...
                </div>
                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="member in coopMembers" :key="member.id" class="cursor-pointer rounded-lg border bg-card p-4 transition hover:border-primary hover:bg-primary/5" @click="selectMember(member)">
                        <h3 class="font-medium text-foreground">{{ member.first_name }} {{ member.last_name }}</h3>
                        <p class="mt-1 text-xs text-muted-foreground">Click to view savings</p>
                    </div>
                </div>
                <div v-if="!loadingMembers && (!coopMembers || coopMembers.length === 0)" class="rounded-lg border bg-card p-6 text-center text-muted-foreground">
                    No members found in this cooperative.
                </div>
            </div>

            <div v-if="showSavingsList" class="space-y-4">
                <div v-if="isGlobalMode && selectedMember" class="mb-1 flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="backToMembers" class="gap-2">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Members
                    </Button>
                    <h2 class="text-lg font-semibold">Savings for {{ selectedMember.first_name }} {{ selectedMember.last_name }}</h2>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-lg">Filters</CardTitle>
                        <CardDescription>Narrow records by account status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-wrap items-end gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Status</label>
                                <select v-model="status" class="rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                    <option value="">All</option>
                                    <option v-for="item in accountStatuses" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </div>
                            <Button variant="outline" class="gap-2" @click="applyFilter">
                                <Filter class="h-4 w-4" />
                                Apply
                            </Button>
                            <Button variant="ghost" @click="resetFilter">Reset</Button>
                        </div>
                    </CardContent>
                </Card>

                <div class="overflow-hidden rounded-lg border bg-card">
                    <div class="overflow-x-auto">
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
                            <tr v-if="savings.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center">
                                    <div class="space-y-1">
                                        <p class="font-medium text-foreground">No savings accounts yet</p>
                                        <p class="text-sm text-muted-foreground">Open a savings account for an active member to start tracking deposits, withdrawals, and interest.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="row in savings.data" :key="row.id" class="border-t">
                                <td class="px-4 py-3">{{ row.account_number }}</td>
                                <td class="px-4 py-3">{{ row.member?.first_name }} {{ row.member?.last_name }}</td>
                                <td class="px-4 py-3">{{ formatPhilippinePeso(row.current_balance) }}</td>
                                <td class="px-4 py-3">{{ row.interest_rate }}%</td>
                                <td class="px-4 py-3">
                                    <Badge :class="[getFinanceStatusBadgeClass(row.account_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                        {{ row.account_status }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <Link :href="viewHref(row.id)">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-view gap-2">
                                                <Eye class="h-4 w-4" />
                                                View
                                            </Button>
                                        </Link>
                                        <Link v-if="permissions.can_edit" :href="editHref(row.id)">
                                            <Button variant="ghost" size="sm" class="table-action-btn table-action-edit gap-2">
                                                <Pencil class="h-4 w-4" />
                                                Edit
                                            </Button>
                                        </Link>
                                        <Button
                                            v-if="permissions.can_close && row.account_status !== 'Closed'"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            class="table-action-btn table-action-delete gap-2 text-destructive hover:text-destructive"
                                            @click="closeAccount(row.id)"
                                        >
                                            <XCircle class="h-4 w-4" />
                                            Close
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </FinanceShellLayout>
</template>
