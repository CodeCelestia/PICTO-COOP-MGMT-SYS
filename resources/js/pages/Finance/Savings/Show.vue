<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import { useCreateBack } from '@/composables/useCreateBack';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ArrowLeft, Calculator, ReceiptText, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    savings: {
        id: number;
        account_number: string;
        account_status: string;
        current_balance: string;
        interest_rate: string;
        member?: { first_name?: string; last_name?: string };
        coop_id?: number;
        cooperative?: { name?: string } | null;
    };
    transactions: {
        data: Array<{
            id: number;
            type: string;
            amount: string;
            balance_after: string;
            recorded_at: string | null;
        }>;
    };
    totalInterestEarned: number;
    permissions: {
        can_edit: boolean;
        can_close: boolean;
        can_record_transaction: boolean;
        can_calculate_interest: boolean;
    };
}>();

const coopSlug = computed(() => usePage().props.auth?.user?.coop_slug ?? 'my');

const isFromCoopContext = computed(() => {
    // Check URL path for coop context
    if (window.location.pathname.startsWith('/cooperatives/')) {
        return true;
    }
    // Also check query parameter for backward compatibility
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const cooperativeName = computed(() => props.savings.cooperative?.name || 'Cooperative');
const page = usePage();
const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const returnToParam = computed(() => {
    const candidate = queryParams.value.get('return_to');
    if (!candidate || !candidate.startsWith('/') || candidate.startsWith('//')) {
        return '';
    }

    return candidate;
});
const coopContextId = computed(() => coopIdFromUrl.value || props.savings.coop_id || null);

const { goBack } = useCreateBack({ fallbackHref: '/finance/savings' });
const currentUrl = computed(() => window.location.pathname + window.location.search);

const displayText = (value: string | null | undefined) => {
    if (!value) return '—';
    const text = String(value).trim();
    return text === '' ? '—' : text;
};

const formatDateLong = (value: string | null | undefined) => {
    if (!value) return '—';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

const transactionUrl = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${props.savings.id}/transactions`;
    }

    return `/finance/savings/${props.savings.id}/transactions`;
});

const interestUrl = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${props.savings.id}/calculate-interest`;
    }

    return `/finance/savings/${props.savings.id}/calculate-interest`;
});

const editHref = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/savings/${props.savings.id}/edit`;
    }

    return currentUrl.value
        ? `/finance/savings/${props.savings.id}/edit?return_to=${encodeURIComponent(currentUrl.value)}`
        : `/finance/savings/${props.savings.id}/edit`;
});

const handleBackClick = () => {
    if (returnToParam.value) {
        router.get(returnToParam.value);
        return;
    }

    if (isFromCoopContext.value && coopContextId.value) {
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=savings`);
    } else {
        goBack();
    }
};

const txForm = useForm({
    type: 'Deposit',
    amount: 0,
    remarks: '',
});

const interestForm = useForm({});

const submitTransaction = () => {
    txForm.post(transactionUrl.value);
};

const calculateInterest = () => {
    interestForm.post(interestUrl.value);
};
</script>

<template>
    <Head :title="`Finance - Savings ${savings.account_number}`" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Savings Account {{ savings.account_number }}</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Detailed account summary and transaction actions.</p>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" @click="handleBackClick">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back
                        </Button>
                        <Link v-if="permissions.can_edit" :href="editHref">
                            <Button>Edit</Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardContent class="py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Current Balance</p>
                        <p class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(savings.current_balance) }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Interest Rate</p>
                        <p class="mt-1 text-lg font-semibold">{{ displayText(savings.interest_rate) }}%</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total Interest Earned</p>
                        <p class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(totalInterestEarned) }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">Account Information</CardTitle>
                            <CardDescription>Core details for this member savings account.</CardDescription>
                        </div>
                        <Badge :class="[getFinanceStatusBadgeClass(savings.account_status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                            {{ savings.account_status }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Member</p>
                            <p class="mt-1 text-sm font-medium">{{ displayText(`${savings.member?.first_name || ''} ${savings.member?.last_name || ''}`.trim()) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Cooperative</p>
                            <p class="mt-1 text-sm font-medium">{{ cooperativeName }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 xl:grid-cols-2">
                <Card v-if="permissions.can_record_transaction">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <ReceiptText class="h-5 w-5" />
                            Record Transaction
                        </CardTitle>
                        <CardDescription>Add a deposit or withdrawal entry.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <select v-model="txForm.type" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="Deposit">Deposit</option>
                            <option value="Withdrawal">Withdrawal</option>
                        </select>
                        <Input v-model.number="txForm.amount" type="number" min="0" step="0.01" placeholder="Amount" />
                        <Textarea v-model="txForm.remarks" rows="3" placeholder="Remarks (Optional)" />
                        <Button type="button" :disabled="txForm.processing" @click="submitTransaction">
                            {{ txForm.processing ? 'Saving...' : 'Save Transaction' }}
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <Calculator class="h-5 w-5" />
                            Interest Calculation
                        </CardTitle>
                        <CardDescription>Apply monthly interest manually for this account.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button v-if="permissions.can_calculate_interest" type="button" :disabled="interestForm.processing" @click="calculateInterest">
                            {{ interestForm.processing ? 'Processing...' : 'Calculate Interest' }}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <TrendingUp class="h-5 w-5" />
                        Transactions
                    </CardTitle>
                    <CardDescription>Chronological transaction history for this account.</CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-hidden rounded-b-xl border-t">
                        <table class="w-full text-sm">
                            <thead class="bg-muted/40">
                                <tr>
                                    <th class="px-4 py-2 text-left">Type</th>
                                    <th class="px-4 py-2 text-left">Amount</th>
                                    <th class="px-4 py-2 text-left">Balance After</th>
                                    <th class="px-4 py-2 text-left">Recorded At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No transactions recorded.</td>
                                </tr>
                                <tr v-for="tx in transactions.data" :key="tx.id" class="border-t">
                                    <td class="px-4 py-2">{{ displayText(tx.type) }}</td>
                                    <td class="px-4 py-2">{{ formatPhilippinePeso(tx.amount) }}</td>
                                    <td class="px-4 py-2">{{ formatPhilippinePeso(tx.balance_after) }}</td>
                                    <td class="px-4 py-2">{{ formatDateLong(tx.recorded_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </FinanceShellLayout>
</template>
