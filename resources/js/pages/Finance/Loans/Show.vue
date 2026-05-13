<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { getFinanceStatusBadgeClass } from '@/composables/useFinanceStatusBadge';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CalendarClock, CreditCard, Eye, File, FileText, Image, Pencil, ReceiptText, ShieldCheck, TrendingUp, Users } from 'lucide-vue-next';
import { computed } from 'vue';
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
        loanType?: { name?: string };
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
    nextPaymentDue?: { due_date?: string | null; total_due?: string | null } | null;
    permissions: {
        can_approve: boolean;
        can_disburse: boolean;
        can_edit: boolean;
        can_delete: boolean;
        can_record_payment: boolean;
    };
}>();

const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');
const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const coopIdFromUrl = computed(() => {
    const coopId = queryParams.value.get('coop_id');
    return coopId ? parseInt(coopId, 10) : null;
});
const isCoopContext = computed(() => Boolean(window.location.pathname.startsWith('/cooperatives/') || coopIdFromUrl.value || props.loan.coop_id || props.cooperative_id));
const coopContextId = computed(() => coopIdFromUrl.value || props.loan.coop_id || props.cooperative_id || props.loan.cooperative?.id || null);
const cooperativeName = computed(() => props.loan.cooperative?.name || 'Cooperative');

const returnToParam = computed(() => {
    const candidate = queryParams.value.get('return_to');
    if (!candidate || !candidate.startsWith('/') || candidate.startsWith('//')) {
        return '';
    }

    return candidate;
});

const backHref = computed(() => {
    if (returnToParam.value) {
        return returnToParam.value;
    }

    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance&subtab=loans`;
    }

    return '/finance/loans';
});

const editHref = computed(() => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/loans/${props.loan.id}/edit?return_to=${encodeURIComponent(backHref.value)}`;
    }

    return backHref.value ? `/finance/loans/${props.loan.id}/edit?return_to=${encodeURIComponent(backHref.value)}` : `/finance/loans/${props.loan.id}/edit`;
});

const approveForm = useForm({ remarks: '' });
const disburseForm = useForm({ amount: Number(props.loan.principal), disbursement_method: 'cash', remarks: '' });
const paymentForm = useForm({ amount: 0, paid_at: '', remarks: '' });

const isLifecycleLocked = ['Active', 'Completed'].includes(props.loan.status);

const actionUrl = (suffix: 'approve' | 'disburse' | 'payments') => {
    if (isCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}/finance/loans/${props.loan.id}/${suffix}`;
    }

    return `/finance/loans/${props.loan.id}/${suffix}`;
};

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
        approveForm.post(actionUrl('approve'));
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
        disburseForm.post(actionUrl('disburse'));
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
        paymentForm.post(actionUrl('payments'));
    }
};

const openFilePreview = (url: string) => {
    if (typeof window !== 'undefined') {
        window.open(url, '_blank', 'noopener,noreferrer');
    }
};

const goBack = () => {
    router.get(backHref.value);
};
</script>

<template>
    <Head :title="`Finance - Loan #${loan.id}`" />

    <FinanceShellLayout active-tab="loans" :hide-tabs="isCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex flex-col gap-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="space-y-2">
                        <div v-if="isCoopContext" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                            <span>/</span>
                            <a :href="`/cooperatives/${coopContextId}`" class="text-primary hover:underline">{{ cooperativeName }}</a>
                            <span>/</span>
                            <span class="text-foreground">Loan #{{ loan.id }}</span>
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-semibold tracking-tight">{{ loan.member?.first_name }} {{ loan.member?.last_name }}</h1>
                            <p class="text-sm text-muted-foreground">Loan #{{ loan.id }} and repayment progress</p>
                        </div>
                        <Badge :class="[getFinanceStatusBadgeClass(loan.status), 'w-fit rounded-full px-3 py-1 text-xs font-medium']">
                            {{ loan.status }}
                        </Badge>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 self-start lg:self-auto">
                        <Button variant="outline" class="gap-2" @click="goBack">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                        <Link v-if="permissions.can_edit" :href="editHref">
                            <Button class="gap-2">
                                <Pencil class="h-4 w-4" />
                                Edit
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Loan Amount</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(loan.principal) }}</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Interest Rate</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.interest_rate }}%</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Term</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.term_months }} months</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Remaining Balance</div>
                    <div class="mt-1 text-lg font-semibold">{{ formatPhilippinePeso(remainingBalance) }}</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Loan Type</div>
                    <div class="mt-1 text-lg font-semibold">{{ loan.loanType?.name || 'N/A' }}</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Cooperative</div>
                    <div class="mt-1 font-semibold">{{ loan.cooperative?.name || 'N/A' }}</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Created</div>
                    <div class="mt-1 font-semibold">{{ formatDate(loan.created_at) }}</div>
                </div>
                <div class="rounded-2xl border bg-card p-4 text-sm">
                    <div class="text-muted-foreground">Approved / Disbursed</div>
                    <div class="mt-1 font-semibold">{{ formatDate(loan.approved_at) }} / {{ formatDate(loan.disbursement_date) }}</div>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-xl">Purpose & Attachments</CardTitle>
                    <CardDescription>Review the original request notes and uploaded supporting files.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div class="rounded-2xl border border-border bg-muted/20 p-4 text-sm leading-6 text-foreground">
                        {{ loan.purpose || 'No purpose provided.' }}
                    </div>

                    <div v-if="loan.attachments && loan.attachments.length > 0" class="space-y-3">
                        <div v-for="file in loan.attachments" :key="file.path" class="flex flex-col gap-3 rounded-xl border border-border bg-background p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-start gap-3">
                                <Badge class="rounded-md px-2 py-0.5 text-xs font-medium">{{ getAttachmentLabel(file) }}</Badge>
                                <component :is="getAttachmentIcon(file)" class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground sm:hidden" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">Supporting document</p>
                                </div>
                            </div>
                            <Button type="button" variant="outline" size="sm" class="gap-2" @click="openFilePreview(file.url)">
                                <Eye class="h-3.5 w-3.5" />
                                Preview
                            </Button>
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">No files attached.</div>
                </CardContent>
            </Card>

            <div class="grid gap-4 xl:grid-cols-3">
                <Card v-if="permissions.can_approve" :class="{ 'opacity-60': isLifecycleLocked }">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <ShieldCheck class="h-5 w-5" />
                            Approve Loan
                        </CardTitle>
                        <CardDescription>Mark the loan as approved once the request is reviewed.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p v-if="isLifecycleLocked" class="text-xs text-muted-foreground">Approval is disabled because this loan is already {{ loan.status }}.</p>
                        <Textarea v-model="approveForm.remarks" rows="3" placeholder="Approval remarks" />
                        <Button type="button" class="w-full" :disabled="approveForm.processing || isLifecycleLocked" @click="approve">
                            {{ approveForm.processing ? 'Processing...' : 'Approve' }}
                        </Button>
                    </CardContent>
                </Card>

                <Card v-if="permissions.can_disburse" :class="{ 'opacity-60': isLifecycleLocked }">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <CreditCard class="h-5 w-5" />
                            Disburse Loan
                        </CardTitle>
                        <CardDescription>Release the approved amount and record how it was disbursed.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p v-if="isLifecycleLocked" class="text-xs text-muted-foreground">Disbursement is disabled because this loan is already {{ loan.status }}.</p>
                        <Input v-model.number="disburseForm.amount" type="number" step="0.01" placeholder="Amount" />
                        <select v-model="disburseForm.disbursement_method" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <Textarea v-model="disburseForm.remarks" rows="3" placeholder="Disbursement remarks" />
                        <Button type="button" class="w-full" :disabled="disburseForm.processing || isLifecycleLocked" @click="disburse">
                            {{ disburseForm.processing ? 'Processing...' : 'Disburse' }}
                        </Button>
                    </CardContent>
                </Card>

                <Card v-if="permissions.can_record_payment">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <ReceiptText class="h-5 w-5" />
                            Record Payment
                        </CardTitle>
                        <CardDescription>Post a member payment against the loan balance.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Input v-model.number="paymentForm.amount" type="number" step="0.01" placeholder="Amount" />
                        <Input v-model="paymentForm.paid_at" type="date" />
                        <Textarea v-model="paymentForm.remarks" rows="3" placeholder="Payment remarks" />
                        <Button type="button" class="w-full" :disabled="paymentForm.processing" @click="recordPayment">
                            {{ paymentForm.processing ? 'Processing...' : 'Save Payment' }}
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <CalendarClock class="h-5 w-5" />
                        Repayment Schedule
                    </CardTitle>
                    <CardDescription>Track the planned installments and their current status.</CardDescription>
                </CardHeader>
                <CardContent class="overflow-hidden rounded-2xl border border-border bg-background p-0">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left">#</th>
                                <th class="px-4 py-3 text-left">Due Date</th>
                                <th class="px-4 py-3 text-left">Amount</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="repaymentSchedule.length === 0">
                                <td colspan="4" class="px-4 py-6 text-center text-muted-foreground">No schedule generated yet.</td>
                            </tr>
                            <tr v-for="row in repaymentSchedule" :key="row.id" class="border-t">
                                <td class="px-4 py-3">{{ row.payment_number || '-' }}</td>
                                <td class="px-4 py-3">{{ formatDate(row.due_date) }}</td>
                                <td class="px-4 py-3">{{ row.total_due === null || row.total_due === undefined || row.total_due === '' ? '-' : formatPhilippinePeso(row.total_due) }}</td>
                                <td class="px-4 py-3">
                                    <Badge :class="[getFinanceStatusBadgeClass(row.status), 'rounded-md px-2 py-0.5 text-xs font-medium']">
                                        {{ row.status }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </FinanceShellLayout>
</template>
