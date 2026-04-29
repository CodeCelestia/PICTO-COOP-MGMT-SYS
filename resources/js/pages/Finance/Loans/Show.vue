<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Eye, File, FileText, Image } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import Swal from 'sweetalert2';

const props = defineProps<{
    loan: {
        id: number;
        coop_id?: number;
        status: string;
        principal: string;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        created_at?: string | null;
        approved_at?: string | null;
        disbursement_date?: string | null;
        member?: { first_name?: string; last_name?: string };
        loan_type?: { name?: string };
        cooperative?: { id?: number; name?: string } | null;
        attachments?: Array<{
            path: string;
            name: string;
            url: string;
            extension: string;
        }>;
    };
    from?: string | null;
    cooperative_id?: number | null;
    memberLoanCount: number;
    repaymentSchedule: Array<{
        id: number;
        payment_number: number | null;
        due_date: string | null;
        total_due: string | null;
        status: string;
    }>;
    remainingBalance: number;
    permissions: {
        can_approve: boolean;
        can_disburse: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_record_payment: boolean;
    };
}>();

const currentUrl = window.location.pathname + window.location.search;

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const coopContextId = computed(() => coopIdFromUrl.value || props.loan.coop_id || props.cooperative_id || null);
const isCoopContext = computed(() => coopContextId.value !== null);
const cooperativeName = computed(() => props.loan.cooperative?.name || 'Cooperative');

const backHref = computed(() => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance`;
    }

    if (props.from === 'coop' && props.cooperative_id) {
        return `/cooperatives/${props.cooperative_id}?tab=finance`;
    }

    return '/finance/loans';
});

const editHref = computed(() => {
    if (isCoopContext.value && coopContextId.value) {
        return `/finance/loans/${props.loan.id}/edit?coop_id=${coopContextId.value}`;
    }

    if (props.from === 'coop' && props.cooperative_id) {
        return `/finance/loans/${props.loan.id}/edit?coop_id=${props.cooperative_id}`;
    }

    return currentUrl ? `/finance/loans/${props.loan.id}/edit?return_to=${encodeURIComponent(currentUrl)}` : `/finance/loans/${props.loan.id}/edit`;
});

const approveForm = useForm({ remarks: '' });
const disburseForm = useForm({ amount: Number(props.loan.principal), disbursement_method: 'cash', remarks: '' });
const paymentForm = useForm({ amount: 0, paid_at: '', remarks: '' });

const approve = async () => {
    const result = await Swal.fire({
        title: 'Approve this loan?',
        text: 'This action will mark the loan as approved.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
    });

    if (result.isConfirmed) {
        approveForm.post(`/finance/loans/${props.loan.id}/approve`);
    }
};

const disburse = async () => {
    const result = await Swal.fire({
        title: 'Disburse this loan?',
        text: 'This action will disburse the loan to the member.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Disburse',
        cancelButtonText: 'Cancel',
    });

    if (result.isConfirmed) {
        disburseForm.post(`/finance/loans/${props.loan.id}/disburse`);
    }
};

const recordPayment = async () => {
    const result = await Swal.fire({
        title: 'Record this payment?',
        text: 'This action will record the payment against the loan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Record',
        cancelButtonText: 'Cancel',
    });

    if (result.isConfirmed) {
        paymentForm.post(`/finance/loans/${props.loan.id}/payments`);
    }
};
const isLifecycleLocked = ['Active', 'Completed'].includes(props.loan.status);

const formatDate = (value: string | null | undefined) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
};

const getAttachmentLabel = (attachment: { extension: string }) => {
    const extension = attachment.extension.toUpperCase();
    if (['JPG', 'JPEG', 'PNG', 'GIF', 'WEBP', 'BMP', 'SVG'].includes(extension)) {
        return 'IMG';
    }
    if (extension === 'PDF') {
        return 'PDF';
    }
    return extension || 'FILE';
};

const getAttachmentIcon = (attachment: { extension: string }) => {
    const extension = attachment.extension.toLowerCase();
    if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(extension)) {
        return Image;
    }
    if (extension === 'pdf') {
        return FileText;
    }
    return File;
};
</script>

<template>
    <Head :title="`Finance - ${loan.member?.first_name || ''} ${loan.member?.last_name || ''} - Loan #${memberLoanCount}`" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isCoopContext">
        <div class="space-y-6">
            <div v-if="isCoopContext" class="flex items-center justify-between gap-4">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ cooperativeName }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Loan #{{ loan.id }}</span>
                </nav>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Link :href="backHref">
                        <Button variant="outline" size="sm" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <Link v-if="permissions.can_edit" :href="editHref">
                        <Button size="sm" class="gap-2">
                            <Pencil class="h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>
            <div v-else class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">{{ loan.member?.first_name }} {{ loan.member?.last_name }} - Loan #{{ memberLoanCount }}</h1>
                    <p class="text-sm text-muted-foreground">
                        Loan ID #{{ loan.id }} | {{ loan.status }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Link :href="backHref">
                        <Button variant="outline" size="sm" class="gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <Link v-if="permissions.can_edit" :href="editHref">
                        <Button size="sm" class="gap-2">
                            <Pencil class="h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-4">
                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Cooperative</p>
                        <p class="text-base font-semibold text-foreground">{{ loan.cooperative?.name || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-muted-foreground">Member</p>
                        <p class="text-base font-semibold text-foreground">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Loan Amount</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(loan.principal) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Loan Type</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.loan_type?.name || 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Purpose</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.purpose || 'N/A' }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Status</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.status }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Remaining Balance</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(remainingBalance) }}</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Created</div>
                    <div class="mt-1 font-semibold">{{ formatDate(loan.created_at) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Approved</div>
                    <div class="mt-1 font-semibold">{{ formatDate(loan.approved_at) }}</div>
                </div>
                <div class="rounded-lg border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Disbursed</div>
                    <div class="mt-1 font-semibold">{{ formatDate(loan.disbursement_date) }}</div>
                </div>
            </div>

            <div class="rounded-lg border bg-card p-4">
                <div v-if="loan.attachments && loan.attachments.length > 0">
                    <h3 class="mb-3 text-sm font-semibold">Attachments</h3>
                    <div class="space-y-2">
                        <div v-for="file in loan.attachments" :key="file.path" class="flex flex-col gap-3 rounded-lg border border-border bg-background p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">{{ getAttachmentLabel(file) }}</Badge>
                                <component :is="getAttachmentIcon(file)" class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground sm:hidden" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ file.name }}</p>
                                </div>
                            </div>
                            <button type="button" @click="window.open(file.url, '_blank', 'noopener,noreferrer')" class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-2 text-xs font-medium text-foreground hover:bg-muted">
                                <Eye class="h-3.5 w-3.5" />
                                Preview
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="text-sm text-muted-foreground">No files attached.</div>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <form
                    v-if="permissions.can_approve"
                    class="rounded-lg border bg-card p-4"
                    :class="{ 'opacity-60 bg-muted/40': isLifecycleLocked }"
                    @submit.prevent="approve"
                >
                    <h2 class="font-semibold">Approve Loan</h2>
                    <p v-if="isLifecycleLocked" class="mt-2 text-xs text-muted-foreground">
                        Approval is disabled because this loan is already {{ loan.status }}.
                    </p>
                    <textarea v-model="approveForm.remarks" rows="3" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Approval remarks"></textarea>
                    <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="approveForm.processing || isLifecycleLocked">{{ approveForm.processing ? 'Processing...' : 'Approve' }}</button>
                </form>

                <form
                    v-if="permissions.can_disburse"
                    class="rounded-lg border bg-card p-4"
                    :class="{ 'opacity-60 bg-muted/40': isLifecycleLocked }"
                    @submit.prevent="disburse"
                >
                    <h2 class="font-semibold">Disburse</h2>
                    <p v-if="isLifecycleLocked" class="mt-2 text-xs text-muted-foreground">
                        Disbursement is disabled because this loan is already {{ loan.status }}.
                    </p>
                    <input v-model.number="disburseForm.amount" type="number" step="0.01" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Amount" />
                    <select v-model="disburseForm.disbursement_method" class="mt-3 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="cash">Cash</option>
                        <option value="check">Check</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                    <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="disburseForm.processing || isLifecycleLocked">{{ disburseForm.processing ? 'Processing...' : 'Disburse' }}</button>
                </form>

                <form v-if="permissions.can_record_payment" class="rounded-lg border bg-card p-4" @submit.prevent="recordPayment">
                    <h2 class="font-semibold">Record Payment</h2>
                    <input v-model.number="paymentForm.amount" type="number" step="0.01" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" placeholder="Amount" />
                    <input v-model="paymentForm.paid_at" type="date" class="mt-3 w-full rounded-md border px-3 py-2 text-sm" />
                    <button type="submit" class="mt-3 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" :disabled="paymentForm.processing">{{ paymentForm.processing ? 'Processing...' : 'Save Payment' }}</button>
                </form>
            </div>

            <div class="overflow-hidden rounded-lg border bg-card">
                <div class="border-b px-4 py-3 text-sm font-semibold">Repayment Schedule</div>
                <table class="w-full text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Due Date</th>
                            <th class="px-4 py-2 text-left">Amount</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="repaymentSchedule.length === 0">
                            <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No schedule generated yet.</td>
                        </tr>
                        <tr v-for="row in repaymentSchedule" :key="row.id" class="border-t">
                            <td class="px-4 py-2">{{ row.payment_number || '-' }}</td>
                            <td class="px-4 py-2">{{ formatDate(row.due_date) }}</td>
                            <td class="px-4 py-2">{{ row.total_due === null || row.total_due === undefined || row.total_due === '' ? '-' : formatPhilippinePeso(row.total_due) }}</td>
                            <td class="px-4 py-2">
                                <Badge :class="[getFinanceStatusBadgeClass(row.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                    {{ row.status }}
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </FinanceShellLayout>
</template>
