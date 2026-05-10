<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Eye, Filter, Pencil, Plus, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

interface Loan {
    id: number;
    purpose: string | null;
    status: string;
    created_at: string;
    member?: { first_name?: string; last_name?: string } | null;
    loanType?: { name?: string } | null;
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
    loans: {
        data: Loan[];
    };
    cooperative?: { id: number; name: string } | null;
    cooperatives?: Array<{ id: number; name: string; status: string }>;
    statuses: string[];
    filters?: {
        status?: string;
    };
    permissions: {
        can_create: boolean;
        can_approve: boolean;
        can_disburse: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_record_payment: boolean;
    };
}>();

const currentUrl = window.location.pathname + window.location.search;
const selectedCoop = ref<Cooperative | null>(null);
const selectedMember = ref<Member | null>(null);
const coopMembers = ref<Member[]>([]);
const loadingMembers = ref(false);
const selectedStatus = ref(props.filters?.status || '');

const isGlobalMode = computed(() => !props.cooperative?.id && !new URLSearchParams(window.location.search).get('coop_id'));
const isCoopContext = computed(() => !isGlobalMode.value);
const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId, 10) : null;
});

const indexUrl = computed(() => {
    if (isCoopContext.value && props.cooperative?.id) {
        return `/cooperatives/${props.cooperative.id}/finance/loans`;
    }

    if (coopIdFromUrl.value) {
        return `/finance/loans?coop_id=${coopIdFromUrl.value}`;
    }

    return '/finance/loans';
});

const createHref = computed(() => {
    if (!props.permissions.can_create) {
        return '';
    }

    if (isCoopContext.value && (props.cooperative?.id || coopIdFromUrl.value)) {
        const coopId = props.cooperative?.id || coopIdFromUrl.value;
        return `/cooperatives/${coopId}/finance/loans/create?return_to=${encodeURIComponent(indexUrl.value)}`;
    }

    if (selectedMember.value) {
        return `/finance/loans/create?member_id=${selectedMember.value.id}&return_to=${encodeURIComponent(indexUrl.value)}`;
    }

    return `/finance/loans/create?return_to=${encodeURIComponent(indexUrl.value)}`;
});

const formatDate = (value: string | null | undefined) => {
    if (!value) {
        return 'N/A';
    }

    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const formatMemberName = (member?: { first_name?: string; last_name?: string } | null) => {
    if (!member) {
        return 'N/A';
    }

    return `${member.first_name || ''} ${member.last_name || ''}`.trim() || 'N/A';
};

const summary = computed(() => {
    const loans = props.loans.data || [];

    return {
        total: loans.length,
        pending: loans.filter((loan) => loan.status === 'Pending').length,
        active: loans.filter((loan) => loan.status === 'Active').length,
        completed: loans.filter((loan) => loan.status === 'Completed').length,
    };
});

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
    router.get(indexUrl.value, {
        member_id: member.id,
        status: selectedStatus.value || undefined,
    }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const backToCooperatives = () => {
    selectedCoop.value = null;
    selectedMember.value = null;
    coopMembers.value = [];
};

const backToMembers = () => {
    selectedMember.value = null;
};

const applyFilter = () => {
    const params: Record<string, string | number | undefined> = {
        status: selectedStatus.value || undefined,
    };

    if (selectedMember.value) {
        params.member_id = selectedMember.value.id;
    }

    if (isCoopContext.value && props.cooperative?.id) {
        router.get(`/cooperatives/${props.cooperative.id}/finance/loans`, params, {
            preserveState: true,
            preserveScroll: true,
        });
        return;
    }

    router.get('/finance/loans', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    selectedStatus.value = '';
    if (selectedMember.value) {
        router.get(indexUrl.value, {
            member_id: selectedMember.value.id,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
        return;
    }

    router.get(indexUrl.value, {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const deleteLoan = (loanId: number) => {
    if (!props.permissions.can_delete) {
        return;
    }

    void Swal.fire({
        title: 'Delete loan?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        const url = isCoopContext.value && props.cooperative?.id
            ? `/cooperatives/${props.cooperative.id}/finance/loans/${loanId}`
            : `/finance/loans/${loanId}`;

        router.delete(url);
    });
};

const showCooperativesList = computed(() => isGlobalMode.value && !selectedCoop.value);
const showMembersList = computed(() => isGlobalMode.value && selectedCoop.value && !selectedMember.value);
const showLoansList = computed(() => (isGlobalMode.value ? Boolean(selectedMember.value) : true));
</script>

<template>
    <Head title="Finance - Loans" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex flex-col gap-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <div v-if="isCoopContext" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                            <span>/</span>
                            <a :href="`/cooperatives/${props.cooperative?.id || coopIdFromUrl}`" class="text-primary hover:underline">{{ props.cooperative?.name || 'Cooperative' }}</a>
                            <span>/</span>
                            <span class="text-foreground">Loans</span>
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-semibold tracking-tight">Member Loans</h1>
                            <p class="max-w-2xl text-sm text-muted-foreground">
                                Apply, approve, disburse, and monitor every cooperative loan from one workspace.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link v-if="props.permissions.can_create && createHref" :href="createHref">
                            <Button class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                                <Plus class="h-4 w-4" />
                                New Loan
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardContent class="flex items-center gap-3 py-5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CreditCard class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Total</div>
                            <div class="text-2xl font-semibold">{{ summary.total }}</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 py-5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-amber-500/10 text-amber-600">
                            <Filter class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Pending</div>
                            <div class="text-2xl font-semibold">{{ summary.pending }}</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 py-5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                            <Users class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Active</div>
                            <div class="text-2xl font-semibold">{{ summary.active }}</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 py-5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-500/10 text-slate-600">
                            <TrendingUp class="h-5 w-5" />
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-muted-foreground">Completed</div>
                            <div class="text-2xl font-semibold">{{ summary.completed }}</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-xl">Loan Workspace</CardTitle>
                    <CardDescription>Use the cooperative picker first in global mode, then narrow to a member and status.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div v-if="showCooperativesList" class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <button v-for="coop in cooperatives" :key="coop.id" type="button" class="rounded-2xl border border-border bg-background p-4 text-left transition hover:border-primary hover:bg-primary/5" @click="selectCoop(coop)">
                                <div class="text-base font-semibold text-foreground">{{ coop.name }}</div>
                                <p class="mt-1 text-xs text-muted-foreground">Open this cooperative to browse members and loans.</p>
                            </button>
                        </div>
                        <div v-if="!cooperatives || cooperatives.length === 0" class="rounded-2xl border border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                            No cooperatives available.
                        </div>
                    </div>

                    <div v-else-if="showMembersList" class="space-y-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Button variant="outline" size="sm" class="gap-2" @click="backToCooperatives">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Cooperatives
                            </Button>
                            <div class="text-lg font-semibold">Members in {{ selectedCoop?.name }}</div>
                        </div>

                        <div v-if="loadingMembers" class="rounded-2xl border border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                            Loading members...
                        </div>

                        <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <button v-for="member in coopMembers" :key="member.id" type="button" class="rounded-2xl border border-border bg-background p-4 text-left transition hover:border-primary hover:bg-primary/5" @click="selectMember(member)">
                                <div class="text-base font-semibold text-foreground">{{ member.first_name }} {{ member.last_name }}</div>
                                <p class="mt-1 text-xs text-muted-foreground">Click to view that member's loans.</p>
                            </button>
                        </div>

                        <div v-if="!loadingMembers && (!coopMembers || coopMembers.length === 0)" class="rounded-2xl border border-border bg-muted/20 p-6 text-center text-sm text-muted-foreground">
                            No members found in this cooperative.
                        </div>
                    </div>

                    <div v-if="showLoansList" class="space-y-5">
                        <div v-if="isGlobalMode && selectedMember" class="flex flex-wrap items-center gap-3">
                            <Button variant="outline" size="sm" class="gap-2" @click="backToMembers">
                                <ArrowLeft class="h-4 w-4" />
                                Back to Members
                            </Button>
                            <div class="text-lg font-semibold">Loans for {{ selectedMember.first_name }} {{ selectedMember.last_name }}</div>
                        </div>

                        <div class="rounded-2xl border bg-muted/20 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                <div class="space-y-1">
                                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Filter loans</h2>
                                    <p class="text-sm text-muted-foreground">Narrow by status before reviewing or editing entries.</p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="space-y-1">
                                        <label class="block text-xs font-medium text-muted-foreground">Status</label>
                                        <select v-model="selectedStatus" class="min-w-45 rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                                            <option value="">All</option>
                                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                                        </select>
                                    </div>
                                    <Button variant="outline" class="gap-2" @click="applyFilter">
                                        <Filter class="h-4 w-4" />
                                        Apply
                                    </Button>
                                    <Button variant="ghost" class="gap-2" @click="resetFilters">
                                        Reset
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-2xl border bg-card">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Member</th>
                                        <th class="px-4 py-3 text-left">Loan Type</th>
                                        <th class="px-4 py-3 text-left">Purpose</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-left">Created</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="loans.data.length === 0">
                                        <td class="px-4 py-6 text-center text-muted-foreground" colspan="6">No loans found.</td>
                                    </tr>
                                    <tr v-for="loan in loans.data" :key="loan.id" class="border-t">
                                        <td class="px-4 py-3">{{ formatMemberName(loan.member) }}</td>
                                        <td class="px-4 py-3">{{ loan.loanType?.name || 'N/A' }}</td>
                                        <td class="px-4 py-3">{{ loan.purpose || 'N/A' }}</td>
                                        <td class="px-4 py-3">
                                            <Badge :class="[getFinanceStatusBadgeClass(loan.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                                {{ loan.status }}
                                            </Badge>
                                        </td>
                                        <td class="px-4 py-3">{{ formatDate(loan.created_at) }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap items-center justify-center gap-2">
                                                <Link :href="isCoopContext && props.cooperative?.id ? `/cooperatives/${props.cooperative.id}/finance/loans/${loan.id}` : (currentUrl ? `/finance/loans/${loan.id}?return_to=${encodeURIComponent(currentUrl)}` : `/finance/loans/${loan.id}`)">
                                                    <Button variant="ghost" size="sm" class="gap-2">
                                                        <Eye class="h-4 w-4" />
                                                        View
                                                    </Button>
                                                </Link>
                                                <Link v-if="props.permissions.can_edit && loan.status === 'Pending'" :href="isCoopContext && props.cooperative?.id ? `/cooperatives/${props.cooperative.id}/finance/loans/${loan.id}/edit` : (currentUrl ? `/finance/loans/${loan.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/loans/${loan.id}/edit`)">
                                                    <Button variant="ghost" size="sm" class="gap-2">
                                                        <Pencil class="h-4 w-4" />
                                                        Edit
                                                    </Button>
                                                </Link>
                                                <Button
                                                    v-if="props.permissions.can_delete && loan.status === 'Pending'"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="gap-2 text-destructive hover:text-destructive"
                                                    @click="deleteLoan(loan.id)"
                                                >
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
                </CardContent>
            </Card>
        </div>
    </FinanceShellLayout>
</template>
